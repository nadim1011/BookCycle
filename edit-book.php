<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
requireLogin();

$bookId = (int) ($_GET['id'] ?? 0);
$pdo = getDb();
$book = $pdo->prepare('SELECT * FROM books WHERE id = :id AND owner_id = :owner_id LIMIT 1');
$book->execute([':id' => $bookId, ':owner_id' => (int) $_SESSION['user_id']]);
$book = $book->fetch();

if (!$book) {
    flash('error', 'আপনি এই বইটি সম্পাদনা করতে পারবেন না।');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    flash('error', 'বই আপডেট করার জন্য আলাদা ফর্মে পোস্ট করুন।');
    redirect('edit-book.php?id=' . $bookId);
}

$title = 'বই সম্পাদনা';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / বই সম্পাদনা</div>
            <h1>বই সম্পাদনা</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="form-shell" method="post" action="<?= e(APP_BASE_URL . 'actions/book-actions.php') ?>" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_book">
                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">

                <div class="form-grid">
                    <div class="field">
                        <label for="title">বইয়ের নাম</label>
                        <input id="title" type="text" name="title" value="<?= e($book['title']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="author">লেখকের নাম</label>
                        <input id="author" type="text" name="author" value="<?= e($book['author']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="isbn">ISBN</label>
                        <input id="isbn" type="text" name="isbn" value="<?= e($book['isbn']) ?>">
                    </div>
                    <div class="field">
                        <label for="category">বিষয় / বিভাগ</label>
                        <input id="category" type="text" name="category" value="<?= e($book['category']) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="description">বই সম্পর্কে বিবরণ</label>
                        <textarea id="description" name="description"><?= e($book['description']) ?></textarea>
                    </div>
                    <div class="field">
                        <label for="condition_status">বইয়ের অবস্থা</label>
                        <select id="condition_status" name="condition_status" required>
                            <option value="new" <?= $book['condition_status'] === 'new' ? 'selected' : '' ?>>নতুন</option>
                            <option value="almost_new" <?= $book['condition_status'] === 'almost_new' ? 'selected' : '' ?>>প্রায় নতুন</option>
                            <option value="good" <?= $book['condition_status'] === 'good' ? 'selected' : '' ?>>ভালো</option>
                            <option value="usable" <?= $book['condition_status'] === 'usable' ? 'selected' : '' ?>>ব্যবহারযোগ্য</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="availability_status">প্রাপ্যতা</label>
                        <select id="availability_status" name="availability_status">
                            <option value="available" <?= $book['availability_status'] === 'available' ? 'selected' : '' ?>>উপলব্ধ</option>
                            <option value="reserved" <?= $book['availability_status'] === 'reserved' ? 'selected' : '' ?>>সংরক্ষিত</option>
                            <option value="inactive" <?= $book['availability_status'] === 'inactive' ? 'selected' : '' ?>>নিষ্ক্রিয়</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="cover_image">প্রচ্ছদ ছবি</label>
                        <input id="cover_image" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field">
                        <label for="university">বিশ্ববিদ্যালয় / ক্যাম্পাস</label>
                        <input id="university" type="text" name="university" value="<?= e($book['university']) ?>">
                    </div>
                    <div class="field full">
                        <label for="pickup_instructions">বই সংগ্রহের নির্দেশনা</label>
                        <textarea id="pickup_instructions" name="pickup_instructions"><?= e($book['pickup_instructions']) ?></textarea>
                    </div>
                    <div class="field full">
                        <label><input type="checkbox" name="is_active" <?= (int) $book['is_active'] === 1 ? 'checked' : '' ?>> বইটি সক্রিয় রাখুন</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">আপডেট করুন</button>
                </div>
            </form>

            <form method="post" action="<?= e(APP_BASE_URL . 'actions/book-actions.php') ?>" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_book">
                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
                <button type="submit" class="btn btn-danger" data-confirm="এই বইটি নিষ্ক্রিয় করবেন?">নিষ্ক্রিয় করুন</button>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
