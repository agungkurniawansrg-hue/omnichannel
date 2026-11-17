<?php
$pageTitle = 'Unified Omnichannel Inbox'; $activeMenu = 'inbox'; $breadcrumb = ['Customer Service', 'Unified Inbox'];
$bodyClass = 'page-inbox';
$extraJs = [];
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/data.php';
$payload = [
    'conversations' => get_conversations(),
    'channels'      => get_channels(),
    'agents'        => get_agents(),
    'q'             => isset($_GET['q']) ? trim($_GET['q']) : '',
    'selected'      => isset($_GET['c']) ? (int)$_GET['c'] : 0,
];
$preJs = 'window.INBOX_DATA=' . json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';';
$extraJs = [url('assets/js/inbox.js')];
include __DIR__ . '/../includes/header.php';
?>
<div class="sim-banner mt-2"><i class="fa-solid fa-flask me-1"></i> <b>Mode simulasi.</b> Percakapan adalah data dummy; balasan, status, dan penugasan hanya tersimpan di browser dan hilang saat halaman dimuat ulang. Belum ada channel yang terhubung ke API resmi.</div>
<div class="inbox" id="inbox">
    <section class="inbox-col list-col">
        <div class="inbox-tools">
            <input class="form-control form-control-sm" id="inboxSearch" type="search" placeholder="Cari pelanggan / pesan...">
            <div class="chip-row" id="channelChips"></div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted" id="convCount"></small>
                <select class="form-select form-select-sm w-auto" id="statusFilter">
                    <option value="all">Semua status</option><option>New</option><option>Open</option><option>Pending</option><option>Resolved</option>
                </select>
            </div>
        </div>
        <div class="conv-list" id="convList"></div>
    </section>
    <section class="inbox-col chat-col" id="chatCol"></section>
    <section class="inbox-col cust-col" id="custCol"></section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
