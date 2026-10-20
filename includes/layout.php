<?php
/**
 * Layout bersama untuk halaman setelah login.
 */
function layout_start(string $title, string $active, string $subtitle = ''): void
{
    $user = current_user();
    $isAdmin = $user['role'] === 'admin';

    $nav = $isAdmin
        ? [
            ['admin/dashboard.php', 'grid', 'Dashboard', 'dashboard'],
        ]
        : [
            ['siswa/dashboard.php', 'grid', 'Dashboard', 'dashboard'],
            ['siswa/buat.php', 'plus', 'Buat Pengaduan', 'buat'],
            ['siswa/riwayat.php', 'clock', 'Riwayat Pengaduan', 'riwayat'],
        ];

    $initial = strtoupper(mb_substr($user['nama'], 0, 1));
    $flash = flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · ZENIN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="app">
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url(home_for($user['role'])) ?>">
      <span class="brand-mark">Z</span>
      <span class="brand-text">ZENIN<small>Pengaduan Sarana Sekolah</small></span>
    </a>

    <nav class="nav">
      <span class="nav-label"><?= $isAdmin ? 'Administrasi' : 'Menu Siswa' ?></span>
      <?php foreach ($nav as [$href, $ico, $label, $key]): ?>
        <a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="<?= url($href) ?>">
          <?= icon($ico) ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
      <div class="who">
        <span class="avatar"><?= e($initial) ?></span>
        <span class="who-text">
          <strong><?= e($user['nama']) ?></strong>
          <small><?= $isAdmin ? 'Administrator' : e($user['kelas'] ?: 'Siswa') ?></small>
        </span>
      </div>
      <form method="post" action="<?= url('logout.php') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="nav-link logout"><?= icon('logout') ?><span>Logout</span></button>
      </form>
    </div>
  </aside>

  <div class="scrim" id="scrim"></div>

  <main class="main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn" type="button" aria-label="Buka menu"><?= icon('menu', 22) ?></button>
      <div class="page-head">
        <h1><?= e($title) ?></h1>
        <?php if ($subtitle !== ''): ?><p><?= e($subtitle) ?></p><?php endif; ?>
      </div>
      <div class="today"><?= e(tgl(date('Y-m-d'))) ?></div>
    </header>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="content">
<?php
}

function layout_end(): void
{
    ?>
    </div>
    <footer class="foot">© <?= date('Y') ?> ZENIN · Aplikasi Pengaduan Sarana Sekolah</footer>
  </main>
</div>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
<?php
}

/** Kartu ringkasan status */
function stat_cards(array $c): void
{
    $items = [
        ['Total Pengaduan', $c['Total'], 'inbox', 'is-total'],
        ['Menunggu', $c['Menunggu'], 'clock', 'is-wait'],
        ['Diproses', $c['Diproses'], 'wrench', 'is-process'],
        ['Selesai', $c['Selesai'], 'check', 'is-done'],
    ];
    echo '<section class="stats">';
    foreach ($items as [$label, $n, $ico, $cls]) {
        echo '<div class="stat ' . $cls . '"><span class="stat-ico">' . icon($ico, 20) . '</span>'
           . '<div><b>' . (int) $n . '</b><span>' . e($label) . '</span></div></div>';
    }
    echo '</section>';
}
