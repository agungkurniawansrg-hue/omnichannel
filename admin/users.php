<?php
require_once __DIR__ . '/../includes/auth.php';
require_access('users');
$me = current_user();
$roles = db()->query('SELECT id, slug, name FROM roles ORDER BY id')->fetchAll();
$roleIds = array_column($roles, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = isset($_POST['action']) ? $_POST['action'] : '';
    $uid = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    try {
        if ($act === 'create') {
            $username = trim($_POST['username']); $email = trim($_POST['email']); $name = trim($_POST['full_name']);
            $role = (int)$_POST['role_id']; $pw = $_POST['password'];
            if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) throw new RuntimeException('Username 3-50 karakter (huruf, angka, . _ -).');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
            if ($name === '' || mb_strlen($name) > 120) throw new RuntimeException('Nama wajib diisi (maks 120).');
            if (!in_array($role, $roleIds)) throw new RuntimeException('Role tidak valid.');
            if ($p = password_problem($pw)) throw new RuntimeException($p);
            db()->prepare('INSERT INTO users (role_id, username, email, full_name, password_hash) VALUES (?,?,?,?,?)')
                ->execute([$role, $username, $email, $name, password_hash($pw, PASSWORD_DEFAULT)]);
            log_activity('user_created', 'user', db()->lastInsertId(), $username);
            flash('Pengguna dibuat.');
        } elseif ($act === 'role') {
            $role = (int)$_POST['role_id'];
            if ($uid === $me['id']) throw new RuntimeException('Tidak bisa mengubah role akun sendiri.');
            if (!in_array($role, $roleIds)) throw new RuntimeException('Role tidak valid.');
            db()->prepare('UPDATE users SET role_id = ? WHERE id = ?')->execute([$role, $uid]);
            log_activity('user_role_changed', 'user', $uid, 'role_id=' . $role);
            flash('Role diperbarui.');
        } elseif ($act === 'toggle') {
            if ($uid === $me['id']) throw new RuntimeException('Tidak bisa menonaktifkan akun sendiri.');
            db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$uid]);
            log_activity('user_toggled', 'user', $uid);
            flash('Status akun diperbarui.');
        } elseif ($act === 'reset') {
            $pw = $_POST['password'];
            if ($p = password_problem($pw)) throw new RuntimeException($p);
            db()->prepare('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $uid]);
            log_activity('password_reset', 'user', $uid);
            flash('Password direset.');
        }
    } catch (RuntimeException $ex) { flash($ex->getMessage(), 'danger'); }
    catch (PDOException $ex) { flash($ex->getCode() == 23000 ? 'Username atau email sudah dipakai.' : 'Kesalahan database.', 'danger'); }
    header('Location: ' . url('admin/users.php')); exit;
}

$users = db()->query('SELECT u.id, u.username, u.email, u.full_name, u.role_id, u.is_active, u.last_login_at FROM users u ORDER BY u.id')->fetchAll();
$pageTitle = 'Pengguna & Role'; $activeMenu = 'users'; $breadcrumb = ['Administrasi', 'Pengguna & Role'];
include __DIR__ . '/../includes/header.php';
$f = flash();
?>
<?php if ($f): ?><div class="alert alert-<?= e($f[0]) ?>"><?= e($f[1]) ?></div><?php endif; ?>
<div class="card-x mb-3">
    <div class="card-x-head">Daftar Pengguna <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mCreate"><i class="fa-solid fa-plus me-1"></i>Tambah</button></div>
    <div class="table-responsive"><table class="table table-x align-middle">
        <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $self = $u['id'] == $me['id']; ?>
            <tr><td><?= e($u['full_name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['email']) ?></td>
            <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="role_id" class="form-select form-select-sm" <?= $self ? 'disabled' : '' ?> onchange="this.form.submit()">
                <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= $r['id'] == $u['role_id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
                </select></form></td>
            <td><span class="badge text-bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
            <td class="small"><?= e($u['last_login_at'] ?: '-') ?></td>
            <td class="text-nowrap">
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button class="btn btn-sm btn-outline-secondary" <?= $self ? 'disabled' : '' ?>><?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mReset" data-id="<?= (int)$u['id'] ?>" data-name="<?= e($u['username']) ?>">Reset PW</button>
            </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<div class="card-x"><div class="card-x-head">Matriks Hak Akses Role</div><div class="card-x-body small">
    <?php $perm = role_permissions(); foreach ($roles as $r): ?>
        <div class="mb-1"><b><?= e($r['name']) ?></b>: <?= e(implode(', ', $perm[$r['slug']])) ?></div>
    <?php endforeach; ?>
    <div class="text-muted mt-2">Hak akses didefinisikan di <code>includes/auth.php</code> (<code>role_permissions()</code>).</div>
</div></div>

<div class="modal fade" id="mCreate" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="modal-header"><h5 class="modal-title">Tambah Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Nama lengkap</label><input class="form-control" name="full_name" maxlength="120" required></div>
        <div class="mb-2"><label class="form-label small">Username</label><input class="form-control" name="username" maxlength="50" required></div>
        <div class="mb-2"><label class="form-label small">Email</label><input class="form-control" type="email" name="email" required></div>
        <div class="mb-2"><label class="form-label small">Role</label><select class="form-select" name="role_id"><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2"><label class="form-label small">Password awal (min. 8, huruf &amp; angka)</label><input class="form-control" type="password" name="password" required></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
</form></div></div>
<div class="modal fade" id="mReset" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" id="resetId">
    <div class="modal-header"><h5 class="modal-title">Reset Password: <span id="resetName"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label small">Password baru</label><input class="form-control" type="password" name="password" required></div>
    <div class="modal-footer"><button class="btn btn-primary">Reset</button></div>
</form></div></div>
<?php
$inlineJs = 'document.getElementById("mReset").addEventListener("show.bs.modal",function(e){var b=e.relatedTarget;document.getElementById("resetId").value=b.dataset.id;document.getElementById("resetName").textContent=b.dataset.name;});';
include __DIR__ . '/../includes/footer.php';
