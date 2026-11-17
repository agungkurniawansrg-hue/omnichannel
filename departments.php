<?php
$pageTitle = 'Department Management'; $activeMenu = 'departments'; $breadcrumb = ['Department Management'];
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
<?php foreach (get_divisions() as $d): ?>
    <div class="col-12 col-md-6 col-xl-4"><div class="card-x dept-card" style="--accent:<?= e($d['color']) ?>">
        <div class="dept-icon"><i class="fa-solid <?= e($d['icon']) ?>"></i></div>
        <h5 class="mb-1"><?= e($d['name']) ?></h5>
        <p class="text-muted small"><?= e($d['desc']) ?></p>
        <div class="d-flex justify-content-between small mb-3"><span><i class="fa-solid fa-user-tie me-1"></i><?= e($d['head']) ?></span><span><i class="fa-solid fa-users me-1"></i><?= e($d['staff']) ?> staf</span></div>
        <a class="btn btn-sm btn-primary" href="<?= url($d['url']) ?>">Buka Dashboard</a>
    </div></div>
<?php endforeach; ?>
</div>
<p class="small text-muted mt-3">Nama kepala divisi dan jumlah staf adalah data dummy. Tambah/ubah departemen tersedia setelah database dibuat (Phase 2).</p>
<?php include __DIR__ . '/includes/footer.php'; ?>
