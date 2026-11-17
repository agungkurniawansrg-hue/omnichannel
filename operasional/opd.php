<?php
// OPD Pipeline: Gate Awal -> Estimasi -> Proses Servis -> Selesai. Setiap kartu wajib punya PIC.
require_once __DIR__ . '/../includes/pipeline.php';
require_access('opd');
$me = current_user();
$editor = can_edit('opd');
$stages = pl_stage_defs('opd');
$keys = array_keys($stages);
$self = 'operasional/opd.php';
$types = ['servis_umum' => 'Servis Umum', 'overhaul' => 'Overhaul'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$editor) { http_response_code(403); exit('Role Anda hanya dapat melihat pipeline ini.'); }
    $act = isset($_POST['action']) ? $_POST['action'] : '';
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $pdo = db();
    try {
        if ($act === 'create') {
            $name = pl_str('customer_name', 120); $plate = strtoupper(preg_replace('/\s+/', ' ', pl_str('plate_no', 15)));
            $complaint = pl_str('complaint', 2000); $pic = (int)$_POST['pic_user_id'];
            $type = isset($types[$_POST['service_type']]) ? $_POST['service_type'] : 'servis_umum';
            if ($name === '' || $plate === '' || $complaint === '') throw new RuntimeException('Nama pelanggan, plat nomor, dan keluhan/info servis wajib diisi.');
            if (!pl_valid_pic('opd', $pic)) throw new RuntimeException('PIC wajib dipilih dan harus valid.');
            $dup = $pdo->prepare("SELECT job_no FROM opd_jobs WHERE plate_no = ? AND status = 'active' LIMIT 1");
            $dup->execute([$plate]);
            if ($d = $dup->fetchColumn()) throw new RuntimeException("Unit $plate masih punya pekerjaan aktif ($d).");
            $vehId = null;
            $v = $pdo->prepare('SELECT id FROM vehicles WHERE plate_no = ?'); $v->execute([$plate]); if ($vid = $v->fetchColumn()) $vehId = $vid;
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO opd_jobs (job_no, customer_name, phone, vehicle_id, plate_no, vehicle_model, service_type, complaint, pic_user_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute(['TMP' . bin2hex(random_bytes(5)), $name, pl_str('phone', 30) ?: null, $vehId, $plate, pl_str('vehicle_model', 80) ?: null, $type, $complaint, $pic, $me['id']]);
            $newId = (int)$pdo->lastInsertId();
            $no = 'OPD-' . date('ym') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE opd_jobs SET job_no = ? WHERE id = ?')->execute([$no, $newId]);
            pl_event('opd', $newId, 'created', null, 'gate_awal', 'Unit diterima di gate');
            $pdo->commit();
            log_activity('opd_created', 'opd_job', $newId, $no . ' ' . $plate);
            pl_redirect($self, "Unit diterima: $no.");
        }

        $st = $pdo->prepare('SELECT * FROM opd_jobs WHERE id = ?'); $st->execute([$id]); $job = $st->fetch();
        if (!$job) throw new RuntimeException('Kartu tidak ditemukan.');
        if (!pl_owns($job['pic_user_id'])) throw new RuntimeException('Hanya PIC kartu ini (atau Administrator) yang dapat mengubahnya.');
        if ($job['status'] !== 'active') throw new RuntimeException('Pekerjaan sudah selesai dan unit diambil.');
        $itemCount = (int)$pdo->query('SELECT COUNT(*) FROM opd_estimate_items WHERE job_id = ' . (int)$id)->fetchColumn();

        if ($act === 'save') {
            $name = pl_str('customer_name', 120); $plate = strtoupper(preg_replace('/\s+/', ' ', pl_str('plate_no', 15)));
            $complaint = pl_str('complaint', 2000); $pic = (int)$_POST['pic_user_id'];
            $type = isset($types[$_POST['service_type']]) ? $_POST['service_type'] : 'servis_umum';
            if ($name === '' || $plate === '' || $complaint === '') throw new RuntimeException('Nama pelanggan, plat nomor, dan keluhan wajib diisi.');
            if (!pl_valid_pic('opd', $pic)) throw new RuntimeException('PIC tidak valid.');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE opd_jobs SET customer_name=?, phone=?, plate_no=?, vehicle_model=?, service_type=?, complaint=?, pic_user_id=? WHERE id=?')
                ->execute([$name, pl_str('phone', 30) ?: null, $plate, pl_str('vehicle_model', 80) ?: null, $type, $complaint, $pic, $id]);
            if ($pic !== (int)$job['pic_user_id']) pl_event('opd', $id, 'assign', null, null, 'PIC dialihkan');
            pl_event('opd', $id, 'update', null, null, 'Data pekerjaan diperbarui');
            $pdo->commit();
            pl_redirect($self, 'Kartu diperbarui.');
        } elseif ($act === 'note') {
            $n = pl_str('note', 500);
            if ($n === '') throw new RuntimeException('Catatan kosong.');
            pl_event('opd', $id, 'note', $job['stage'], null, $n);
            pl_redirect($self, 'Catatan dicatat.');
        } elseif ($act === 'add_item') {
            if (!in_array($job['stage'], ['estimasi', 'proses_servis'], true)) throw new RuntimeException('Item estimasi hanya dapat diubah pada tahap Estimasi atau Proses Servis.');
            $type = $_POST['item_type'] === 'sparepart' ? 'sparepart' : 'pekerjaan';
            $desc = pl_str('description', 200); $qty = pl_money('qty'); $price = pl_money('unit_price');
            if ($desc === '' || $qty <= 0) throw new RuntimeException('Deskripsi dan jumlah (>0) wajib diisi.');
            $pdo->prepare('INSERT INTO opd_estimate_items (job_id, item_type, description, qty, unit_price) VALUES (?,?,?,?,?)')->execute([$id, $type, $desc, $qty, $price]);
            pl_event('opd', $id, 'update', null, null, 'Item estimasi: ' . $desc);
            pl_redirect($self, 'Item estimasi ditambahkan.');
        } elseif ($act === 'del_item') {
            if (!in_array($job['stage'], ['estimasi', 'proses_servis'], true)) throw new RuntimeException('Item estimasi hanya dapat diubah pada tahap Estimasi atau Proses Servis.');
            $pdo->prepare('DELETE FROM opd_estimate_items WHERE id = ? AND job_id = ?')->execute([(int)$_POST['item_id'], $id]);
            pl_redirect($self, 'Item dihapus.');
        } elseif ($act === 'move') {
            $dir = $_POST['dir'] === 'next' ? 1 : -1;
            $i = array_search($job['stage'], $keys, true) + $dir;
            if ($i < 0 || $i >= count($keys)) throw new RuntimeException('Tidak ada tahap berikutnya.');
            $to = $keys[$i];
            if ($dir === 1 && $to === 'proses_servis' && $itemCount < 1) throw new RuntimeException('Tambahkan minimal satu item estimasi (sparepart / pekerjaan) sebelum ke Proses Servis.');
            if ($dir === -1 && $job['stage'] === 'selesai') { /* membuka kembali: QC diulang */ }
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE opd_jobs SET stage = ?, qc_passed = IF(? = "selesai", qc_passed, 0), ready_at = IF(? = "selesai", ready_at, NULL) WHERE id = ?')->execute([$to, $to, $to, $id]);
            pl_event('opd', $id, 'move', $job['stage'], $to, null);
            $pdo->commit();
            log_activity('opd_moved', 'opd_job', $id, $job['stage'] . ' -> ' . $to);
            pl_redirect($self, $job['job_no'] . ' dipindah ke ' . $stages[$to][0] . '.');
        } elseif ($act === 'qc') {
            if ($job['stage'] !== 'selesai') throw new RuntimeException('Pemeriksaan QC dilakukan pada tahap Selesai.');
            $pass = !empty($_POST['qc_passed']) ? 1 : 0;
            $note = pl_str('qc_note', 500);
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE opd_jobs SET qc_passed = ?, qc_note = ?, ready_at = IF(? = 1, ready_at, NULL) WHERE id = ?')->execute([$pass, $note ?: null, $pass, $id]);
            pl_event('opd', $id, 'update', 'selesai', null, 'QC ' . ($pass ? 'LULUS' : 'belum lulus') . ($note ? ': ' . $note : ''));
            $pdo->commit();
            pl_redirect($self, $pass ? 'QC dinyatakan lulus.' : 'QC dicatat belum lulus.');
        } elseif ($act === 'ready') {
            if ($job['stage'] !== 'selesai' || !$job['qc_passed']) throw new RuntimeException('Unit baru dapat dinyatakan siap diambil setelah QC lulus.');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE opd_jobs SET ready_at = NOW() WHERE id = ?')->execute([$id]);
            pl_event('opd', $id, 'update', 'selesai', null, 'Unit siap diambil pelanggan');
            $pdo->commit();
            log_activity('opd_ready', 'opd_job', $id, $job['plate_no']);
            pl_redirect($self, $job['plate_no'] . ' siap diambil pelanggan.');
        } elseif ($act === 'pickup') {
            if (!$job['ready_at']) throw new RuntimeException('Unit belum dinyatakan siap diambil.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE opd_jobs SET status='picked_up', picked_up_at=NOW() WHERE id = ?")->execute([$id]);
            pl_event('opd', $id, 'close', 'selesai', null, 'Unit sudah diambil pelanggan');
            $pdo->commit();
            log_activity('opd_pickup', 'opd_job', $id, $job['plate_no']);
            pl_redirect($self, $job['plate_no'] . ' sudah diambil pelanggan.');
        }
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        pl_redirect($self, $ex->getMessage(), 'danger');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        pl_redirect($self, 'Kesalahan database. Perubahan dibatalkan.', 'danger');
    }
    pl_redirect($self, 'Aksi tidak dikenal.', 'danger');
}

// ---- Tampilan ----
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sel = isset($_GET['pic']) ? (int)$_GET['pic'] : 0;   // filter PIC
$sql = "SELECT j.*, u.full_name AS pic_name FROM opd_jobs j JOIN users u ON u.id = j.pic_user_id WHERE j.status = 'active'";
$params = [];
if ($q !== '') { $sql .= ' AND (j.plate_no LIKE ? OR j.customer_name LIKE ? OR j.job_no LIKE ? OR j.vehicle_model LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like); }
if ($sel) { $sql .= ' AND j.pic_user_id = ?'; $params[] = $sel; }
$sql .= ' ORDER BY j.updated_at DESC LIMIT 500';
$st = db()->prepare($sql); $st->execute($params); $jobs = $st->fetchAll();
$ids = array_column($jobs, 'id');
$events = pl_events('opd', $ids);
$items = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s2 = db()->prepare("SELECT * FROM opd_estimate_items WHERE job_id IN ($in) ORDER BY id"); $s2->execute($ids);
    foreach ($s2->fetchAll() as $it) $items[$it['job_id']][] = $it;
}
$pics = pl_pic_users('opd');
$byStage = array_fill_keys($keys, []);
foreach ($jobs as $j) $byStage[$j['stage']][] = $j;

$pageTitle = 'OPD Pipeline'; $activeMenu = 'opd'; $breadcrumb = ['Operasional', 'OPD Pipeline'];
include __DIR__ . '/../includes/header.php';
$f = flash();
?>
<?php if ($f): ?><div class="alert alert-<?= e($f[0]) ?> py-2"><?= e($f[1]) ?></div><?php endif; ?>
<?php if (!$editor): ?><div class="sim-banner"><i class="fa-solid fa-eye me-1"></i> Mode pantau (read-only): role Anda dapat melihat pipeline tetapi tidak mengubahnya.</div><?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form class="d-flex gap-2" method="get">
        <input class="form-control form-control-sm" style="width:230px" name="q" placeholder="Cari plat, pelanggan, no. job..." value="<?= e($q) ?>">
        <select class="form-select form-select-sm w-auto" name="pic" onchange="this.form.submit()"><option value="0">Semua PIC</option>
            <?php foreach ($pics as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $sel == $p['id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <?php if ($editor): ?><button class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#mNew"><i class="fa-solid fa-plus me-1"></i>Terima Unit</button><?php endif; ?>
</div>

<div class="kanban">
<?php foreach ($stages as $key => $def): $list = $byStage[$key]; ?>
    <div class="kcol" style="--accent:<?= e($def[3]) ?>">
        <div class="kcol-head"><div><i class="fa-solid <?= e($def[2]) ?> me-1"></i><b><?= e($def[0]) ?></b> <span class="badge text-bg-light border"><?= count($list) ?></span></div><small class="text-muted"><?= e($def[1]) ?></small></div>
        <div class="kcol-body">
        <?php foreach ($list as $j): $mine = $editor && pl_owns($j['pic_user_id']); ?>
            <div class="kcard">
                <div class="d-flex justify-content-between"><b><?= e($j['plate_no']) ?></b><small class="text-muted"><?= e(pl_age($j['updated_at'])) ?></small></div>
                <small class="text-muted"><?= e($j['job_no']) ?> &middot; <?= e($j['vehicle_model'] ?: '-') ?></small>
                <div class="small"><?= e($j['customer_name']) ?></div>
                <div class="small"><span class="badge text-bg-<?= $j['service_type'] === 'overhaul' ? 'danger' : 'info' ?>"><?= e($types[$j['service_type']]) ?></span>
                    <?php if ($key === 'selesai'): ?><?= $j['ready_at'] ? '<span class="badge text-bg-success">Siap diambil</span>' : ($j['qc_passed'] ? '<span class="badge text-bg-primary">QC lulus</span>' : '<span class="badge text-bg-warning">Menunggu QC</span>') ?><?php endif; ?></div>
                <div class="kcard-foot"><span class="small"><i class="fa-solid fa-user-gear me-1"></i><?= e($j['pic_name']) ?></span>
                    <span class="ms-auto d-flex gap-1">
                    <?php if ($mine): foreach (['prev' => ['fa-chevron-left', 'Mundur'], 'next' => ['fa-chevron-right', 'Maju']] as $dir => $ic):
                        if (($dir === 'prev' && $key === $keys[0]) || ($dir === 'next' && $key === end($keys))) continue; ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= $ic[1] ?>"><i class="fa-solid <?= $ic[0] ?>"></i></button></form>
                    <?php endforeach; endif; ?>
                    <button class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#m<?= (int)$j['id'] ?>">Detail</button></span></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$list): ?><div class="text-muted small text-center py-3">Kosong</div><?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php foreach ($jobs as $j): $mine = $editor && pl_owns($j['pic_user_id']); $its = isset($items[$j['id']]) ? $items[$j['id']] : []; $total = 0; ?>
<div class="modal fade" id="m<?= (int)$j['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title"><?= e($j['plate_no']) ?> &middot; <?= e($j['customer_name']) ?></h5><small class="text-muted"><?= e($j['job_no']) ?> &middot; <?= e($stages[$j['stage']][0]) ?> &middot; PIC <?= e($j['pic_name']) ?></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php $d = $mine ? '' : 'disabled'; ?>
        <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <div class="col-md-6"><label class="form-label small">Pelanggan</label><input class="form-control form-control-sm" name="customer_name" maxlength="120" value="<?= e($j['customer_name']) ?>" required <?= $d ?>></div>
            <div class="col-md-6"><label class="form-label small">Telepon</label><input class="form-control form-control-sm" name="phone" maxlength="30" value="<?= e($j['phone']) ?>" <?= $d ?>></div>
            <div class="col-md-4"><label class="form-label small">Plat nomor</label><input class="form-control form-control-sm" name="plate_no" maxlength="15" value="<?= e($j['plate_no']) ?>" required <?= $d ?>></div>
            <div class="col-md-4"><label class="form-label small">Model kendaraan</label><input class="form-control form-control-sm" name="vehicle_model" maxlength="80" value="<?= e($j['vehicle_model']) ?>" <?= $d ?>></div>
            <div class="col-md-4"><label class="form-label small">Jenis pekerjaan</label><select class="form-select form-select-sm" name="service_type" <?= $d ?>><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $k === $j['service_type'] ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label small">PIC (penanggung jawab)</label><select class="form-select form-select-sm" name="pic_user_id" <?= $d ?>><?php foreach ($pics as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $j['pic_user_id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label small">Keluhan / info servis masuk</label><textarea class="form-control form-control-sm" name="complaint" rows="2" maxlength="2000" required <?= $d ?>><?= e($j['complaint']) ?></textarea></div>
            <?php if ($mine): ?><div class="col-12"><button class="btn btn-sm btn-primary">Simpan Perubahan</button></div><?php endif; ?>
        </form>

        <hr><h6 class="small text-uppercase text-muted">Estimasi (sparepart &amp; pekerjaan)</h6>
        <div class="table-responsive"><table class="table table-sm table-x align-middle">
            <thead><tr><th>Jenis</th><th>Deskripsi</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th><th></th></tr></thead><tbody>
            <?php foreach ($its as $it): $sub = $it['qty'] * $it['unit_price']; $total += $sub; ?>
                <tr><td><?= $it['item_type'] === 'sparepart' ? 'Sparepart' : 'Pekerjaan' ?></td><td><?= e($it['description']) ?></td><td class="text-end"><?= e(rtrim(rtrim($it['qty'], '0'), '.')) ?></td><td class="text-end"><?= rupiah($it['unit_price']) ?></td><td class="text-end"><?= rupiah($sub) ?></td>
                <td><?php if ($mine && in_array($j['stage'], ['estimasi', 'proses_servis'], true)): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_item"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>"><button class="btn btn-sm btn-link text-danger p-0" title="Hapus"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$its): ?><tr><td colspan="6" class="text-muted text-center">Belum ada item.</td></tr><?php else: ?><tr><th colspan="4" class="text-end">Total estimasi</th><th class="text-end"><?= rupiah($total) ?></th><th></th></tr><?php endif; ?>
        </tbody></table></div>
        <?php if ($mine && in_array($j['stage'], ['estimasi', 'proses_servis'], true)): ?>
        <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="add_item"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <div class="col-md-3"><select class="form-select form-select-sm" name="item_type"><option value="sparepart">Sparepart</option><option value="pekerjaan">Pekerjaan</option></select></div>
            <div class="col-md-4"><input class="form-control form-control-sm" name="description" maxlength="200" placeholder="Deskripsi" required></div>
            <div class="col-md-1"><input class="form-control form-control-sm" name="qty" value="1" title="Qty"></div>
            <div class="col-md-3"><input class="form-control form-control-sm" name="unit_price" value="0" title="Harga satuan (Rp)"></div>
            <div class="col-md-1"><button class="btn btn-sm btn-outline-primary w-100">+</button></div>
        </form>
        <?php endif; ?>

        <?php if ($j['stage'] === 'selesai'): ?>
        <hr><h6 class="small text-uppercase text-muted">Quality Control &amp; Serah Terima</h6>
        <form method="post" class="row g-2 align-items-end"><?= csrf_field() ?><input type="hidden" name="action" value="qc"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <div class="col-md-8"><input class="form-control form-control-sm" name="qc_note" maxlength="500" value="<?= e($j['qc_note']) ?>" placeholder="Catatan hasil QC" <?= $d ?>></div>
            <div class="col-md-2"><label class="small"><input type="checkbox" name="qc_passed" value="1" <?= $j['qc_passed'] ? 'checked' : '' ?> <?= $d ?>> QC lulus</label></div>
            <?php if ($mine): ?><div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Simpan QC</button></div><?php endif; ?>
        </form>
        <?php if ($mine): ?><div class="d-flex gap-2 mt-2">
            <?php if (!$j['ready_at']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="ready"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><button class="btn btn-sm btn-success" <?= $j['qc_passed'] ? '' : 'disabled title="QC harus lulus dulu"' ?>><i class="fa-solid fa-key me-1"></i>Nyatakan Siap Diambil</button></form>
            <?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pickup"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><button class="btn btn-sm btn-success" onclick="return confirm('Unit sudah diambil pelanggan?')"><i class="fa-solid fa-hand-holding me-1"></i>Unit Sudah Diambil</button></form><?php endif; ?>
        </div><?php endif; ?>
        <?php endif; ?>

        <?php if ($mine): ?><hr><form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="Catatan pengerjaan..." required><button class="btn btn-sm btn-outline-primary text-nowrap">Catat</button></form><?php endif; ?>
        <hr><h6 class="small text-uppercase text-muted">Riwayat</h6>
        <?php foreach (isset($events[$j['id']]) ? $events[$j['id']] : [] as $ev): ?>
            <div class="small mb-1"><span class="text-muted"><?= e($ev['created_at']) ?></span> &middot; <b><?= e($ev['full_name'] ?: '-') ?></b>:
            <?php if ($ev['event_type'] === 'move'): ?>pindah <?= e($stages[$ev['from_stage']][0]) ?> &rarr; <?= e($stages[$ev['to_stage']][0]) ?><?php else: ?><?= e($ev['note'] ?: $ev['event_type']) ?><?php endif; ?></div>
        <?php endforeach; ?>
    </div>
</div></div></div>
<?php endforeach; ?>

<?php if ($editor): ?>
<div class="modal fade" id="mNew" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="modal-header"><h5 class="modal-title">Terima Unit (Gate Awal)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
        <div class="col-md-6"><label class="form-label small">Pelanggan *</label><input class="form-control form-control-sm" name="customer_name" maxlength="120" required></div>
        <div class="col-md-6"><label class="form-label small">Telepon</label><input class="form-control form-control-sm" name="phone" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label small">Plat nomor *</label><input class="form-control form-control-sm" name="plate_no" maxlength="15" required></div>
        <div class="col-md-4"><label class="form-label small">Model kendaraan</label><input class="form-control form-control-sm" name="vehicle_model" maxlength="80"></div>
        <div class="col-md-4"><label class="form-label small">Jenis pekerjaan *</label><select class="form-select form-select-sm" name="service_type"><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label small">PIC (penanggung jawab) *</label><select class="form-select form-select-sm" name="pic_user_id"><?php foreach ($pics as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $me['id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label small">Keluhan / info servis masuk *</label><textarea class="form-control form-control-sm" name="complaint" rows="3" maxlength="2000" required></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-success">Terima Unit</button></div>
</form></div></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
