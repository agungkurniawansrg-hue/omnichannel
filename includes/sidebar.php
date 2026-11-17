<?php
function nav_item($key, $href, $icon, $label, $active, $color = null, $badge = null) {
    if (!can($key)) return;
    $cls = ($active === $key) ? ' active' : '';
    $style = $color ? ' style="--accent:' . e($color) . '"' : '';
    echo '<a class="nav-link-item' . $cls . '" href="' . e(url($href)) . '"' . $style . '>'
       . '<i class="fa-solid ' . e($icon) . '"></i><span>' . e($label) . '</span>'
       . ($badge ? '<em class="nav-badge">' . e($badge) . '</em>' : '') . '</a>';
}
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url('index.php') ?>">
        <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
        <span class="brand-text"><strong>Auto<b>TRANZ</b></strong><small>Omnichannel Management</small></span>
    </a>
    <nav class="sidebar-nav">
        <div class="nav-section">Utama</div>
        <?php nav_item('dashboard', 'index.php', 'fa-gauge-high', 'Dashboard Utama', $activeMenu); ?>
        <?php nav_item('departments', 'departments.php', 'fa-sitemap', 'Department Management', $activeMenu); ?>
        <?php nav_item('bookings', 'bookings.php', 'fa-calendar-check', 'Recent Bookings', $activeMenu); ?>

        <div class="nav-section">Customer Service</div>
        <?php nav_item('inbox', 'omnichannel/inbox.php', 'fa-inbox', 'Unified Inbox', $activeMenu, null, '3'); ?>
        <?php nav_item('channels', 'omnichannel/channels.php', 'fa-share-nodes', 'Channel Online & Offline', $activeMenu); ?>

        <?php if (can('smd') || can('opd')): ?><div class="nav-section">Pipeline</div><?php endif; ?>
        <?php nav_item('smd', 'sales-marketing/smd.php', 'fa-filter-circle-dollar', 'SMD Pipeline', $activeMenu, '#0d6efd'); ?>
        <?php nav_item('opd', 'operasional/opd.php', 'fa-screwdriver-wrench', 'OPD Pipeline', $activeMenu, '#fd7e14'); ?>

        <div class="nav-section">Divisi</div>
        <?php foreach (get_divisions() as $key => $d) nav_item($key, $d['url'], $d['icon'], $d['name'], $activeMenu, $d['color']); ?>
        <?php if (can('users')): ?>
        <div class="nav-section">Administrasi</div>
        <?php nav_item('users', 'admin/users.php', 'fa-user-gear', 'Pengguna & Role', $activeMenu); ?>
        <?php nav_item('logs', 'admin/logs.php', 'fa-clock-rotate-left', 'Log Aktivitas', $activeMenu); ?>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <span class="dot-sim"></span> Phase 2 &middot; Sebagian data dummy
    </div>
</aside>
