<?php
$pageTitle = 'Channel Online & Offline'; $activeMenu = 'channels'; $breadcrumb = ['Customer Service', 'Channel'];
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/data.php';
include __DIR__ . '/../includes/header.php';
$channels = get_channels();
?>
<div class="sim-banner"><i class="fa-solid fa-circle-info me-1"></i> Tidak ada channel yang berstatus <b>Terhubung</b>. Label <b>Simulasi</b> = data dummy lokal; <b>Manual</b> = dicatat staf; <b>Belum Dikonfigurasi</b> = menunggu API &amp; kredensial (Phase 6). Pesan hari ini adalah angka dummy.</div>
<?php foreach (['online' => ['Channel Online', 'fa-globe'], 'offline' => ['Channel Offline', 'fa-building']] as $g => $meta): ?>
<h5 class="mt-3 mb-2"><i class="fa-solid <?= $meta[1] ?> me-2 text-primary"></i><?= $meta[0] ?></h5>
<div class="row g-3">
<?php foreach ($channels as $c): if ($c['group'] !== $g) continue; $l = integration_label($c['integration']); ?>
    <div class="col-12 col-md-6 col-xl-4"><div class="card-x channel-card">
        <span class="ch-ico" style="background:<?= e($c['color']) ?>;width:46px;height:46px;font-size:1.2rem"><i class="<?= e($c['icon']) ?>"></i></span>
        <div class="flex-grow-1">
            <b><?= e($c['name']) ?></b>
            <div class="small text-muted"><?= e($l[2]) ?></div>
            <div class="mt-1"><span class="badge text-bg-<?= e($l[1]) ?> <?= $l[1] === 'light' ? 'border text-dark' : '' ?>"><?= e($l[0]) ?></span>
            <span class="small text-muted ms-2"><?= (int)$c['today'] ?> pesan hari ini</span></div>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('omnichannel/inbox.php') ?>" title="Buka inbox">Inbox</a>
    </div></div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
