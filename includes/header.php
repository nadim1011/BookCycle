<?php

declare(strict_types=1);

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/init.php';
}

$user = currentUser();
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? APP_NAME) ?> | বুকসাইকেল</title>
    <meta name="description" content="বুকসাইকেল—শিক্ষার্থীদের বিনামূল্যে বই আদান-প্রদান প্ল্যাটফর্ম।">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(APP_BASE_URL . 'assets/css/style.css?v=' . (int) filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
</head>
<body>
    <div class="page-shell">
        <?php require __DIR__ . '/navigation.php'; ?>

        <?php if (!empty($flashMessages)): ?>
            <div class="flash-stack">
                <?php foreach ($flashMessages as $flash): ?>
                    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
