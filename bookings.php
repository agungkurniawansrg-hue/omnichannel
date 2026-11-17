<?php
$pageTitle = 'Recent Bookings'; $activeMenu = 'bookings'; $breadcrumb = ['Recent Bookings'];
include __DIR__ . '/includes/header.php';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$all = get_bookings();
$statuses = array_values(array_unique(array_map(function ($b) { return $b['status']; }, $all)));
$rows = array_filter($all, function ($b) use ($status, $q) {
    if ($status !== '' && $b['status'] !== $status) return false;
    if ($q !== '' && stripos($b['code'] . ' ' . $b['customer'] . ' ' . $b['vehicle'], $q) === false) return false;
    return true;
});
?>
<div class="sim-banner"><i class="fa-solid fa-flask me-1"></i> Data booking dummy. Pembuatan booking tersedia di Phase 4-5.</div>
<div class="card-x">
    <div class="card-x-head">
        <form class="d-flex flex-wrap gap-2" method="get">
            <input class="form-control form-control-sm" style="width:220px" name="q" placeholder="Cari kode/pelanggan/kendaraan" value="<?= e($q) ?>">
            <select class="form-select form-select-sm w-auto" name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <?php foreach ($statuses as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
            <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-sm btn-outline-secondary" href="bookings.php">Reset</a><?php endif; ?>
        </form>
        <span class="small text-muted"><?= count($rows) ?> booking</span>
    </div>
    <div class="table-responsive"><table class="table table-x table-hover align-middle">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Kendaraan</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Driver</th><th>Channel</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b): ?>
            <tr><td><b><?= e($b['code']) ?></b></td><td><?= e($b['customer']) ?></td><td><?= e($b['vehicle']) ?></td><td><?= e($b['type']) ?></td>
            <td><?= e($b['start']) ?></td><td><?= e($b['end']) ?></td><td><?= $b['driver'] ? 'Ya' : 'Tidak' ?></td><td><?= e($b['channel']) ?></td>
            <td><?= rupiah($b['amount']) ?></td><td><span class="badge text-bg-<?= booking_badge($b['status']) ?>"><?= e($b['status']) ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4">Tidak ada booking yang cocok.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
