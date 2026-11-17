<?php
require_once __DIR__ . '/../includes/auth.php';
require_access('logs');
$pageTitle = 'Log Aktivitas'; $activeMenu = 'logs'; $breadcrumb = ['Administrasi', 'Log Aktivitas'];
$perPage = 25;
$page = max(1, (int)(isset($_GET['p']) ? $_GET['p'] : 1));
$total = (int)db()->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$st = db()->prepare('SELECT l.*, u.username FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT ? OFFSET ?');
$st->bindValue(1, $perPage, PDO::PARAM_INT); $st->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
$st->execute(); $rows = $st->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card-x">
    <div class="card-x-head">Aktivitas Pengguna <span class="small text-muted"><?= $total ?> entri</span></div>
    <div class="table-responsive"><table class="table table-x table-sm align-middle">
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Entitas</th><th>Detail</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td class="text-nowrap"><?= e($r['created_at']) ?></td><td><?= e($r['username'] ?: '-') ?></td><td><code><?= e($r['action']) ?></code></td>
            <td><?= e(trim($r['entity'] . ' ' . $r['entity_id'])) ?></td><td><?= e($r['details']) ?></td><td><?= e($r['ip_address']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <div class="card-x-body d-flex justify-content-between align-items-center">
        <span class="small text-muted">Halaman <?= $page ?> / <?= $pages ?></span>
        <div class="btn-group btn-group-sm">
            <a class="btn btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="?p=<?= $page - 1 ?>">&laquo; Sebelumnya</a>
            <a class="btn btn-outline-secondary <?= $page >= $pages ? 'disabled' : '' ?>" href="?p=<?= $page + 1 ?>">Berikutnya &raquo;</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
