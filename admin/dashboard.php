<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$user = require_login('admin');

$pdo = db();
$counts   = count_by_status();
$kategori = kategori_list();
$murid    = $pdo->query("SELECT id, nama, kelas FROM users WHERE role = 'siswa' ORDER BY nama")->fetchAll();

/* ---------- Ambil & validasi filter ---------- */
$q        = trim((string) ($_GET['q'] ?? ''));
$status   = (string) ($_GET['status'] ?? '');
$kat      = (int) ($_GET['kategori'] ?? 0);
$mur      = (int) ($_GET['murid'] ?? 0);
$tanggal  = (string) ($_GET['tanggal'] ?? '');
$bulan    = (string) ($_GET['bulan'] ?? '');

if (!in_array($status, STATUS_LIST, true)) { $status = ''; }
if ($tanggal !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) { $tanggal = ''; }
if ($bulan !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) { $bulan = ''; }

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = '(p.judul LIKE ? OR p.lokasi LIKE ? OR p.deskripsi LIKE ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like);
}
if ($status !== '') { $where[] = 'p.status = ?';            $args[] = $status; }
if ($kat > 0)       { $where[] = 'p.kategori_id = ?';       $args[] = $kat; }
if ($mur > 0)       { $where[] = 'p.user_id = ?';           $args[] = $mur; }
if ($tanggal !== ''){ $where[] = 'p.tanggal_pengaduan = ?'; $args[] = $tanggal; }
if ($bulan !== '')  { $where[] = "DATE_FORMAT(p.tanggal_pengaduan, '%Y-%m') = ?"; $args[] = $bulan; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- Pagination ---------- */
$cnt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan p $whereSql");
$cnt->execute($args);
$total = (int) $cnt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page  = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * PER_PAGE;

$st = $pdo->prepare(
    "SELECT p.id, p.judul, p.lokasi, p.status, p.tanggal_pengaduan,
            k.nama AS kategori, u.nama AS pelapor, u.kelas
     FROM pengaduan p
     JOIN kategori k ON k.id = p.kategori_id
     JOIN users u ON u.id = p.user_id
     $whereSql
     ORDER BY p.tanggal_pengaduan DESC, p.id DESC
     LIMIT " . PER_PAGE . " OFFSET $offset"
);
$st->execute($args);
$rows = $st->fetchAll();

$filters = array_filter(
    ['q' => $q, 'status' => $status, 'kategori' => $kat ?: '', 'murid' => $mur ?: '', 'tanggal' => $tanggal, 'bulan' => $bulan],
    fn($v) => $v !== ''
);
$isFiltered = (bool) $filters;
$pageUrl = fn(int $n) => url('admin/dashboard.php') . '?' . http_build_query($filters + ['page' => $n]);

layout_start('Dashboard Admin', 'dashboard', 'Pantau dan kelola seluruh pengaduan sarana sekolah.');
stat_cards($counts);
?>
<section class="card">
  <div class="card-head">
    <h2>Daftar Pengaduan <span class="count"><?= $total ?></span></h2>
  </div>

  <form class="filters" method="get" action="">
    <label class="search">
      <?= icon('search', 17) ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul, lokasi, atau isi pengaduan…">
    </label>

    <label class="field mini"><span>Status</span>
      <select name="status">
        <option value="">Semua</option>
        <?php foreach (STATUS_LIST as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
      </select>
    </label>

    <label class="field mini"><span>Kategori</span>
      <select name="kategori">
        <option value="">Semua</option>
        <?php foreach ($kategori as $k): ?><option value="<?= (int) $k['id'] ?>" <?= $kat === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?>
      </select>
    </label>

    <label class="field mini"><span>Murid</span>
      <select name="murid">
        <option value="">Semua</option>
        <?php foreach ($murid as $m): ?><option value="<?= (int) $m['id'] ?>" <?= $mur === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['nama']) ?></option><?php endforeach; ?>
      </select>
    </label>

    <label class="field mini"><span>Tanggal</span>
      <input type="date" name="tanggal" value="<?= e($tanggal) ?>">
    </label>

    <label class="field mini"><span>Bulan</span>
      <input type="month" name="bulan" value="<?= e($bulan) ?>">
    </label>

    <div class="filter-actions">
      <button class="btn btn-primary btn-sm" type="submit"><?= icon('filter', 15) ?> Terapkan</button>
      <?php if ($isFiltered): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/dashboard.php') ?>">Reset</a><?php endif; ?>
    </div>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <?= icon('search', 34) ?>
      <h3>Tidak ada pengaduan ditemukan</h3>
      <p><?= $isFiltered ? 'Coba ubah atau reset filter pencarian.' : 'Belum ada pengaduan yang masuk.' ?></p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Pengaduan</th><th>Pelapor</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="muted">#<?= (int) $r['id'] ?></td>
            <td><span class="strong"><?= e($r['judul']) ?></span><small class="sub-line"><?= icon('pin', 12) ?> <?= e($r['lokasi']) ?></small></td>
            <td><?= e($r['pelapor']) ?><small class="sub-line"><?= e($r['kelas']) ?></small></td>
            <td><?= e($r['kategori']) ?></td>
            <td><?= e(tgl($r['tanggal_pengaduan'])) ?></td>
            <td><?= badge($r['status']) ?></td>
            <td class="right"><a class="btn btn-ghost btn-sm" href="<?= url('admin/detail.php?id=' . $r['id']) ?>">Kelola <?= icon('arrow', 14) ?></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Halaman">
        <?php if ($page > 1): ?><a href="<?= e($pageUrl($page - 1)) ?>">‹ Sebelumnya</a><?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a class="<?= $i === $page ? 'on' : '' ?>" href="<?= e($pageUrl($i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= e($pageUrl($page + 1)) ?>">Berikutnya ›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php layout_end();
