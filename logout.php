<?php
require_once __DIR__ . '/includes/auth.php';
// Logout hanya lewat POST + CSRF agar tidak bisa dipicu dari link/gambar pihak lain.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user()) {
    csrf_check();
    log_activity('logout', 'user', current_user()['id']);
    logout_user();
}
header('Location: ' . url('login.php'));
exit;
