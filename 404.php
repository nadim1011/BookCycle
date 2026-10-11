<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$title = 'পৃষ্ঠা পাওয়া যায়নি';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section">
        <div class="container">
            <div class="form-shell" style="text-align:center;">
                <h1>৪০৪ — পৃষ্ঠা পাওয়া যায়নি</h1>
                <p>আপনি যে পাতাটি খুঁজছেন সেটি নেই বা সরানো হয়েছে।</p>
                <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'index.php') ?>">হোমে ফিরুন</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
