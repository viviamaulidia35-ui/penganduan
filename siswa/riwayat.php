<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$user = require_login('siswa');

$st = db()->prepare(
    "SELECT p.id, p.judul, p.tanggal_pengaduan, p.status, k.nama AS kategori,
            (SELECT r.umpan_balik FROM riwayat_status r
              WHERE r.pengaduan_id = p.id AND r.admin_id IS NOT NULL AND r.umpan_balik <> ''
              ORDER BY r.id DESC LIMIT 1) AS umpan_balik
     FROM pengaduan p JOIN kategori k ON k.id = p.kategori_id
     WHERE p.user_id = ? ORDER BY p.tanggal_pengaduan DESC, p.id DESC"
);
$st->execute([$user['id']]);
$rows = $st->fetchAll();

layout_start('Riwayat Pengaduan', 'riwayat', 'Semua pengaduan yang pernah Anda kirim.');
?>
<section class="card">
  <?php if (!$rows): ?>
    <div class="empty">
      <?= icon('inbox', 34) ?>
      <h3>Belum ada pengaduan</h3>
      <p>Riwayat akan muncul di sini setelah Anda mengirim pengaduan pertama.</p>
      <a class="btn btn-primary" href="<?= url('siswa/buat.php') ?>"><?= icon('plus', 16) ?> Buat Pengaduan</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Judul</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th>Umpan Balik Admin</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="strong"><?= e($r['judul']) ?></td>
            <td><?= e($r['kategori']) ?></td>
            <td><?= e(tgl($r['tanggal_pengaduan'])) ?></td>
            <td><?= badge($r['status']) ?></td>
            <td class="note"><?= $r['umpan_balik'] ? e(mb_strimwidth($r['umpan_balik'], 0, 70, '…')) : '<span class="muted">Belum ada</span>' ?></td>
            <td class="right"><a class="btn btn-ghost btn-sm" href="<?= url('siswa/detail.php?id=' . $r['id']) ?>"><?= icon('eye', 15) ?> Detail</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php layout_end();
