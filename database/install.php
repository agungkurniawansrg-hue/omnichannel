<?php
// Seeder akun awal. Jalankan SETELAH omnichannel_rental.sql diimpor:
//   C:\AppServ\php7\php.exe C:\AppServ\www\omnichannel\database\install.php
// Hanya via CLI. Membuat 1 akun per role dengan password acak (ditampilkan sekali).
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only.'); }
require_once __DIR__ . '/../includes/db.php';
$pdo = db();
if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) exit("Tabel users sudah berisi data. Dibatalkan.\n");

$seed = [
    ['administrator',     'admin',   'Administrator',      null],
    ['managing-director', 'md',      'Managing Director',  'managing-director'],
    ['sales-marketing',   'sales',   'Staf Sales',         'sales-marketing'],
    ['finance',           'finance', 'Staf Finance',       'finance'],
    ['operasional',       'ops',     'Staf Operasional',   'operasional'],
    ['human-resource',    'hr',      'Staf HR',            'human-resource'],
];
$pdo->beginTransaction();
try {
    $roleId = function ($slug) use ($pdo) { $s = $pdo->prepare('SELECT id FROM roles WHERE slug=?'); $s->execute([$slug]); return $s->fetchColumn(); };
    $deptId = function ($slug) use ($pdo) { $s = $pdo->prepare('SELECT id FROM departments WHERE slug=?'); $s->execute([$slug]); return $s->fetchColumn(); };
    $n = 1;
    foreach ($seed as $row) {
        list($role, $user, $name, $dept) = $row;
        $pw = substr(bin2hex(random_bytes(6)), 0, 10) . 'A1';
        $empId = null;
        if ($dept) {
            $pdo->prepare('INSERT INTO employees (employee_no, department_id, full_name, position, email, hire_date) VALUES (?,?,?,?,?,CURDATE())')
                ->execute([sprintf('EMP-%04d', $n), $deptId($dept), $name, 'Staf', $user . '@autotranz.local']);
            $empId = $pdo->lastInsertId();
        }
        $pdo->prepare('INSERT INTO users (role_id, employee_id, username, email, full_name, password_hash) VALUES (?,?,?,?,?,?)')
            ->execute([$roleId($role), $empId, $user, $user . '@autotranz.local', $name, password_hash($pw, PASSWORD_DEFAULT)]);
        $uid = $pdo->lastInsertId();
        if ($role === 'sales-marketing') $pdo->prepare('INSERT INTO agents (user_id, display_name) VALUES (?,?)')->execute([$uid, $name]);
        echo str_pad($user, 10) . $pw . "   ($role)\n";
        $n++;
    }
    $pdo->commit();
    echo "\nSimpan password di atas, lalu ganti lewat menu Pengaturan Akun.\n";
} catch (Throwable $e) { $pdo->rollBack(); fwrite(STDERR, 'Gagal: ' . $e->getMessage() . "\n"); exit(1); }
