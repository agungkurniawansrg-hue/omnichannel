<header class="topbar">
    <button class="icon-btn d-lg-none" id="sidebarToggle" type="button" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <form class="topbar-search d-none d-md-flex<?= can('inbox') ? '' : ' invisible' ?>" action="<?= url('omnichannel/inbox.php') ?>" method="get">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" placeholder="Cari pelanggan atau percakapan..." value="<?= e(isset($_GET['q']) ? $_GET['q'] : '') ?>">
    </form>
    <div class="ms-auto d-flex align-items-center gap-2">
        <?php if (can('inbox')): ?>
        <div class="dropdown">
            <button class="icon-btn position-relative" data-bs-toggle="dropdown" type="button" aria-label="Notifikasi">
                <i class="fa-regular fa-bell"></i><span class="notif-dot">3</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
                <div class="px-3 py-2 fw-semibold border-bottom">Notifikasi <small class="text-muted">(dummy)</small></div>
                <a class="dropdown-item notif-item" href="<?= url('omnichannel/inbox.php?c=1') ?>"><i class="fa-brands fa-whatsapp text-success"></i><div><b>Andi Pratama</b><br><small>2 pesan baru via WhatsApp</small></div></a>
                <a class="dropdown-item notif-item" href="<?= url('omnichannel/inbox.php?c=6') ?>"><i class="fa-solid fa-comments" style="color:#6f42c1"></i><div><b>Dimas Kurniawan</b><br><small>Live chat baru dari website</small></div></a>
                <a class="dropdown-item notif-item" href="<?= url('bookings.php') ?>"><i class="fa-solid fa-calendar-check text-primary"></i><div><b>BKG-2611-0040</b><br><small>Menunggu pembayaran</small></div></a>
            </div>
        </div>
        <?php endif; ?>
        <div class="dropdown">
            <button class="profile-btn" data-bs-toggle="dropdown" type="button">
                <span class="avatar"><?= e($currentUser['initials']) ?></span>
                <span class="d-none d-sm-block text-start lh-sm"><b><?= e($currentUser['name']) ?></b><br><small><?= e($currentUser['role_name']) ?></small></span>
                <i class="fa-solid fa-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= url(home_for_role($currentUser['role'])) ?>"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                <li><a class="dropdown-item" href="<?= url('account.php') ?>"><i class="fa-solid fa-key me-2"></i>Pengaturan Akun</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?><button class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button></form></li>
            </ul>
        </div>
    </div>
</header>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
            <?php foreach ($breadcrumb as $b): ?>
                <li class="breadcrumb-item active"><?= e($b) ?></li>
            <?php endforeach; ?>
        </ol></nav>
    </div>
</div>
