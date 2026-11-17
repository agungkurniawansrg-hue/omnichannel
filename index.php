<?php
$pageTitle = 'Dashboard Utama'; $activeMenu = 'dashboard'; $breadcrumb = ['Dashboard'];
$extraJs = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js'];
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/data.php';
$rev = get_revenue_series();
$fleet = get_fleet_status();
$inlineJs = 'new Chart(document.getElementById("revChart"),{type:"line",data:{labels:' . json_encode($rev['labels']) . ',datasets:[{label:"Pendapatan (juta Rp)",data:' . json_encode($rev['data']) . ',borderColor:"#2457d6",backgroundColor:"rgba(36,87,214,.12)",fill:true,tension:.35}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}}}});'
    . 'new Chart(document.getElementById("fleetChart"),{type:"doughnut",data:{labels:' . json_encode($fleet['labels']) . ',datasets:[{data:' . json_encode($fleet['data']) . ',backgroundColor:["#2457d6","#198754","#fd7e14"]}]},options:{maintainAspectRatio:false,cutout:"65%",plugins:{legend:{position:"bottom"}}}});';
include __DIR__ . '/includes/header.php';
?>
<div class="sim-banner"><i class="fa-solid fa-flask me-1"></i> Phase 1: seluruh angka di halaman ini adalah <b>data dummy</b> untuk keperluan tampilan, bukan transaksi nyata.</div>
<div class="row g-3 mb-3">
<?php foreach (get_company_stats() as $s): ?>
    <div class="col-12 col-sm-6 col-xl-3"><div class="card-x stat-card">
        <div class="stat-icon <?= e($s['color']) ?>"><i class="fa-solid <?= e($s['icon']) ?>"></i></div>
        <div><div class="stat-label"><?= e($s['label']) ?></div><div class="stat-value"><?= e($s['value']) ?></div><div class="stat-trend"><?= e($s['trend']) ?></div></div>
    </div></div>
<?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card-x h-100"><div class="card-x-head">Pertumbuhan Pendapatan (6 Bulan)</div><div class="card-x-body"><div class="chart-box"><canvas id="revChart"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card-x h-100"><div class="card-x-head">Status Armada</div><div class="card-x-body"><div class="chart-box"><canvas id="fleetChart"></canvas></div></div></div></div>
</div>
<div class="card-x mb-3">
    <div class="card-x-head">Department Management <a class="btn btn-sm btn-outline-primary" href="<?= url('departments.php') ?>">Lihat Semua</a></div>
    <div class="card-x-body"><div class="row g-3">
    <?php foreach (get_divisions() as $d): ?>
        <div class="col-12 col-md-6 col-xl"><a class="card-x dept-card" href="<?= url($d['url']) ?>" style="--accent:<?= e($d['color']) ?>">
            <div class="dept-icon"><i class="fa-solid <?= e($d['icon']) ?>"></i></div>
            <b><?= e($d['name']) ?></b><div class="small text-muted"><?= e($d['staff']) ?> staf</div>
        </a></div>
    <?php endforeach; ?>
    </div></div>
</div>
<div class="card-x">
    <div class="card-x-head">Recent Bookings <a class="btn btn-sm btn-outline-primary" href="<?= url('bookings.php') ?>">Lihat Semua</a></div>
    <div class="table-responsive"><table class="table table-x table-hover align-middle">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Kendaraan</th><th>Periode</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach (array_slice(get_bookings(), 0, 5) as $b): ?>
            <tr><td><b><?= e($b['code']) ?></b></td><td><?= e($b['customer']) ?></td><td><?= e($b['vehicle']) ?></td>
            <td><?= e($b['start']) ?> - <?= e($b['end']) ?></td><td><?= rupiah($b['amount']) ?></td>
            <td><span class="badge text-bg-<?= booking_badge($b['status']) ?>"><?= e($b['status']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
