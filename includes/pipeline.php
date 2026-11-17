<?php
// Helper bersama untuk SMD Pipeline dan OPD Pipeline.
require_once __DIR__ . '/auth.php';

function pl_stage_defs($pipe) {
    if ($pipe === 'smd') return [
        'leads_masuk' => ['Leads Masuk', 'Prospek B2B & kontak baru', 'fa-user-plus', '#6c757d'],
        'follow_up'   => ['Follow Up', 'Hubungi kembali & kirim detail', 'fa-phone-volume', '#0d6efd'],
        'penawaran'   => ['Penawaran', 'Brosur, info harga, draft penawaran', 'fa-file-invoice-dollar', '#fd7e14'],
        'deal'        => ['Deal', 'Penerbitan SPK & penutupan penjualan', 'fa-handshake', '#198754'],
    ];
    return [
        'gate_awal'     => ['Gate Awal', 'Penerimaan unit & info servis masuk', 'fa-right-to-bracket', '#6c757d'],
        'estimasi'      => ['Estimasi', 'Sparepart & item pekerjaan', 'fa-calculator', '#0d6efd'],
        'proses_servis' => ['Proses Servis', 'Perbaikan umum maupun overhaul', 'fa-screwdriver-wrench', '#fd7e14'],
        'selesai'       => ['Selesai', 'Quality Control & siap diambil', 'fa-circle-check', '#198754'],
    ];
}

// Kandidat PIC: pengguna aktif dengan role pipeline tersebut (+ administrator).
function pl_pic_users($pipe) {
    $role = $pipe === 'smd' ? 'sales-marketing' : 'operasional';
    $st = db()->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.is_active = 1 AND r.slug IN (?, 'administrator') ORDER BY u.full_name");
    $st->execute([$role]);
    return $st->fetchAll();
}

function pl_valid_pic($pipe, $uid) {
    foreach (pl_pic_users($pipe) as $u) if ((int)$u['id'] === (int)$uid) return true;
    return false;
}

function pl_event($pipe, $cardId, $type, $from, $to, $note) {
    $st = db()->prepare('INSERT INTO pipeline_events (pipeline, card_id, event_type, from_stage, to_stage, note, user_id) VALUES (?,?,?,?,?,?,?)');
    $st->execute([$pipe, $cardId, $type, $from, $to, $note !== null ? mb_substr($note, 0, 500) : null, current_user()['id']]);
}

function pl_events($pipe, array $ids) {
    $out = [];
    if (!$ids) return $out;
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT e.*, u.full_name FROM pipeline_events e LEFT JOIN users u ON u.id = e.user_id WHERE e.pipeline = ? AND e.card_id IN ($in) ORDER BY e.id DESC");
    $st->execute(array_merge([$pipe], $ids));
    foreach ($st->fetchAll() as $r) $out[$r['card_id']][] = $r;
    return $out;
}

// Hanya PIC kartu atau Administrator yang boleh mengubah/menggeser kartu.
function pl_owns($picId) {
    $u = current_user();
    return $u['role'] === 'administrator' || (int)$u['id'] === (int)$picId;
}

function pl_redirect($path, $msg, $type = 'success') {
    flash($msg, $type);
    header('Location: ' . url($path));
    exit;
}

function pl_str($key, $max) {
    $v = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    return mb_substr($v, 0, $max);
}
function pl_money($key) {
    $v = isset($_POST[$key]) ? str_replace(['.', ','], ['', '.'], trim($_POST[$key])) : '0';
    return is_numeric($v) && $v >= 0 ? round((float)$v, 2) : 0;
}
function pl_date_or_null($key) {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}
function pl_age($ts) {
    $d = time() - strtotime($ts);
    if ($d < 3600) return max(1, (int)($d / 60)) . ' mnt';
    if ($d < 86400) return (int)($d / 3600) . ' jam';
    return (int)($d / 86400) . ' hari';
}
