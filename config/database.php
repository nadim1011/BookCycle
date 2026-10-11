<?php

declare(strict_types=1);

$databaseSetting = static function (string $name, string $default): string {
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    if (isset($_ENV[$name])) {
        return (string) $_ENV[$name];
    }

    if (isset($_SERVER[$name])) {
        return (string) $_SERVER[$name];
    }

    return $default;
};

define('DB_HOST', $databaseSetting('DB_HOST', 'sql211.infinityfree.com'));
define('DB_NAME', $databaseSetting('DB_NAME', 'if0_43117205_bookcl'));
define('DB_USER', $databaseSetting('DB_USER', 'if0_43117205'));
define('DB_PASS', $databaseSetting('DB_PASS', 'WRWwqdNMfZOG'));
define('DB_CHARSET', 'utf8mb4');

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]
    );

    return $pdo;
}
