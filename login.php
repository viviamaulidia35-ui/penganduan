<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($u = current_user()) {
    redirect(home_for($u['role']));
}

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($login === '' || $password === '') {
        $error = 'Username/email dan password wajib diisi.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([$login, $login]);
        $row = $st->fetch();

        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => (int) $row['id'],
                'nama'     => $row['nama'],
                'username' => $row['username'],
                'role'     => $row['role'],
                'kelas'    => $row['kelas'],
            ];
            redirect(home_for($row['role']));
        }
        $error = 'Username/email atau password salah.';
    }
}

$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · ZENIN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="auth">
<div class="auth-wrap">
  <section class="auth-hero">
    <div class="hero-inner">
      <span class="brand-mark lg">Z</span>
      <h1>ZENIN</h1>
      <p class="hero-tag">Suara siswa untuk sekolah yang lebih nyaman.</p>
      <p class="hero-desc">Laporkan kerusakan sarana, pantau perkembangan penanganan, dan terima umpan balik langsung dari pihak sekolah.</p>
      <ul class="hero-points">
        <li><span></span>Pengaduan tercatat rapi dan transparan</li>
        <li><span></span>Status penanganan dapat dipantau kapan saja</li>
        <li><span></span>Umpan balik langsung dari admin sekolah</li>
      </ul>
    </div>
    <div class="hero-orb o1"></div><div class="hero-orb o2"></div>
  </section>

  <section class="auth-panel">
    <form class="auth-card" method="post" action="" novalidate>
      <p class="eyebrow">Selamat datang</p>
      <h2>Masuk ke akun Anda</h2>
      <p class="muted">Gunakan akun siswa atau admin yang telah terdaftar.</p>

      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($flash && !$error): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>

      <?= csrf_field() ?>
      <label class="field">
        <span>Username atau Email</span>
        <input type="text" name="login" value="<?= e($login) ?>" placeholder="contoh: budi" autocomplete="username" required autofocus>
      </label>
      <label class="field">
        <span>Password</span>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </label>
      <button class="btn btn-primary btn-block" type="submit">Masuk <?= icon('arrow', 16) ?></button>

      <div class="demo">
        <b>Akun contoh</b>
        <span>Admin · <code>admin</code> / <code>admin123</code></span>
        <span>Siswa · <code>budi</code> / <code>siswa123</code></span>
      </div>
    </form>
  </section>
</div>
</body>
</html>
