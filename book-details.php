<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$pdo = getDb();
$book = $pdo->prepare('SELECT b.*, u.name AS owner_name, u.university, u.department FROM books b LEFT JOIN users u ON u.id = b.owner_id WHERE b.id = :id AND b.is_active = 1 LIMIT 1');
$book->execute([':id' => $id]);
$book = $book->fetch();

if (!$book) {
    flash('error', 'বইটি খুঁজে পাওয়া যায়নি।');
    redirect('books.php');
}

$title = $book['title'];
require_once __DIR__ . '/includes/header.php';

$canRequest = isLoggedIn() && ((int) $book['owner_id'] !== (int) ($_SESSION['user_id'] ?? 0));
$existingRequest = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT * FROM borrow_requests WHERE book_id = :book_id AND requester_id = :user_id ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':book_id' => $id, ':user_id' => (int) $_SESSION['user_id']]);
    $existingRequest = $stmt->fetch();
}
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / বই / <?= e($book['title']) ?></div>
            <h1><?= e($book['title']) ?></h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid" style="grid-template-columns: 1.1fr 1.3fr; gap: 28px;">
                <div class="card">
                    <img class="card-image" src="<?= e(APP_BASE_URL . ($book['cover_image'] ?: 'assets/images/default-book.svg')) ?>" alt="<?= e($book['title']) ?>">
                </div>
                <div class="card card-body">
                    <span class="pill"><?= e($book['category']) ?></span>
                    <h2><?= e($book['title']) ?></h2>
                    <p><strong>লেখক:</strong> <?= e($book['author']) ?></p>
                    <p><strong>বইদাতা:</strong>
                        <?php if ((int) $book['owner_id'] > 0): ?>
                            <a href="<?= e(APP_BASE_URL . 'profile.php?id=' . (int) $book['owner_id']) ?>"><?= e($book['owner_name'] ?: 'অজানা') ?></a>
                        <?php else: ?>
                            <?= e($book['owner_name'] ?: 'অজানা') ?>
                        <?php endif; ?>
                    </p>
                    <p><strong>বিশ্ববিদ্যালয়:</strong> <?= e($book['university'] ?: 'তথ্য নেই') ?></p>
                    <p><strong>বইয়ের অবস্থা:</strong> <?= e(bookConditionLabel((string) $book['condition_status'])) ?></p>
                    <p><strong>প্রাপ্যতা:</strong> <?= e(bookAvailabilityLabel((string) $book['availability_status'])) ?></p>

                    <?php if (!empty($book['isbn'])): ?>
                        <p><strong>ISBN:</strong> <?= e($book['isbn']) ?></p>
                    <?php endif; ?>

                    <div class="meta-list">
                        <div class="meta-item"><span>বিবরণ</span><span><?= e($book['description'] ?: 'বিবরণ নেই') ?></span></div>
                        <div class="meta-item"><span>সংগ্রহ নির্দেশনা</span><span><?= e($book['pickup_instructions'] ?: 'সরাসরি সম্মেলন করুন') ?></span></div>
                    </div>

                    <?php if ($canRequest): ?>
                        <?php if ($existingRequest && in_array(($existingRequest['status'] ?? 'Pending'), ['Pending','Approved','Borrowed','Return Requested'], true)): ?>
                            <div class="badge badge-yellow">আপনার অনুরোধের অবস্থা: <?= e(requestStatusLabel((string) $existingRequest['status'])) ?></div>
                        <?php else: ?>
                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/request-actions.php') ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="request_book">
                                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
                                <div class="form-grid">
                                    <div class="field">
                                        <label for="start_date">শুরুর তারিখ</label>
                                        <input id="start_date" type="date" name="start_date" required>
                                    </div>
                                    <div class="field">
                                        <label for="due_date">ফেরতের তারিখ</label>
                                        <input id="due_date" type="date" name="due_date" required>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">বই ধার নেওয়ার অনুরোধ</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php elseif (isLoggedIn()): ?>
                        <div class="badge badge-blue">আপনি এই বইয়ের মালিক।</div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'login.php') ?>">লগইন করে অনুরোধ করুন</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
