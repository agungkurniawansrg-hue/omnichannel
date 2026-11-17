<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { header('Location: ' . url(home_for_role(current_user()['role']))); exit; }
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (login_user($username, $password, $error)) {
        header('Location: ' . url(home_for_role(current_user()['role']))); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#0b1f4b,#2457d6);min-height:100vh" class="d-flex align-items-center justify-content-center p-3">
<div class="card-x p-4" style="width:100%;max-width:400px">
    <div class="text-center mb-3">
        <span class="brand-logo mx-auto mb-2" style="background:#2457d6;color:#fff"><i class="fa-solid fa-car-side"></i></span>
        <h4 class="mb-0" style="color:#0b1f4b">Auto<b style="color:#2457d6">TRANZ</b></h4>
        <small class="text-muted">Omnichannel Management System</small>
    </div>
    <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label small">Username</label>
            <input class="form-control" name="username" value="<?= e($username) ?>" required autofocus></div>
        <div class="mb-3"><label class="form-label small">Password</label>
            <input class="form-control" type="password" name="password" required></div>
        <button class="btn btn-primary w-100">Masuk</button>
    </form>
</div>
</body></html>
