<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$title = 'হোম';
$pdo = getDb();

$latestBooks = $pdo->query('SELECT b.*, u.name AS owner_name FROM books b LEFT JOIN users u ON u.id = b.owner_id WHERE b.is_active = 1 ORDER BY b.created_at DESC LIMIT 6')->fetchAll();
$categories = $pdo->query('SELECT category, COUNT(*) AS total FROM books WHERE is_active = 1 GROUP BY category ORDER BY total DESC LIMIT 6')->fetchAll();
$studentCount = (int) $pdo->query('SELECT COUNT(*) AS total FROM users WHERE role = "student"')->fetchColumn();
$bookCount = (int) $pdo->query('SELECT COUNT(*) AS total FROM books WHERE is_active = 1')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <h1>বই ভাগ করি, জ্ঞানের চক্র গড়ি।</h1>
                <p>আপনার প্রয়োজনের বইটি খুঁজে নিন অন্য কোনো শিক্ষার্থীর সংগ্রহে। নিজের পড়া বইটি ধার দিয়ে সাহায্য করুন আর অন্যের বই থেকে শিখুন—সম্পূর্ণ বিনামূল্যে।</p>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'books.php') ?>">বই খুঁজুন</a>
                    <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'add-book.php') ?>">বই শেয়ার করুন</a>
                </div>

                <form class="search-box" method="get" action="<?= e(APP_BASE_URL . 'books.php') ?>">
                    <input type="search" name="q" placeholder="বইয়ের নাম, লেখক বা বিষয় লিখুন" aria-label="বই খুঁজুন">
                    <button type="submit">খুঁজুন</button>
                </form>
            </div>

            <div class="hero-visual" aria-label="বুকসাইকেলের চিত্রকরন">
                <div class="book-stack">
                    <div class="stack-card">
                        <span class="pill">১৫০+ বই</span>
                        <h3>বই সংগ্রহ</h3>
                        <p>শিক্ষার্থীদের বই বিনিময়</p>
                    </div>
                    <div class="stack-card">
                        <span class="pill">ফ্রি</span>
                        <h3>ভাড়া নেই</h3>
                        <p>কোনো ফি ছাড়াই</p>
                    </div>
                    <div class="stack-card">
                        <span class="pill">জ্ঞান</span>
                        <h3>শেখা</h3>
                        <p>অন্যের পড়া থেকে শিখুন</p>
                    </div>
                    <div class="stack-card">
                        <span class="pill">১ ক্লিক</span>
                        <h3>অনুরোধ</h3>
                        <p>সহজ ও দ্রুত</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>সাম্প্রতিকভাবে যুক্ত হওয়া বই</h2>
            </div>

            <?php if (empty($latestBooks)): ?>
                <div class="empty-state">এখনও কোনো বই তালিকাভুক্ত হয়নি। প্রথম বইটি যোগ করুন এবং নিজের সংগ্রহ শুরু করুন।</div>
            <?php else: ?>
                <div class="grid cards-3">
                    <?php foreach ($latestBooks as $book): ?>
                        <article class="card">
                            <img class="card-image" src="<?= e(APP_BASE_URL . ($book['cover_image'] ?: 'assets/images/default-book.svg')) ?>" alt="<?= e($book['title']) ?>">
                            <div class="card-body">
                                <span class="pill"><?= e($book['category']) ?></span>
                                <h3><?= e($book['title']) ?></h3>
                                <p><?= e($book['author']) ?></p>
                                <div class="meta-item">
                                    <span>দাতা</span>
                                    <strong>
                                        <?php if ((int) $book['owner_id'] > 0): ?>
                                            <a href="<?= e(APP_BASE_URL . 'profile.php?id=' . (int) $book['owner_id']) ?>"><?= e($book['owner_name'] ?: 'অজানা') ?></a>
                                        <?php else: ?>
                                            <?= e($book['owner_name'] ?: 'অজানা') ?>
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="meta-item">
                                    <span>অবস্থা</span>
                                    <strong><?= e(bookConditionLabel((string) $book['condition_status'])) ?></strong>
                                </div>
                                <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'book-details.php?id=' . (int) $book['id']) ?>">বিস্তারিত</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>জনপ্রিয় বিষয়সমূহ</h2>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">এখনো কোনো বিষয় শ্রেণি নেই।</div>
            <?php else: ?>
                <div class="grid cards-4">
                    <?php foreach ($categories as $category): ?>
                        <div class="card card-body">
                            <h3><?= e($category['category']) ?></h3>
                            <p><?= e((string) $category['total']) ?>টি বই</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="how-it-works" class="section">
        <div class="container">
            <div class="section-header">
                <h2>কীভাবে বুকসাইকেল ব্যবহার করবেন</h2>
            </div>
            <div class="grid cards-3">
                <div class="card card-body">
                    <h3>১. নিবন্ধন করুন</h3>
                    <p>একটি সাধারণ শিক্ষার্থী অ্যাকাউন্ট তৈরি করুন এবং আপনার প্রোফাইল পূরণ করুন।</p>
                </div>
                <div class="card card-body">
                    <h3>২. বই যোগ করুন বা খুঁজুন</h3>
                    <p>আপনার বইয়ের তালিকা প্রকাশ করুন অথবা অন্য শিক্ষার্থীর কাছ থেকে প্রয়োজনীয় বই খুঁজুন।</p>
                </div>
                <div class="card card-body">
                    <h3>৩. অনুরোধ ও ধার</h3>
                    <p>অনুরোধ গ্রহণ/প্রত্যাখ্যান, বই হস্তান্তর ও ফেরত নিশ্চিত করুন—সর্বোচ্চ নিরাপত্তা নিশ্চিত করে।</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>বুকসাইকেলের সুবিধা</h2>
            </div>
            <div class="grid cards-3">
                <div class="card card-body">
                    <h3>খরচ কমে</h3>
                    <p>বিনামূল্যে বই আদান-প্রদানের মাধ্যমে শিক্ষা খরচ কমে।</p>
                </div>
                <div class="card card-body">
                    <h3>উপকারিতা বেশি</h3>
                    <p>অব্যবহৃত বই নতুনভাবে কাজে লাগে আর অন্যের পড়া থেকে বুঝতে সুবিধা হয়।</p>
                </div>
                <div class="card card-body">
                    <h3>সম্প্রদায় গড়ে</h3>
                    <p>শিক্ষার্থীদের মধ্যে সহযোগিতা ও জ্ঞানভিত্তিক সম্পর্ক গড়ে ওঠে।</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>কমিউনিটিতে যোগ দিন</h2>
            </div>
            <div class="card card-body">
                <p>আজই আপনার বই শেয়ার করা শুরু করুন, অন্য শিক্ষার্থীদের সহায়তা করুন, আর নিজের পড়া থেকে জ্ঞানকে আরও ব্যাপক করুন।</p>
                <div class="form-actions">
                    <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'register.php') ?>">নিবন্ধন করুন</a>
                    <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'books.php') ?>">বই খুঁজুন</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
