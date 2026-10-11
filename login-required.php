<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$title = 'লগইন প্রয়োজন';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="form-shell" style="text-align:center;">
                <h1>লগইন করা প্রয়োজন</h1>
                <p>এই পৃষ্ঠা দেখতে আপনাকে লগইন করতে হবে।</p>
                <div class="form-actions" style="justify-content:center;">
                    <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'login.php') ?>">লগইন করুন</a>
                    <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'register.php') ?>">নিবন্ধন করুন</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
