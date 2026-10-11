<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$title = 'বই খুঁজুন';
$pdo = getDb();
$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$condition = trim((string) ($_GET['condition'] ?? ''));

$where = ['b.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(b.title LIKE :q OR b.author LIKE :q OR b.category LIKE :q OR b.description LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($category !== '') {
    $where[] = 'b.category = :category';
    $params[':category'] = $category;
}
if ($condition !== '') {
    $where[] = 'b.condition_status = :condition';
    $params[':condition'] = $condition;
}

$sql = 'SELECT b.*, u.name AS owner_name FROM books b LEFT JOIN users u ON u.id = b.owner_id WHERE ' . implode(' AND ', $where) . ' ORDER BY b.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM books WHERE is_active = 1 ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / বই খুঁজুন</div>
            <h1>বই খুঁজুন</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="card card-body" method="get" action="<?= e(APP_BASE_URL . 'books.php') ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="q">বইয়ের নাম / লেখক / বিষয়</label>
                        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="যেমন: ডেটা স্ট্রাকচার">
                    </div>
                    <div class="field">
                        <label for="category">বিভাগ</label>
                        <select id="category" name="category">
                            <option value="">সব বিভাগ</option>
                            <?php foreach ($categories as $item): ?>
                                <option value="<?= e($item) ?>" <?= $item === $category ? 'selected' : '' ?>><?= e($item) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="condition">বইয়ের অবস্থা</label>
                        <select id="condition" name="condition">
                            <option value="">সব অবস্থা</option>
                            <option value="new" <?= $condition === 'new' ? 'selected' : '' ?>>নতুন</option>
                            <option value="almost_new" <?= $condition === 'almost_new' ? 'selected' : '' ?>>প্রায় নতুন</option>
                            <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>ভালো</option>
                            <option value="usable" <?= $condition === 'usable' ? 'selected' : '' ?>>ব্যবহারযোগ্য</option>
                        </select>
                    </div>
                    <div class="field">
                        <label> </label>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">ফিল্টার করুন</button>
                            <a href="<?= e(APP_BASE_URL . 'books.php') ?>" class="btn btn-secondary">রিসেট</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if (empty($books)): ?>
                <div class="empty-state">কোনো বই পাওয়া যায়নি। অন্য কোনো শব্দ দিয়ে খুঁজুন বা বইটি আপনার সংগ্রহে যোগ করুন।</div>
            <?php else: ?>
                <div class="grid cards-3">
                    <?php foreach ($books as $book): ?>
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
                                <div class="meta-item">
                                    <span>প্রাপ্যতা</span>
                                    <strong><?= e(bookAvailabilityLabel((string) $book['availability_status'])) ?></strong>
                                </div>
                                <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'book-details.php?id=' . (int) $book['id']) ?>">বিস্তারিত দেখুন</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
