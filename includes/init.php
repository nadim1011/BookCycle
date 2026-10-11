<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

define('APP_NAME', 'BookCycle');

$script = $_SERVER['SCRIPT_NAME'] ?? '/bookcycle/index.php';
$root = rtrim(dirname($script), '/\\');
if (in_array(basename($root), ['actions', 'admin'], true)) {
    $root = rtrim(dirname($root), '/\\');
}
$baseUrl = ($root === '.' || $root === '/' || $root === '\\') ? '/' : $root . '/';

define('APP_BASE_URL', $baseUrl);
