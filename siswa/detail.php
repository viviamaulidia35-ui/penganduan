<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/detail_parts.php';
$user = require_login('siswa');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$p = false;
if ($id) {
    $st = db()->prepare(
        'SELECT p.*, k.nama AS kategori, u.nama, u.kelas
         FROM pengaduan p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.user_id
         WHERE p.id = ? AND p.user_id = ?'
    );
    $st->execute([$id, $user['id']]);
    $p = $st->fetch();
}
if (!$p) {
    flash('error', 'Pengaduan tidak ditemukan.');
    redirect('siswa/riwayat.php');
}

$h = db()->prepare(
    'SELECT r.*, a.nama AS admin_nama FROM riwayat_status r
     LEFT JOIN users a ON a.id = r.admin_id WHERE r.pengaduan_id = ? ORDER BY r.id'
);
$h->execute([$p['id']]);
$riwayat = $h->fetchAll();

layout_start('Detail Pengaduan', 'riwayat');
?>
<a class="back" href="<?= url('siswa/riwayat.php') ?>"><?= icon('back', 16) ?> Kembali ke riwayat</a>
<div class="split">
  <section class="card">
    <div class="card-head"><h2><?= e($p['judul']) ?></h2><?= badge($p['status']) ?></div>
    <?php detail_body($p); ?>
  </section>
  <aside class="card">
    <div class="card-head"><h2>Perkembangan Penanganan</h2></div>
    <?php timeline($riwayat); ?>
  </aside>
</div>
<?php layout_end();
