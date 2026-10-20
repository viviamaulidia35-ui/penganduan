<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$user = require_login('siswa');

$kategori = kategori_list();
$old = ['judul' => '', 'kategori_id' => '', 'lokasi' => '', 'deskripsi' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }
    if ($old['judul'] === '' || mb_strlen($old['judul']) > 150) {
        $errors[] = 'Judul wajib diisi (maksimal 150 karakter).';
    }
    $kategoriIds = array_map('intval', array_column($kategori, 'id'));
    if (!in_array((int) $old['kategori_id'], $kategoriIds, true)) {
        $errors[] = 'Pilih kategori sarana yang valid.';
    }
    if ($old['lokasi'] === '' || mb_strlen($old['lokasi']) > 100) {
        $errors[] = 'Lokasi wajib diisi (maksimal 100 karakter).';
    }
    if (mb_strlen($old['deskripsi']) < 10) {
        $errors[] = 'Deskripsi masalah minimal 10 karakter.';
    }

    /* ---- Unggah foto (opsional) ---- */
    $fotoName = null;
    $fotoTmp  = null;
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['foto'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Foto gagal diunggah. Coba lagi dengan file yang lebih kecil.';
        } elseif ($f['size'] > UPLOAD_MAX) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if ($ext === null || @getimagesize($f['tmp_name']) === false) {
                $errors[] = 'Format foto harus JPG, PNG, atau WEBP.';
            } else {
                $fotoName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $fotoTmp  = $f['tmp_name'];
            }
        }
    }

    if (!$errors) {
        $pdo = db();
        $saved = false;
        try {
            if ($fotoName && !move_uploaded_file($fotoTmp, __DIR__ . '/../uploads/' . $fotoName)) {
                throw new RuntimeException('Gagal menyimpan foto.');
            }
            $pdo->beginTransaction();
            $ins = $pdo->prepare(
                "INSERT INTO pengaduan (user_id, kategori_id, judul, lokasi, deskripsi, foto, status, tanggal_pengaduan)
                 VALUES (?, ?, ?, ?, ?, ?, 'Menunggu', CURDATE())"
            );
            $ins->execute([$user['id'], (int) $old['kategori_id'], $old['judul'], $old['lokasi'], $old['deskripsi'], $fotoName]);
            $id = (int) $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO riwayat_status (pengaduan_id, admin_id, status_lama, status_baru, umpan_balik)
                 VALUES (?, NULL, NULL, 'Menunggu', 'Pengaduan berhasil dikirim dan menunggu ditinjau admin.')"
            )->execute([$id]);
            $pdo->commit();
            $saved = true;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($fotoName && is_file(__DIR__ . '/../uploads/' . $fotoName)) {
                @unlink(__DIR__ . '/../uploads/' . $fotoName);
            }
            $errors[] = 'Pengaduan gagal disimpan. Silakan coba lagi.';
        }
        if ($saved) {
            flash('success', 'Pengaduan berhasil dikirim. Terima kasih atas laporan Anda.');
            redirect('siswa/detail.php?id=' . $id);
        }
    }
}

layout_start('Buat Pengaduan', 'buat', 'Ceritakan kerusakan sarana yang Anda temukan.');
?>
<section class="card form-card">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <strong>Periksa kembali isian Anda:</strong>
      <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="grid-2">
      <label class="field span-2">
        <span>Judul Pengaduan</span>
        <input type="text" name="judul" maxlength="150" value="<?= e($old['judul']) ?>" placeholder="mis. Proyektor kelas tidak menyala" required>
      </label>

      <label class="field">
        <span>Kategori Sarana</span>
        <select name="kategori_id" required>
          <option value="">— Pilih kategori —</option>
          <?php foreach ($kategori as $k): ?>
            <option value="<?= (int) $k['id'] ?>" <?= (string) $k['id'] === $old['kategori_id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="field">
        <span>Lokasi</span>
        <input type="text" name="lokasi" maxlength="100" value="<?= e($old['lokasi']) ?>" placeholder="mis. Ruang XII RPL 1, lantai 2" required>
      </label>

      <label class="field span-2">
        <span>Deskripsi Masalah</span>
        <textarea name="deskripsi" rows="5" placeholder="Jelaskan kondisi kerusakan dan sejak kapan terjadi…" required><?= e($old['deskripsi']) ?></textarea>
      </label>

      <label class="field span-2">
        <span>Foto Bukti <em>(opsional · JPG/PNG/WEBP · maks. 2 MB)</em></span>
        <div class="upload" id="upload">
          <?= icon('image', 26) ?>
          <div><b id="uploadName">Pilih atau seret foto ke sini</b><small>Foto membantu petugas menilai kerusakan lebih cepat</small></div>
          <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp">
        </div>
      </label>
    </div>

    <div class="form-actions">
      <a class="btn btn-ghost" href="<?= url('siswa/dashboard.php') ?>">Batal</a>
      <button class="btn btn-primary" type="submit">Kirim Pengaduan <?= icon('arrow', 16) ?></button>
    </div>
  </form>
</section>
<?php layout_end();
