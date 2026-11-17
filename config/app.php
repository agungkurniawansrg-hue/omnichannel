<?php
// Konfigurasi aplikasi AutoTRANZ. Sesuaikan BASE_URL jika folder project diganti.
define('APP_NAME', 'AutoTRANZ');
define('APP_FULL_NAME', 'AutoTRANZ Omnichannel Management System');
define('APP_VERSION', '0.1.0-phase1');
define('BASE_URL', '/omnichannel');          // http://localhost/omnichannel/
define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');


function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function rupiah($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
