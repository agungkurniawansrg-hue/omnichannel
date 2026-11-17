<?php
// Koneksi PDO singleton + helper log aktivitas.
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset={$c['charset']}";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa config/database.php dan pastikan database sudah dibuat (lihat database/install.php).');
        }
    }
    return $pdo;
}

function log_activity($action, $entity = null, $entityId = null, $details = null, $userId = null) {
    if ($userId === null && isset($_SESSION['user']['id'])) $userId = $_SESSION['user']['id'];
    $st = db()->prepare('INSERT INTO activity_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)');
    $st->execute([$userId, $action, $entity, $entityId, $details !== null ? mb_substr($details, 0, 500) : null, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null]);
}
