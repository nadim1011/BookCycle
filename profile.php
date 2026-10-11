<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$currentUser = currentUser();
$requestedUserId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$profileUserId = $requestedUserId ?: (int) ($currentUser['id'] ?? 0);

if ($profileUserId < 1) {
    requireLogin();
    redirect('login.php');
}

$isOwnProfile = $currentUser && (int) $currentUser['id'] === $profileUserId;
$isPublicProfile = !$isOwnProfile;

if ($isOwnProfile) {
    $profileUser = $currentUser;
} else {
    $profileQuery = getDb()->prepare('SELECT id, name, university, department, bio, profile_image, created_at FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $profileQuery->execute([':id' => $profileUserId]);
    $profileUser = $profileQuery->fetch();
}

if (!$profileUser) {
    http_response_code(404);
    $title = 'প্রোফাইল পাওয়া যায়নি';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main>
        <section class="page-head">
            <div class="container">
                <div class="breadcrumbs">হোম / প্রোফাইল</div>
                <h1>প্রোফাইল পাওয়া যায়নি</h1>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="empty-state">এই সদস্যের প্রোফাইলটি পাওয়া যায়নি বা বর্তমানে উপলব্ধ নয়।</div>
            </div>
        </section>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$title = $isPublicProfile ? userDisplayName($profileUser) . ' - প্রোফাইল' : 'আমার প্রোফাইল';
$profileImage = trim((string) ($profileUser['profile_image'] ?? ''));
$profileBookCount = 0;
$bookCountQuery = getDb()->prepare('SELECT COUNT(*) FROM books WHERE owner_id = :owner_id AND is_active = 1');
$bookCountQuery->execute([':owner_id' => $profileUserId]);
$profileBookCount = (int) $bookCountQuery->fetchColumn();

$publicBooks = [];
if ($isPublicProfile) {
    $booksQuery = getDb()->prepare('SELECT id, title, author, category, cover_image, condition_status, availability_status FROM books WHERE owner_id = :owner_id AND is_active = 1 ORDER BY created_at DESC');
    $booksQuery->execute([':owner_id' => $profileUserId]);
    $publicBooks = $booksQuery->fetchAll();
}
require_once __DIR__ . '/includes/header.php';
?>

<main class="profile-page">
    <section class="page-head profile-page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / <?= $isPublicProfile ? 'সদস্য প্রোফাইল' : 'আমার প্রোফাইল' ?></div>
            <p class="profile-eyebrow"><?= $isPublicProfile ? 'বুকসাইকেল সদস্য' : 'আপনার বুকসাইকেল পরিচয়' ?></p>
            <h1><?= $isPublicProfile ? e(userDisplayName($profileUser)) . ' এর প্রোফাইল' : 'আমার প্রোফাইল' ?></h1>
            <p class="profile-intro"><?= $isPublicProfile ? 'এই সদস্যের পরিচিতি এবং শেয়ার করা বইগুলো দেখুন।' : 'আপনার পরিচয়কে নিজের মতো সাজান, আর বই ভাগাভাগির গল্প এগিয়ে নিন।' ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container profile-layout">
            <?php if ($isPublicProfile): ?>
                <aside class="profile-card">
                    <div class="profile-card-pattern" aria-hidden="true"></div>
                    <div class="profile-avatar-wrap">
                        <span class="profile-avatar-fallback" id="public-profile-avatar-fallback" <?= $profileImage !== '' ? 'hidden' : '' ?>>ব</span>
                        <?php if ($profileImage !== ''): ?>
                            <img class="profile-avatar" src="<?= e(APP_BASE_URL . ltrim($profileImage, '/')) ?>" alt="<?= e(userDisplayName($profileUser)) ?>-এর প্রোফাইল ছবি" data-fallback-element="public-profile-avatar-fallback" onerror="this.onerror=null;this.hidden=true;document.getElementById(this.dataset.fallbackElement).hidden=false">
                        <?php endif; ?>
                    </div>
                    <span class="profile-card-label">বুকসাইকেল সদস্য</span>
                    <h2 class="profile-card-name"><?= e(userDisplayName($profileUser)) ?></h2>
                    <p class="profile-card-school"><?= e(trim((string) ($profileUser['university'] ?? '')) ?: 'বিশ্ববিদ্যালয়ের তথ্য যোগ করা হয়নি') ?></p>
                    <?php if (trim((string) ($profileUser['department'] ?? '')) !== ''): ?>
                        <p class="profile-card-department"><?= e($profileUser['department']) ?></p>
                    <?php endif; ?>
                    <div class="profile-member-id">
                        <span>ইউনিক মেম্বার আইডি</span>
                        <strong>BC-<?= e(str_pad((string) $profileUser['id'], 6, '0', STR_PAD_LEFT)) ?></strong>
                    </div>
                    <div class="profile-card-bio">
                        <span>আমার কথা</span>
                        <p><?= e(trim((string) ($profileUser['bio'] ?? '')) ?: 'এই সদস্য এখনো পরিচিতি যোগ করেননি।') ?></p>
                    </div>
                    <p class="profile-member-since">
                        বুকসাইকেলে যোগ দিয়েছেন
                        <strong><?= e(formatDate((string) ($profileUser['created_at'] ?? ''), 'তারিখ নেই')) ?></strong>
                    </p>
                </aside>

                <div class="profile-settings">
                    <div class="profile-stat-grid" aria-label="Profile summary">
                        <div class="profile-stat-card">
                            <span>শেয়ার করা বই</span>
                            <strong><?= $profileBookCount ?></strong>
                        </div>
                        <div class="profile-stat-card">
                            <span>সদস্য পরিচয়</span>
                            <strong>বুকসাইকেল সদস্য</strong>
                        </div>
                        <div class="profile-stat-card">
                            <span>যোগদানের তারিখ</span>
                            <strong><?= e(formatDate((string) ($profileUser['created_at'] ?? ''), 'তারিখ নেই')) ?></strong>
                        </div>
                    </div>
                    <section class="panel profile-settings-card">
                        <div class="profile-section-heading">
                            <div>
                                <span class="profile-section-kicker">এই সদস্যের সংগ্রহ</span>
                                <h2>শেয়ার করা বই <span class="profile-book-count"><?= $profileBookCount ?></span></h2>
                            </div>
                        </div>
                        <?php if (empty($publicBooks)): ?>
                            <div class="empty-state">এই সদস্য এখনো কোনো বই শেয়ার করেননি।</div>
                        <?php else: ?>
                            <div class="grid cards-2 profile-books">
                                <?php foreach ($publicBooks as $book): ?>
                                    <?php
                                    $coverImage = trim((string) ($book['cover_image'] ?? ''));
                                    $coverImageUrl = APP_BASE_URL . ($coverImage !== '' ? ltrim($coverImage, '/') : 'assets/images/default-book.svg');
                                    ?>
                                    <article class="card">
                                        <img class="card-image" src="<?= e($coverImageUrl) ?>" alt="<?= e($book['title']) ?>" data-fallback="<?= e(APP_BASE_URL . 'assets/images/default-book.svg') ?>" onerror="this.onerror=null;this.src=this.dataset.fallback">
                                        <div class="card-body">
                                            <span class="pill"><?= e($book['category']) ?></span>
                                            <h3><?= e($book['title']) ?></h3>
                                            <p><?= e($book['author']) ?></p>
                                            <div class="meta-item">
                                                <span>প্রাপ্যতা</span>
                                                <strong><?= e(bookAvailabilityLabel((string) $book['availability_status'])) ?></strong>
                                            </div>
                                            <a class="btn btn-secondary" href="<?= e(APP_BASE_URL . 'book-details.php?id=' . (int) $book['id']) ?>">বইয়ের বিস্তারিত</a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php else: ?>
            <aside class="profile-card">
                <div class="profile-card-pattern" aria-hidden="true"></div>
                <div class="profile-avatar-wrap">
                    <span class="profile-avatar-fallback" id="profile-avatar-fallback" <?= $profileImage !== '' ? 'hidden' : '' ?>>ব</span>
                    <img
                        class="profile-avatar"
                        id="profile-avatar-preview"
                        src="<?= $profileImage !== '' ? e(APP_BASE_URL . ltrim($profileImage, '/')) : '' ?>"
                        alt="<?= e(userDisplayName($profileUser)) ?>-এর প্রোফাইল ছবি"
                        <?= $profileImage === '' ? 'hidden' : '' ?>
                        data-fallback-element="profile-avatar-fallback"
                        onerror="this.onerror=null;this.hidden=true;document.getElementById(this.dataset.fallbackElement).hidden=false"
                    >
                </div>
                <span class="profile-card-label">বুকসাইকেল সদস্য</span>
                <h2 class="profile-card-name"><?= e(userDisplayName($profileUser)) ?></h2>
                <p class="profile-card-school">
                    <?= e(trim((string) ($profileUser['university'] ?? '')) ?: 'বিশ্ববিদ্যালয়ের নাম যোগ করুন') ?>
                </p>
                <?php if (trim((string) ($profileUser['department'] ?? '')) !== ''): ?>
                    <p class="profile-card-department"><?= e($profileUser['department']) ?></p>
                <?php endif; ?>

                <div class="profile-member-id">
                    <span>আপনার ইউনিক মেম্বার আইডি</span>
                    <strong>BC-<?= e(str_pad((string) $profileUser['id'], 6, '0', STR_PAD_LEFT)) ?></strong>
                </div>

                <div class="profile-share">
                    <span>আপনার পাবলিক প্রোফাইল লিংক</span>
                    <a href="<?= e(APP_BASE_URL . 'profile.php?id=' . (int) $profileUser['id']) ?>"><?= e(APP_BASE_URL . 'profile.php?id=' . (int) $profileUser['id']) ?></a>
                </div>

                <div class="profile-card-bio">
                    <span>আমার কথা</span>
                    <p><?= e(trim((string) ($profileUser['bio'] ?? '')) ?: 'আপনার সম্পর্কে ছোট্ট কিছু লিখুন—আপনার প্রোফাইলকে আরও ব্যক্তিগত করে তুলুন।') ?></p>
                </div>

                <p class="profile-member-since">
                    বুকসাইকেলে যোগ দিয়েছেন
                    <strong><?= e(formatDate((string) ($profileUser['created_at'] ?? ''), 'তারিখ নেই')) ?></strong>
                </p>
            </aside>

            <div class="profile-settings">
                <div class="profile-stat-grid" aria-label="Profile summary">
                    <div class="profile-stat-card">
                        <span>শেয়ার করা বই</span>
                        <strong><?= $profileBookCount ?></strong>
                    </div>
                    <div class="profile-stat-card">
                        <span>প্রোফাইল অবস্থা</span>
                        <strong><?= $isOwnProfile ? 'ব্যক্তিগত' : 'সদস্য' ?></strong>
                    </div>
                    <div class="profile-stat-card">
                        <span>যোগদানের তারিখ</span>
                        <strong><?= e(formatDate((string) ($profileUser['created_at'] ?? ''), 'তারিখ নেই')) ?></strong>
                    </div>
                </div>

                <section class="panel profile-settings-card">
                    <div class="profile-section-heading">
                        <div>
                            <span class="profile-section-kicker">আপনার পরিচিতি</span>
                            <h2>প্রোফাইল তথ্য</h2>
                        </div>
                        <span class="profile-section-mark" aria-hidden="true">01</span>
                    </div>

                    <form method="post" action="<?= e(APP_BASE_URL . 'actions/profile-actions.php') ?>" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-grid">
                            <div class="field">
                                <label for="name">নাম</label>
                                <input id="name" type="text" name="name" value="<?= e($profileUser['name']) ?>" required>
                            </div>
                            <div class="field">
                                <label for="email">ইমেইল</label>
                                <input id="email" type="email" value="<?= e($profileUser['email']) ?>" disabled>
                            </div>
                            <div class="field">
                                <label for="university">বিশ্ববিদ্যালয়</label>
                                <input id="university" type="text" name="university" value="<?= e($profileUser['university']) ?>">
                            </div>
                            <div class="field">
                                <label for="department">বিভাগ</label>
                                <input id="department" type="text" name="department" value="<?= e($profileUser['department']) ?>">
                            </div>
                            <div class="field full">
                                <label for="bio">সংক্ষিপ্ত পরিচিতি</label>
                                <textarea id="bio" name="bio" placeholder="আপনার পড়াশোনা, আগ্রহ বা বই পড়ার অভ্যাস সম্পর্কে লিখুন"><?= e($profileUser['bio']) ?></textarea>
                            </div>
                            <div class="field">
                                <label for="contact_pref">যোগাযোগের পছন্দ</label>
                                <select id="contact_pref" name="contact_pref">
                                    <option value="email" <?= ($profileUser['contact_pref'] ?? 'email') === 'email' ? 'selected' : '' ?>>ইমেইল</option>
                                    <option value="phone" <?= ($profileUser['contact_pref'] ?? '') === 'phone' ? 'selected' : '' ?>>ফোন</option>
                                    <option value="both" <?= ($profileUser['contact_pref'] ?? '') === 'both' ? 'selected' : '' ?>>দুইটি</option>
                                    <option value="none" <?= ($profileUser['contact_pref'] ?? '') === 'none' ? 'selected' : '' ?>>কোনোটিই নয়</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="profile_image">প্রোফাইল ছবি</label>
                                <input
                                    id="profile_image"
                                    type="file"
                                    name="profile_image"
                                    accept="image/jpeg,image/png,image/webp"
                                    data-preview="profile-avatar-preview"
                                    data-preview-fallback="profile-avatar-fallback"
                                >
                                <small class="profile-field-hint">JPG, PNG অথবা WebP ছবি ব্যবহার করুন।</small>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">প্রোফাইল সংরক্ষণ করুন</button>
                        </div>
                    </form>
                </section>

                <section class="panel profile-settings-card profile-password-card">
                    <div class="profile-section-heading">
                        <div>
                            <span class="profile-section-kicker">অ্যাকাউন্ট সুরক্ষা</span>
                            <h2>পাসওয়ার্ড পরিবর্তন</h2>
                        </div>
                        <span class="profile-section-mark" aria-hidden="true">02</span>
                    </div>
                    <p class="profile-password-note">নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।</p>
                    <form method="post" action="<?= e(APP_BASE_URL . 'actions/profile-actions.php') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_password">
                        <div class="field">
                            <label for="password">নতুন পাসওয়ার্ড</label>
                            <div class="profile-password-input">
                                <input id="password" type="password" name="password" placeholder="কমপক্ষে ৮ অক্ষর">
                                <button type="button" class="btn btn-secondary password-toggle" data-target="password">দেখুন</button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-secondary">পাসওয়ার্ড আপডেট করুন</button>
                        </div>
                    </form>
                </section>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
