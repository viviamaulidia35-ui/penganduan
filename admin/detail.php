<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/detail_parts.php';
$admin = require_login('admin');

$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'ID pengaduan tidak valid.');
    redirect('admin/dashboard.php');
}

/* ---------- Proses pembaruan status + umpan balik ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusBaru = (string) ($_POST['status'] ?? '');
    $umpan      = trim((string) ($_POST['umpan_balik'] ?? ''));
    $err = '';

    if (!csrf_verify()) {
        $err = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!in_array($statusBaru, STATUS_LIST, true)) {
        $err = 'Status yang dipilih tidak valid.';
    } elseif (mb_strlen($umpan) > 1000) {
        $err = 'Umpan balik maksimal 1000 karakter.';
    } elseif ($statusBaru === 'Ditolak' && $umpan === '') {
        $err = 'Berikan alasan penolakan pada kolom umpan balik.';
    }

    if ($err === '') {
        try {
            $pdo->beginTransaction();
            $cur = $pdo->prepare('SELECT status FROM pengaduan WHERE id = ? FOR UPDATE');
            $cur->execute([$id]);
            $lama = $cur->fetchColumn();
            if ($lama === false) {
                throw new RuntimeException('Pengaduan tidak ditemukan.');
            }
            if ($lama === $statusBaru && $umpan === '') {
                $pdo->rollBack();
                $err = 'Tidak ada perubahan: ubah status atau tuliskan umpan balik.';
            } else {
                $pdo->prepare('UPDATE pengaduan SET status = ? WHERE id = ?')->execute([$statusBaru, $id]);
                $pdo->prepare(
                    'INSERT INTO riwayat_status (pengaduan_id, admin_id, status_lama, status_baru, umpan_balik)
                     VALUES (?, ?, ?, ?, ?)'
                )->execute([$id, $admin['id'], $lama, $statusBaru, $umpan !== '' ? $umpan : null]);
                $pdo->commit();
                flash('success', 'Status dan umpan balik berhasil diperbarui.');
                redirect('admin/detail.php?id=' . $id);
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'Perubahan gagal disimpan. Silakan coba lagi.';
        }
    }
    flash('error', $err);
    redirect('admin/detail.php?id=' . $id);
}

/* ---------- Ambil data ---------- */
$st = $pdo->prepare(
    'SELECT p.*, k.nama AS kategori, u.nama, u.kelas, u.email
     FROM pengaduan p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.user_id
     WHERE p.id = ?'
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash('error', 'Pengaduan tidak ditemukan.');
    redirect('admin/dashboard.php');
}

$h = $pdo->prepare(
    'SELECT r.*, a.nama AS admin_nama FROM riwayat_status r
     LEFT JOIN users a ON a.id = r.admin_id WHERE r.pengaduan_id = ? ORDER BY r.id'
);
$h->execute([$id]);
$riwayat = $h->fetchAll();

layout_start('Kelola Pengaduan', 'dashboard');
?>
<a class="back" href="<?= url('admin/dashboard.php') ?>"><?= icon('back', 16) ?> Kembali ke dashboard</a>
<div class="split">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2><?= e($p['judul']) ?></h2><?= badge($p['status']) ?></div>
      <?php detail_body($p); ?>
    </section>

    <section class="card">
      <div class="card-head"><h2>Perbarui Status &amp; Umpan Balik</h2></div>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="seg" role="radiogroup" aria-label="Status pengaduan">
          <?php foreach (STATUS_LIST as $s): ?>
            <label class="seg-item <?= status_class($s) ?>">
              <input type="radio" name="status" value="<?= e($s) ?>" <?= $p['status'] === $s ? 'checked' : '' ?>>
              <span><?= e($s) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <label class="field">
          <span>Umpan Balik untuk Siswa <em>(wajib bila ditolak)</em></span>
          <textarea name="umpan_balik" rows="4" maxlength="1000" placeholder="Tuliskan perkembangan atau catatan penanganan…"></textarea>
        </label>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Simpan Perubahan <?= icon('check', 16) ?></button>
        </div>
      </form>
    </section>
  </div>

  <aside class="card">
    <div class="card-head"><h2>Riwayat Perubahan</h2></div>
    <?php timeline($riwayat); ?>
  </aside>
</div>
<?php layout_end();
