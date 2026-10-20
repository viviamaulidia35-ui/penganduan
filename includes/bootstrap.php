<?php
/**
 * Bootstrap ZENIN: session, helper, autentikasi, CSRF.
 */
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

/* ---------- BASE URL otomatis (folder proyek di htdocs) ---------- */
(function () {
    $root = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    $doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $base = ($doc !== '' && stripos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
    define('BASE_URL', rtrim($base, '/'));
})();

const STATUS_LIST   = ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'];
const UPLOAD_MAX    = 2 * 1024 * 1024; // 2 MB
const PER_PAGE      = 10;

/* ---------- Helper umum ---------- */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(?string $type = null, ?string $message = null)
{
    if ($type !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function bulan_id(int $n): string
{
    $b = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
          'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $b[$n] ?? '';
}

function tgl($date, bool $withTime = false): string
{
    if (!$date) {
        return '-';
    }
    $t = strtotime((string) $date);
    $s = date('j', $t) . ' ' . bulan_id((int) date('n', $t)) . ' ' . date('Y', $t);
    return $withTime ? $s . ', ' . date('H:i', $t) : $s;
}

function status_class(string $status): string
{
    return [
        'Menunggu' => 'is-wait',
        'Diproses' => 'is-process',
        'Selesai'  => 'is-done',
        'Ditolak'  => 'is-reject',
    ][$status] ?? 'is-wait';
}

function badge(string $status): string
{
    return '<span class="badge ' . status_class($status) . '"><i></i>' . e($status) . '</span>';
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* ---------- Autentikasi & hak akses ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    if ($role !== null && $user['role'] !== $role) {
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'siswa/dashboard.php');
    }
    return $user;
}

function home_for(string $role): string
{
    return $role === 'admin' ? 'admin/dashboard.php' : 'siswa/dashboard.php';
}

/* ---------- Ikon SVG (garis tipis, elegan) ---------- */
function icon(string $name, int $size = 18): string
{
    $paths = [
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'inbox'   => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
        'wrench'  => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-9.4 9.4a2.1 2.1 0 0 1-3-3z"/><path d="M14 6l4-4 4 4-4 4"/>',
        'check'   => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>',
        'x'       => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'eye'     => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back'    => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'pin'     => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'image'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'filter'  => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $p . '</svg>';
}

/* ---------- Data bantu ---------- */
function kategori_list(): array
{
    return db()->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();
}

function count_by_status(?int $userId = null): array
{
    $sql = 'SELECT status, COUNT(*) AS n FROM pengaduan' . ($userId ? ' WHERE user_id = ?' : '') . ' GROUP BY status';
    $st  = db()->prepare($sql);
    $st->execute($userId ? [$userId] : []);
    $out = array_fill_keys(STATUS_LIST, 0);
    foreach ($st->fetchAll() as $r) {
        $out[$r['status']] = (int) $r['n'];
    }
    $out['Total'] = array_sum($out);
    return $out;
}
