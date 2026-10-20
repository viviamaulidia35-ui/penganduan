<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$user = require_login('siswa');

$counts = count_by_status($user['id']);
$st = db()->prepare(
    'SELECT p.id, p.judul, p.tanggal_pengaduan, p.status, k.nama AS kategori
     FROM pengaduan p JOIN kategori k ON k.id = p.kategori_id
     WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC LIMIT 5'
);
$st->execute([$user['id']]);
$recent = $st->fetchAll();

layout_start('Dashboard', 'dashboard', 'Halo, ' . explode(' ', $user['nama'])[0] . '. Berikut ringkasan pengaduan Anda.');
stat_cards($counts);
?>
<section class="card">
  <div class="card-head">
    <h2>Pengaduan Terbaru</h2>
    <a class="link" href="<?= url('siswa/riwayat.php') ?>">Lihat semua <?= icon('arrow', 14) ?></a>
  </div>

  <?php if (!$recent): ?>
    <div class="empty">
      <?= icon('inbox', 34) ?>
      <h3>Belum ada pengaduan</h3>
      <p>Temukan sarana sekolah yang rusak? Laporkan sekarang agar segera ditangani.</p>
      <a class="btn btn-primary" href="<?= url('siswa/buat.php') ?>"><?= icon('plus', 16) ?> Buat Pengaduan</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Judul</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td class="strong"><?= e($r['judul']) ?></td>
            <td><?= e($r['kategori']) ?></td>
            <td><?= e(tgl($r['tanggal_pengaduan'])) ?></td>
            <td><?= badge($r['status']) ?></td>
            <td class="right"><a class="btn btn-ghost btn-sm" href="<?= url('siswa/detail.php?id=' . $r['id']) ?>"><?= icon('eye', 15) ?> Detail</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php layout_end();
