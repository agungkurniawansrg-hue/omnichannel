<?php
// Autentikasi, sesi, CSRF, dan RBAC.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('autotranz_sid');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Hak akses menu per role. 'all' = seluruh menu.
function role_permissions() {
    return [
        'administrator'     => ['all'],
        'managing-director' => ['all'],
        'sales-marketing'   => ['bookings', 'inbox', 'channels', 'sales-marketing', 'smd'],
        'finance'           => ['bookings', 'finance'],
        'operasional'       => ['bookings', 'operasional', 'opd'],
        'human-resource'    => ['human-resource'],
    ];
}
// Menu yang hanya boleh dibuka Administrator.
function admin_only_menus() { return ['users', 'logs']; }

// Siapa yang boleh MENGUBAH data pipeline. Managing Director hanya memantau (read-only).
function can_edit($menu) {
    $u = current_user();
    if (!$u) return false;
    $map = ['smd' => ['administrator', 'sales-marketing'], 'opd' => ['administrator', 'operasional']];
    return isset($map[$menu]) && in_array($u['role'], $map[$menu], true);
}

function current_user() { return isset($_SESSION['user']) ? $_SESSION['user'] : null; }

function can($menu) {
    $u = current_user();
    if (!$u) return false;
    if (in_array($menu, admin_only_menus(), true)) return $u['role'] === 'administrator';
    $p = role_permissions();
    $perm = isset($p[$u['role']]) ? $p[$u['role']] : [];
    return in_array('all', $perm, true) || in_array($menu, $perm, true);
}

function home_for_role($role) {
    $map = ['sales-marketing' => 'sales-marketing/dashboard.php', 'finance' => 'finance/dashboard.php',
            'operasional' => 'operasional/dashboard.php', 'human-resource' => 'human-resource/dashboard.php'];
    return isset($map[$role]) ? $map[$role] : 'index.php';
}

function require_login() {
    if (!current_user()) { header('Location: ' . url('login.php')); exit; }
    // Validasi ulang ke DB: akun nonaktif / role berubah langsung berlaku.
    $st = db()->prepare('SELECT u.is_active, r.slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $row = $st->fetch();
    if (!$row || !$row['is_active']) { logout_user(); header('Location: ' . url('login.php')); exit; }
    $_SESSION['user']['role'] = $row['slug'];
}

function require_access($menu) {
    require_login();
    if (can($menu)) return;
    $home = home_for_role(current_user()['role']);
    if ($menu === 'dashboard' && $home !== 'index.php') { header('Location: ' . url($home)); exit; }
    http_response_code(403);
    $pageTitle = 'Akses Ditolak';
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>403</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="d-grid vh-100 place-items-center text-center bg-light"><div class="m-auto"><h1 class="display-4">403</h1>'
       . '<p>Role Anda tidak memiliki akses ke halaman ini.</p><a class="btn btn-primary" href="' . e(url($home)) . '">Kembali</a></div></body></html>';
    exit;
}

function login_user($username, $password, &$error) {
    $st = db()->prepare('SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ? LIMIT 1');
    $st->execute([$username]);
    $u = $st->fetch();
    $generic = 'Username atau password salah.';
    if (!$u) { password_verify($password, '$2y$10$usesomesillystringforsaltuiYvKQ3b0Yd1k2rQWd8pGZ5U5hS6'); $error = $generic; log_activity('login_failed', 'user', null, 'username tidak dikenal: ' . mb_substr($username, 0, 50), null); return false; }
    if ($u['locked_until'] && strtotime($u['locked_until']) > time()) { $error = 'Akun terkunci sementara karena terlalu banyak percobaan. Coba lagi nanti.'; return false; }
    if (!$u['is_active']) { $error = 'Akun dinonaktifkan. Hubungi administrator.'; return false; }
    if (!password_verify($password, $u['password_hash'])) {
        $fails = $u['failed_logins'] + 1;
        $lock = $fails >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
        db()->prepare('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?')->execute([$lock ? 0 : $fails, $lock, $u['id']]);
        log_activity('login_failed', 'user', $u['id'], 'password salah', $u['id']);
        $error = $generic; return false;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    db()->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'name' => $u['full_name'],
        'role' => $u['role_slug'], 'role_name' => $u['role_name'], 'initials' => strtoupper(mb_substr($u['full_name'], 0, 2))];
    log_activity('login', 'user', $u['id'], 'login berhasil');
    return true;
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// CSRF
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400); exit('Token keamanan (CSRF) tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function password_problem($pw) {
    if (strlen($pw) < 8) return 'Password minimal 8 karakter.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password harus mengandung huruf dan angka.';
    return null;
}

function flash($msg = null, $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = [$type, $msg]; return; }
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : null; unset($_SESSION['flash']); return $f;
}
