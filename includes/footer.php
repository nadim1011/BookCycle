<?php

declare(strict_types=1);
?>
        <footer class="site-footer">
            <div class="container footer-grid">
                <div>
                    <h3>BookCycle</h3>
                    <p>বই ভাগ করি, জ্ঞানের চক্র গড়ি।</p>
                </div>
                <div>
                    <h4>দ্রুত লিঙ্ক</h4>
                    <ul>
                        <li><a href="<?= e(APP_BASE_URL . 'books.php') ?>">বই খুঁজুন</a></li>
                        <li><a href="<?= e(APP_BASE_URL . 'dashboard.php') ?>">ড্যাশবোর্ড</a></li>
                        <li><a href="<?= e(APP_BASE_URL . 'profile.php') ?>">প্রোফাইল</a></li>
                    </ul>
                </div>
                <div>
                    <h4>সাহায্য</h4>
                    <ul>
                        <li><a href="<?= e(APP_BASE_URL . 'index.php#how-it-works') ?>">কীভাবে কাজ করে</a></li>
                        <li><a href="<?= e(APP_BASE_URL . 'login.php') ?>">লগইন</a></li>
                        <li><a href="<?= e(APP_BASE_URL . 'register.php') ?>">নিবন্ধন</a></li>
                    </ul>
                </div>
            </div>
            <div class="container footer-bottom">
                <p>© <?= date('Y') ?> বুকসাইকেল — শিক্ষার্থীদের জন্য বিনামূল্যে বই শেয়ারিং প্ল্যাটফর্ম</p>
            </div>
        </footer>
    </div>

    <script src="<?= e(APP_BASE_URL . 'assets/js/main.js?v=' . (int) filemtime(__DIR__ . '/../assets/js/main.js')) ?>"></script>
</body>
</html>
