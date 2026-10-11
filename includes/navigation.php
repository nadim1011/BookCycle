<?php

declare(strict_types=1);

$user = currentUser();
$navItems = [
    ['label' => 'হোম', 'url' => 'index.php'],
    ['label' => 'বই খুঁজুন', 'url' => 'books.php'],
    ['label' => 'কীভাবে কাজ করে', 'url' => 'index.php#how-it-works'],
    ['label' => 'বই শেয়ার করুন', 'url' => 'add-book.php'],
];

if ($user) {
    $navItems = [
        ['label' => 'হোম', 'url' => 'index.php'],
        ['label' => 'বই খুঁজুন', 'url' => 'books.php'],
        ['label' => 'ড্যাশবোর্ড', 'url' => 'dashboard.php'],
        ['label' => 'বিজ্ঞপ্তি', 'url' => 'notifications.php'],
        ['label' => 'প্রোফাইল', 'url' => 'profile.php'],
    ];
}
?>
<header class="site-header">
    <div class="container nav-wrap">
        <a href="<?= e(APP_BASE_URL . 'index.php') ?>" class="brand" aria-label="বুকসাইকেল">
            <span class="brand-mark">B</span>
            <span>
                <strong>BookCycle</strong>
                <small>বুকসাইকেল</small>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="মেনু খুলুন">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" aria-label="প্রধান নেভিগেশন">
            <ul>
                <?php foreach ($navItems as $item): ?>
                    <li><a href="<?= e(APP_BASE_URL . $item['url']) ?>"><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>

                <?php if ($user): ?>
                    <?php if (($user['role'] ?? 'student') === 'admin'): ?>
                        <li><a href="<?= e(APP_BASE_URL . 'admin/index.php') ?>">অ্যাডমিন</a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(APP_BASE_URL . 'logout.php') ?>" class="btn btn-secondary btn-small">লগআউট</a></li>
                <?php else: ?>
                    <li><a href="<?= e(APP_BASE_URL . 'login.php') ?>">লগইন</a></li>
                    <li><a href="<?= e(APP_BASE_URL . 'admin/login.php') ?>">অ্যাডমিন লগইন</a></li>
                    <li><a href="<?= e(APP_BASE_URL . 'register.php') ?>" class="btn btn-primary btn-small">নিবন্ধন</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
