<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$title = 'অনুমতি নেই';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="form-shell" style="text-align:center;">
                <h1>৪০৩ — এই পৃষ্ঠা দেখার অনুমতি নেই</h1>
                <p>এই কনটেন্টটি আপনার অ্যাকাউন্ট বা ভূমিকার জন্য উপলব্ধ নয়।</p>
                <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'index.php') ?>">হোমে ফিরুন</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
