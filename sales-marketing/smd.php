<?php
// SMD Pipeline: Leads Masuk -> Follow Up -> Penawaran -> Deal
require_once __DIR__ . '/../includes/pipeline.php';
require_access('smd');
$me = current_user();
$editor = can_edit('smd');
$stages = pl_stage_defs('smd');
$keys = array_keys($stages);
$self = 'sales-marketing/smd.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$editor) { http_response_code(403); exit('Role Anda hanya dapat melihat pipeline ini.'); }
    $act = isset($_POST['action']) ? $_POST['action'] : '';
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $pdo = db();
    try {
        if ($act === 'create') {
            $company = pl_str('company_name', 120); $phone = pl_str('phone', 30); $email = pl_str('email', 120);
            $pic = (int)$_POST['owner_user_id'];
            if ($company === '') throw new RuntimeException('Nama perusahaan / prospek wajib diisi.');
            if ($phone === '' && $email === '') throw new RuntimeException('Isi minimal nomor telepon atau email.');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Format email tidak valid.');
            if (!pl_valid_pic('smd', $pic)) throw new RuntimeException('PIC tidak valid.');
            $dup = $pdo->prepare("SELECT deal_no FROM smd_deals WHERE (? <> '' AND phone = ?) OR (? <> '' AND email = ?) LIMIT 1");
            $dup->execute([$phone, $phone, $email, $email]);
            if ($d = $dup->fetchColumn()) throw new RuntimeException("Prospek dengan telepon/email ini sudah ada ($d). Gunakan kartu yang ada.");
            $ch = (int)$_POST['source_channel_id'];
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO smd_deals (deal_no, company_name, contact_name, phone, email, source_channel_id, est_value, notes, owner_user_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute(['TMP' . bin2hex(random_bytes(5)), $company, pl_str('contact_name', 120) ?: null, $phone ?: null, $email ?: null, $ch ?: null, pl_money('est_value'), pl_str('notes', 2000) ?: null, $pic, $me['id']]);
            $newId = (int)$pdo->lastInsertId();
            $no = 'SMD-' . date('ym') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE smd_deals SET deal_no = ? WHERE id = ?')->execute([$no, $newId]);
            pl_event('smd', $newId, 'created', null, 'leads_masuk', 'Lead dicatat');
            $pdo->commit();
            log_activity('smd_created', 'smd_deal', $newId, $no);
            pl_redirect($self, "Lead $no dicatat.");
        }

        $st = $pdo->prepare('SELECT * FROM smd_deals WHERE id = ?'); $st->execute([$id]); $card = $st->fetch();
        if (!$card) throw new RuntimeException('Kartu tidak ditemukan.');
        if (!pl_owns($card['owner_user_id'])) throw new RuntimeException('Hanya PIC kartu ini (atau Administrator) yang dapat mengubahnya.');
        if ($card['status'] !== 'active') throw new RuntimeException('Kartu sudah ditutup.');

        if ($act === 'save') {
            $company = pl_str('company_name', 120); $email = pl_str('email', 120); $pic = (int)$_POST['owner_user_id'];
            if ($company === '') throw new RuntimeException('Nama perusahaan wajib diisi.');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Format email tidak valid.');
            if (!pl_valid_pic('smd', $pic)) throw new RuntimeException('PIC tidak valid.');
            $phone = pl_str('phone', 30);
            if ($phone === '' && $email === '') throw new RuntimeException('Isi minimal nomor telepon atau email.');
            $ch = (int)$_POST['source_channel_id'];
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE smd_deals SET company_name=?, contact_name=?, phone=?, email=?, source_channel_id=?, est_value=?, notes=?, spk_no=?, spk_date=?, owner_user_id=? WHERE id=?')
                ->execute([$company, pl_str('contact_name', 120) ?: null, $phone ?: null, $email ?: null, $ch ?: null, pl_money('est_value'), pl_str('notes', 2000) ?: null, pl_str('spk_no', 40) ?: null, pl_date_or_null('spk_date'), $pic, $id]);
            if ($pic !== (int)$card['owner_user_id']) pl_event('smd', $id, 'assign', null, null, 'PIC dialihkan');
            pl_event('smd', $id, 'update', null, null, 'Data kartu diperbarui');
            $pdo->commit();
            pl_redirect($self, 'Kartu diperbarui.');
        } elseif ($act === 'note') {
            $n = pl_str('note', 500);
            if ($n === '') throw new RuntimeException('Catatan follow up kosong.');
            pl_event('smd', $id, 'note', $card['stage'], null, $n);
            pl_redirect($self, 'Catatan dicatat.');
        } elseif ($act === 'move') {
            $dir = $_POST['dir'] === 'next' ? 1 : -1;
            $i = array_search($card['stage'], $keys, true) + $dir;
            if ($i < 0 || $i >= count($keys)) throw new RuntimeException('Tidak ada tahap berikutnya.');
            $to = $keys[$i];
            if ($dir === 1 && $to === 'penawaran' && (float)$card['est_value'] <= 0) throw new RuntimeException('Isi nilai penawaran (estimasi nilai) di Detail sebelum ke tahap Penawaran.');
            if ($dir === 1 && $to === 'deal' && trim((string)$card['spk_no']) === '') throw new RuntimeException('Isi nomor SPK di Detail sebelum ke tahap Deal.');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE smd_deals SET stage = ? WHERE id = ?')->execute([$to, $id]);
            pl_event('smd', $id, 'move', $card['stage'], $to, null);
            $pdo->commit();
            log_activity('smd_moved', 'smd_deal', $id, $card['stage'] . ' -> ' . $to);
            pl_redirect($self, $card['deal_no'] . ' dipindah ke ' . $stages[$to][0] . '.');
        } elseif ($act === 'won') {
            if ($card['stage'] !== 'deal' || trim((string)$card['spk_no']) === '') throw new RuntimeException('Penutupan hanya untuk kartu tahap Deal yang sudah memiliki nomor SPK.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE smd_deals SET status='won', closed_at=NOW() WHERE id=?")->execute([$id]);
            pl_event('smd', $id, 'close', 'deal', 'deal', 'Penjualan ditutup (SPK ' . $card['spk_no'] . ')');
            $pdo->commit();
            log_activity('smd_won', 'smd_deal', $id, $card['spk_no']);
            pl_redirect($self, $card['deal_no'] . ' ditutup sebagai penjualan berhasil.');
        } elseif ($act === 'lost') {
            $r = pl_str('reason', 255);
            if ($r === '') throw new RuntimeException('Alasan wajib diisi untuk menandai Lost.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE smd_deals SET status='lost', lost_reason=?, closed_at=NOW() WHERE id=?")->execute([$r, $id]);
            pl_event('smd', $id, 'close', $card['stage'], null, 'Lost: ' . $r);
            $pdo->commit();
            log_activity('smd_lost', 'smd_deal', $id, $r);
            pl_redirect($self, $card['deal_no'] . ' ditandai Lost.');
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
$view = isset($_GET['v']) && $_GET['v'] === 'db' ? 'db' : 'board';
$fStatus = isset($_GET['status']) ? $_GET['status'] : 'active';
$sql = 'SELECT d.*, u.full_name AS pic_name, c.name AS channel_name FROM smd_deals d JOIN users u ON u.id = d.owner_user_id LEFT JOIN channels c ON c.id = d.source_channel_id WHERE 1=1';
$params = [];
if ($view === 'board') { $sql .= " AND d.status = 'active'"; }
elseif (in_array($fStatus, ['active', 'won', 'lost'], true)) { $sql .= ' AND d.status = ?'; $params[] = $fStatus; }
if ($q !== '') { $sql .= ' AND (d.company_name LIKE ? OR d.contact_name LIKE ? OR d.phone LIKE ? OR d.email LIKE ? OR d.deal_no LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like, $like); }
$sql .= ' ORDER BY d.updated_at DESC LIMIT 500';
$st = db()->prepare($sql); $st->execute($params); $cards = $st->fetchAll();
$events = pl_events('smd', array_column($cards, 'id'));
$pics = pl_pic_users('smd');
$channels = db()->query('SELECT id, name FROM channels ORDER BY id')->fetchAll();
$byStage = array_fill_keys($keys, []);
foreach ($cards as $c) $byStage[$c['stage']][] = $c;
$counts = db()->query("SELECT stage, COUNT(*) n, SUM(est_value) v FROM smd_deals WHERE status='active' GROUP BY stage")->fetchAll(PDO::FETCH_UNIQUE);

$pageTitle = 'SMD Pipeline'; $activeMenu = 'smd'; $breadcrumb = ['Sales & Marketing', 'SMD Pipeline'];
include __DIR__ . '/../includes/header.php';
$f = flash();

function smd_card_form($c, $channels, $pics, $editable) {
    $d = $editable ? '' : 'disabled';
    ob_start(); ?>
    <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <div class="col-md-6"><label class="form-label small">Perusahaan / prospek</label><input class="form-control form-control-sm" name="company_name" maxlength="120" value="<?= e($c['company_name']) ?>" required <?= $d ?>></div>
        <div class="col-md-6"><label class="form-label small">Nama kontak</label><input class="form-control form-control-sm" name="contact_name" maxlength="120" value="<?= e($c['contact_name']) ?>" <?= $d ?>></div>
        <div class="col-md-4"><label class="form-label small">Telepon</label><input class="form-control form-control-sm" name="phone" maxlength="30" value="<?= e($c['phone']) ?>" <?= $d ?>></div>
        <div class="col-md-4"><label class="form-label small">Email</label><input class="form-control form-control-sm" type="email" name="email" maxlength="120" value="<?= e($c['email']) ?>" <?= $d ?>></div>
        <div class="col-md-4"><label class="form-label small">Sumber channel</label><select class="form-select form-select-sm" name="source_channel_id" <?= $d ?>><option value="0">-</option>
            <?php foreach ($channels as $ch): ?><option value="<?= (int)$ch['id'] ?>" <?= $ch['id'] == $c['source_channel_id'] ? 'selected' : '' ?>><?= e($ch['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label small">Nilai penawaran (Rp)</label><input class="form-control form-control-sm" name="est_value" value="<?= e((int)$c['est_value']) ?>" <?= $d ?>></div>
        <div class="col-md-4"><label class="form-label small">No. SPK</label><input class="form-control form-control-sm" name="spk_no" maxlength="40" value="<?= e($c['spk_no']) ?>" <?= $d ?>></div>
        <div class="col-md-4"><label class="form-label small">Tanggal SPK</label><input class="form-control form-control-sm" type="date" name="spk_date" value="<?= e($c['spk_date']) ?>" <?= $d ?>></div>
        <div class="col-md-6"><label class="form-label small">PIC Sales</label><select class="form-select form-select-sm" name="owner_user_id" <?= $d ?>>
            <?php foreach ($pics as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $c['owner_user_id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label small">Catatan</label><textarea class="form-control form-control-sm" name="notes" rows="2" maxlength="2000" <?= $d ?>><?= e($c['notes']) ?></textarea></div>
        <?php if ($editable): ?><div class="col-12"><button class="btn btn-sm btn-primary">Simpan Perubahan</button></div><?php endif; ?>
    </form>
    <?php return ob_get_clean();
}
?>
<?php if ($f): ?><div class="alert alert-<?= e($f[0]) ?> py-2"><?= e($f[1]) ?></div><?php endif; ?>
<?php if (!$editor): ?><div class="sim-banner"><i class="fa-solid fa-eye me-1"></i> Mode pantau (read-only): role Anda dapat melihat pipeline tetapi tidak mengubahnya.</div><?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <div class="btn-group btn-group-sm">
        <a class="btn btn-<?= $view === 'board' ? 'primary' : 'outline-primary' ?>" href="?v=board"><i class="fa-solid fa-table-columns me-1"></i>Pipeline</a>
        <a class="btn btn-<?= $view === 'db' ? 'primary' : 'outline-primary' ?>" href="?v=db"><i class="fa-solid fa-database me-1"></i>Database Prospek</a>
    </div>
    <form class="d-flex gap-2" method="get"><input type="hidden" name="v" value="<?= e($view) ?>">
        <input class="form-control form-control-sm" style="width:230px" name="q" placeholder="Cari prospek, kontak, telepon..." value="<?= e($q) ?>">
        <?php if ($view === 'db'): ?><select class="form-select form-select-sm w-auto" name="status" onchange="this.form.submit()">
            <?php foreach (['active' => 'Aktif', 'won' => 'Won', 'lost' => 'Lost', 'all' => 'Semua'] as $k => $l): ?><option value="<?= $k ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select><?php endif; ?>
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
    </form>
    <?php if (can('inbox')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('omnichannel/inbox.php') ?>"><i class="fa-solid fa-inbox me-1"></i>Buka Inbox</a><?php endif; ?>
    <?php if ($editor): ?><button class="btn btn-sm btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#mNew"><i class="fa-solid fa-plus me-1"></i>Tambah Lead</button><?php endif; ?>
</div>

<?php if ($view === 'board'): ?>
<div class="kanban">
<?php foreach ($stages as $key => $def): $list = $byStage[$key]; $cnt = isset($counts[$key]) ? $counts[$key] : ['n' => 0, 'v' => 0]; ?>
    <div class="kcol" style="--accent:<?= e($def[3]) ?>">
        <div class="kcol-head"><div><i class="fa-solid <?= e($def[2]) ?> me-1"></i><b><?= e($def[0]) ?></b> <span class="badge text-bg-light border"><?= (int)$cnt['n'] ?></span></div>
            <small class="text-muted"><?= e($def[1]) ?></small><?php if ($cnt['v'] > 0): ?><small class="d-block text-muted">Total <?= rupiah($cnt['v']) ?></small><?php endif; ?></div>
        <div class="kcol-body">
        <?php foreach ($list as $c): $mine = $editor && pl_owns($c['owner_user_id']); ?>
            <div class="kcard">
                <div class="d-flex justify-content-between"><b><?= e($c['company_name']) ?></b><small class="text-muted"><?= e(pl_age($c['updated_at'])) ?></small></div>
                <small class="text-muted"><?= e($c['deal_no']) ?><?= $c['contact_name'] ? ' &middot; ' . e($c['contact_name']) : '' ?></small>
                <?php if ($c['est_value'] > 0): ?><div class="small"><?= rupiah($c['est_value']) ?></div><?php endif; ?>
                <?php if ($c['spk_no']): ?><div class="small"><i class="fa-solid fa-file-signature me-1"></i>SPK <?= e($c['spk_no']) ?></div><?php endif; ?>
                <div class="kcard-foot"><span class="small"><i class="fa-solid fa-user me-1"></i><?= e($c['pic_name']) ?></span>
                    <span class="ms-auto d-flex gap-1">
                    <?php if ($mine): foreach (['prev' => ['fa-chevron-left', 'Mundur'], 'next' => ['fa-chevron-right', 'Maju']] as $dir => $ic):
                        if (($dir === 'prev' && $key === $keys[0]) || ($dir === 'next' && $key === end($keys))) continue; ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= $ic[1] ?>"><i class="fa-solid <?= $ic[0] ?>"></i></button></form>
                    <?php endforeach; endif; ?>
                    <button class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#m<?= (int)$c['id'] ?>">Detail</button></span></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$list): ?><div class="text-muted small text-center py-3">Kosong</div><?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card-x"><div class="table-responsive"><table class="table table-x table-hover align-middle">
    <thead><tr><th>No</th><th>Prospek</th><th>Kontak</th><th>Telepon / Email</th><th>Channel</th><th>Tahap</th><th>Nilai</th><th>PIC</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cards as $c): ?>
        <tr><td><b><?= e($c['deal_no']) ?></b></td><td><?= e($c['company_name']) ?></td><td><?= e($c['contact_name']) ?></td>
        <td class="small"><?= e($c['phone']) ?><br><?= e($c['email']) ?></td><td><?= e($c['channel_name'] ?: '-') ?></td>
        <td><?= e($stages[$c['stage']][0]) ?></td><td><?= $c['est_value'] > 0 ? rupiah($c['est_value']) : '-' ?></td><td><?= e($c['pic_name']) ?></td>
        <td><span class="badge text-bg-<?= ['active' => 'primary', 'won' => 'success', 'lost' => 'secondary'][$c['status']] ?>"><?= e(['active' => 'Aktif', 'won' => 'Won', 'lost' => 'Lost'][$c['status']]) ?></span></td>
        <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#m<?= (int)$c['id'] ?>">Detail</button></td></tr>
    <?php endforeach; ?>
    <?php if (!$cards): ?><tr><td colspan="10" class="text-center text-muted py-4">Belum ada prospek.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php endif; ?>

<?php foreach ($cards as $c): $mine = $editor && pl_owns($c['owner_user_id']) && $c['status'] === 'active'; ?>
<div class="modal fade" id="m<?= (int)$c['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title"><?= e($c['company_name']) ?></h5><small class="text-muted"><?= e($c['deal_no']) ?> &middot; <?= e($stages[$c['stage']][0]) ?> &middot; PIC <?= e($c['pic_name']) ?></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?php if ($c['status'] === 'lost'): ?><div class="alert alert-secondary py-2 small">Lost: <?= e($c['lost_reason']) ?></div><?php endif; ?>
        <?php if ($c['status'] === 'won'): ?><div class="alert alert-success py-2 small">Penjualan ditutup <?= e($c['closed_at']) ?> (SPK <?= e($c['spk_no']) ?>).</div><?php endif; ?>
        <?= smd_card_form($c, $channels, $pics, $mine) ?>
        <?php if ($mine): ?>
        <hr><form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="Catat follow up / kirim brosur / info harga..." required><button class="btn btn-sm btn-outline-primary text-nowrap">Catat</button></form>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <?php if ($c['stage'] === 'deal'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="won"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-success" onclick="return confirm('Tutup penjualan ini?')"><i class="fa-solid fa-check me-1"></i>Tutup Penjualan</button></form><?php endif; ?>
            <form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="action" value="lost"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input class="form-control form-control-sm" name="reason" maxlength="255" placeholder="Alasan Lost" required><button class="btn btn-sm btn-outline-danger text-nowrap" onclick="return confirm('Tandai Lost?')">Tandai Lost</button></form>
        </div>
        <?php endif; ?>
        <hr><h6 class="small text-uppercase text-muted">Riwayat</h6>
        <?php foreach (isset($events[$c['id']]) ? $events[$c['id']] : [] as $ev): ?>
            <div class="small mb-1"><span class="text-muted"><?= e($ev['created_at']) ?></span> &middot; <b><?= e($ev['full_name'] ?: '-') ?></b>:
            <?php if ($ev['event_type'] === 'move'): ?>pindah <?= e($stages[$ev['from_stage']][0]) ?> &rarr; <?= e($stages[$ev['to_stage']][0]) ?>
            <?php else: ?><?= e($ev['note'] ?: $ev['event_type']) ?><?php endif; ?></div>
        <?php endforeach; ?>
    </div>
</div></div></div>
<?php endforeach; ?>

<?php if ($editor): ?>
<div class="modal fade" id="mNew" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="modal-header"><h5 class="modal-title">Tambah Lead (Leads Masuk)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
        <div class="col-md-6"><label class="form-label small">Perusahaan / prospek *</label><input class="form-control form-control-sm" name="company_name" maxlength="120" required></div>
        <div class="col-md-6"><label class="form-label small">Nama kontak</label><input class="form-control form-control-sm" name="contact_name" maxlength="120"></div>
        <div class="col-md-4"><label class="form-label small">Telepon</label><input class="form-control form-control-sm" name="phone" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label small">Email</label><input class="form-control form-control-sm" type="email" name="email" maxlength="120"></div>
        <div class="col-md-4"><label class="form-label small">Sumber channel</label><select class="form-select form-select-sm" name="source_channel_id"><option value="0">-</option><?php foreach ($channels as $ch): ?><option value="<?= (int)$ch['id'] ?>"><?= e($ch['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label small">PIC Sales *</label><select class="form-select form-select-sm" name="owner_user_id"><?php foreach ($pics as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $me['id'] ? 'selected' : '' ?>><?= e($p['full_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label small">Perkiraan nilai (Rp)</label><input class="form-control form-control-sm" name="est_value" value="0"></div>
        <div class="col-12"><label class="form-label small">Catatan</label><textarea class="form-control form-control-sm" name="notes" rows="2" maxlength="2000"></textarea></div>
        <div class="col-12 small text-muted">Isi minimal telepon atau email. Prospek dengan telepon/email yang sama tidak bisa dicatat dua kali.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-success">Simpan Lead</button></div>
</form></div></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
