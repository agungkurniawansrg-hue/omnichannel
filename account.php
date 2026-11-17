<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$me = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = isset($_POST['old']) ? $_POST['old'] : '';
    $new = isset($_POST['new']) ? $_POST['new'] : '';
    $conf = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    $row = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $row->execute([$me['id']]);
    $hash = $row->fetchColumn();
    if (!password_verify($old, $hash)) flash('Password lama salah.', 'danger');
    elseif ($new !== $conf) flash('Konfirmasi password tidak sama.', 'danger');
    elseif ($p = password_problem($new)) flash($p, 'danger');
    else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        log_activity('password_changed', 'user', $me['id']);
        session_regenerate_id(true);
        flash('Password berhasil diubah.');
    }
    header('Location: ' . url('account.php')); exit;
}
$pageTitle = 'Pengaturan Akun'; $breadcrumb = ['Pengaturan Akun'];
include __DIR__ . '/includes/header.php';
$f = flash();
?>
<?php if ($f): ?><div class="alert alert-<?= e($f[0]) ?>"><?= e($f[1]) ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-lg-5"><div class="card-x"><div class="card-x-head">Profil</div><div class="card-x-body">
        <dl class="row mb-0"><dt class="col-4">Nama</dt><dd class="col-8"><?= e($me['name']) ?></dd>
        <dt class="col-4">Username</dt><dd class="col-8"><?= e($me['username']) ?></dd>
        <dt class="col-4">Role</dt><dd class="col-8"><?= e($me['role_name']) ?></dd></dl>
    </div></div></div>
    <div class="col-lg-5"><div class="card-x"><div class="card-x-head">Ganti Password</div><div class="card-x-body">
        <form method="post" autocomplete="off"><?= csrf_field() ?>
            <div class="mb-2"><label class="form-label small">Password lama</label><input class="form-control" type="password" name="old" required></div>
            <div class="mb-2"><label class="form-label small">Password baru (min. 8, huruf &amp; angka)</label><input class="form-control" type="password" name="new" required></div>
            <div class="mb-3"><label class="form-label small">Konfirmasi password baru</label><input class="form-control" type="password" name="confirm" required></div>
            <button class="btn btn-primary">Simpan</button>
        </form>
    </div></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
