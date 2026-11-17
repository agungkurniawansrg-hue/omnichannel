<?php
// Template dashboard divisi (Phase 1, data dummy). Set $divKey sebelum include.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/data.php';

$divs = get_divisions();
$d = $divs[$divKey];
$detail = [
    'managing-director' => [
        'stats' => [['Pendapatan Bulan Ini', rupiah(485750000)], ['Transaksi Rental', '218'], ['Armada Disewa', '71 / 96'], ['Produktivitas Karyawan', '92%']],
        'features' => ['Executive Dashboard', 'Monitoring seluruh divisi', 'Performa Sales & Marketing', 'Laporan bulanan & tahunan', 'Grafik pertumbuhan bisnis'],
    ],
    'sales-marketing' => [
        'stats' => [['Leads Aktif', '64'], ['Quotation Terkirim', '27'], ['Konversi Leads', '31%'], ['Booking Bulan Ini', '218']],
        'features' => ['Customer & CRM', 'Leads Management', 'Quotation', 'Booking Management', 'Follow-up pelanggan', 'Laporan penjualan'],
    ],
    'finance' => [
        'stats' => [['Pemasukan', rupiah(485750000)], ['Pengeluaran', rupiah(298400000)], ['Piutang', rupiah(63200000)], ['Invoice Belum Lunas', '14']],
        'features' => ['Invoice & Payment', 'Income / Expense', 'Piutang pelanggan', 'Laba rugi', 'Export PDF & Excel'],
    ],
    'operasional' => [
        'stats' => [['Total Kendaraan', '96'], ['Sedang Disewa', '71'], ['Dalam Servis', '5'], ['Pengemudi Bertugas', '38']],
        'features' => ['Fleet & Vehicle Management', 'Jadwal rental & pengembalian', 'Driver Management', 'Perawatan & servis', 'Dokumen kendaraan'],
    ],
    'human-resource' => [
        'stats' => [['Total Karyawan', '49'], ['Hadir Hari Ini', '44'], ['Cuti / Izin', '3'], ['Pengajuan Cuti Menunggu', '2']],
        'features' => ['Employee Management', 'Absensi & jadwal kerja', 'Cuti & izin', 'Data pengemudi', 'Evaluasi kinerja'],
    ],
][$divKey];

$pageTitle = $d['name']; $activeMenu = $divKey; $breadcrumb = ['Divisi', $d['name']];
include __DIR__ . '/header.php';
?>
<div class="sim-banner"><i class="fa-solid fa-flask me-1"></i> Dashboard divisi Phase 1: angka adalah <b>data dummy</b>. Fitur lengkap dibangun di Phase 4-5.</div>
<?php if ($divKey === 'sales-marketing' && can('smd')): ?><a class="btn btn-primary btn-sm mb-3" href="<?= url('sales-marketing/smd.php') ?>"><i class="fa-solid fa-filter-circle-dollar me-1"></i>Buka SMD Pipeline</a><?php endif; ?>
<?php if ($divKey === 'operasional' && can('opd')): ?><a class="btn btn-primary btn-sm mb-3" href="<?= url('operasional/opd.php') ?>"><i class="fa-solid fa-screwdriver-wrench me-1"></i>Buka OPD Pipeline</a><?php endif; ?>
<div class="row g-3 mb-3">
<?php foreach ($detail['stats'] as $s): ?>
    <div class="col-6 col-xl-3"><div class="card-x stat-card" style="border-top:4px solid <?= e($d['color']) ?>">
        <div><div class="stat-label"><?= e($s[0]) ?></div><div class="stat-value"><?= e($s[1]) ?></div></div>
    </div></div>
<?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-5"><div class="card-x h-100"><div class="card-x-head">Tentang Divisi</div><div class="card-x-body">
        <div class="dept-icon" style="--accent:<?= e($d['color']) ?>"><i class="fa-solid <?= e($d['icon']) ?>"></i></div>
        <p><?= e($d['desc']) ?>.</p>
        <p class="mb-0 small"><i class="fa-solid fa-user-tie me-2"></i>Kepala: <?= e($d['head']) ?> &middot; <?= e($d['staff']) ?> staf</p>
    </div></div></div>
    <div class="col-lg-7"><div class="card-x h-100"><div class="card-x-head">Modul yang Direncanakan</div><div class="card-x-body">
        <ul class="list-unstyled mb-0">
        <?php foreach ($detail['features'] as $f): ?>
            <li class="py-1"><i class="fa-regular fa-clock me-2 text-muted"></i><?= e($f) ?> <span class="badge text-bg-light border ms-1">Belum dibangun</span></li>
        <?php endforeach; ?>
        </ul>
    </div></div></div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
