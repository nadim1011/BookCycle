<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$title = 'সার্ভারে সমস্যা';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="form-shell" style="text-align:center;">
                <h1>৫০০ — সার্ভারে সমস্যা হয়েছে</h1>
                <p>দুঃখিত, সাময়িকভাবে সার্ভারে সমস্যা হচ্ছে। দয়া করে কিছুক্ষণ পরে আবার চেষ্টা করুন।</p>
                <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'index.php') ?>">হোমে ফিরুন</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
