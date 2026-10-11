<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
requireLogin();

$title = 'বই যোগ করুন';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / বই যোগ করুন</div>
            <h1>নতুন বই যোগ করুন</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="form-shell" method="post" action="<?= e(APP_BASE_URL . 'actions/book-actions.php') ?>" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_book">
                <div class="form-grid">
                    <div class="field">
                        <label for="title">বইয়ের নাম</label>
                        <input id="title" type="text" name="title" required>
                    </div>
                    <div class="field">
                        <label for="author">লেখকের নাম</label>
                        <input id="author" type="text" name="author" required>
                    </div>
                    <div class="field">
                        <label for="isbn">ISBN (ঐচ্ছিক)</label>
                        <input id="isbn" type="text" name="isbn" placeholder="978-...">
                    </div>
                    <div class="field">
                        <label for="category">বিষয় / বিভাগ</label>
                        <input id="category" type="text" name="category" required>
                    </div>
                    <div class="field full">
                        <label for="description">বই সম্পর্কে বিবরণ</label>
                        <textarea id="description" name="description" placeholder="বইয়ের সারাংশ ও ব্যবহারের তথ্য"></textarea>
                    </div>
                    <div class="field">
                        <label for="condition_status">বইয়ের অবস্থা</label>
                        <select id="condition_status" name="condition_status" required>
                            <option value="new">নতুন</option>
                            <option value="almost_new">প্রায় নতুন</option>
                            <option value="good">ভালো</option>
                            <option value="usable">ব্যবহারযোগ্য</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="cover_image">প্রচ্ছদ ছবি (ঐচ্ছিক)</label>
                        <input id="cover_image" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field">
                        <label for="university">বিশ্ববিদ্যালয় / ক্যাম্পাস</label>
                        <input id="university" type="text" name="university">
                    </div>
                    <div class="field full">
                        <label for="pickup_instructions">বই সংগ্রহের নির্দেশনা</label>
                        <textarea id="pickup_instructions" name="pickup_instructions" placeholder="কোথায়, কখন, কীভাবে বই সংগ্রহ করবেন"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">বইয়ের তালিকা প্রকাশ করুন</button>
                    <a href="<?= e(APP_BASE_URL . 'dashboard.php') ?>" class="btn btn-secondary">ড্যাশবোর্ড</a>
                </div>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
