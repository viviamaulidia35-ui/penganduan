<?php
/** Bagian tampilan detail pengaduan yang dipakai siswa dan admin. */
function detail_body(array $p): void
{ ?>
  <div class="detail-meta">
    <div><span>Kategori</span><b><?= e($p['kategori']) ?></b></div>
    <div><span>Lokasi</span><b><?= icon('pin', 14) ?> <?= e($p['lokasi']) ?></b></div>
    <div><span>Tanggal</span><b><?= e(tgl($p['tanggal_pengaduan'])) ?></b></div>
    <div><span>Pelapor</span><b><?= e($p['nama']) ?><?= $p['kelas'] ? ' · ' . e($p['kelas']) : '' ?></b></div>
  </div>
  <h3 class="sub">Deskripsi</h3>
  <p class="prose"><?= nl2br(e($p['deskripsi'])) ?></p>
  <?php if ($p['foto']): ?>
    <h3 class="sub">Foto Bukti</h3>
    <a href="<?= url('uploads/' . rawurlencode($p['foto'])) ?>" target="_blank" rel="noopener">
      <img class="proof" src="<?= url('uploads/' . rawurlencode($p['foto'])) ?>" alt="Foto bukti pengaduan">
    </a>
  <?php endif;
}

function timeline(array $items): void
{ ?>
  <ol class="timeline">
    <?php foreach (array_reverse($items) as $i => $r): ?>
      <li class="<?= $i === 0 ? 'current' : '' ?>">
        <span class="dot <?= status_class($r['status_baru']) ?>"></span>
        <div class="tl-body">
          <div class="tl-top"><?= badge($r['status_baru']) ?><time><?= e(tgl($r['created_at'], true)) ?></time></div>
          <?php if ($r['umpan_balik']): ?><p><?= nl2br(e($r['umpan_balik'])) ?></p><?php endif; ?>
          <small><?= $r['admin_nama'] ? 'Oleh ' . e($r['admin_nama']) : 'Sistem' ?></small>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
<?php }
