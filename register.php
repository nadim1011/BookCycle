<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $university = trim((string) ($_POST['university'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));

        if ($name === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('নাম, ইমেইল ও পাসওয়ার্ড আবশ্যক।');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('কার্যকর ইমেইল দিন।');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('এই ইমেইল দিয়ে আগেই একটি অ্যাকাউন্ট আছে।');
        }

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, university, department, role) VALUES (:name, :email, :password_hash, :university, :department, :role)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':university' => $university,
            ':department' => $department,
            ':role' => 'student',
        ]);

        flash('success', 'সফলভাবে নিবন্ধন সম্পন্ন হয়েছে। এখন লগইন করুন।');
        redirect('login.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$title = 'নিবন্ধন';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / নিবন্ধন</div>
            <h1>নিবন্ধন</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="form-shell" method="post" action="<?= e(APP_BASE_URL . 'register.php') ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="field">
                        <label for="name">নাম</label>
                        <input id="name" type="text" name="name" placeholder="আপনার নাম" required>
                    </div>
                    <div class="field">
                        <label for="email">ইমেইল</label>
                        <input id="email" type="email" name="email" placeholder="you@example.com" required>
                    </div>
                    <div class="field">
                        <label for="password">পাসওয়ার্ড</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input id="password" type="password" name="password" placeholder="কমপক্ষে ৮ অক্ষর" required>
                            <button type="button" class="btn btn-secondary password-toggle" data-target="password">দেখুন</button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="university">বিশ্ববিদ্যালয়</label>
                        <input id="university" type="text" name="university" placeholder="বিশ্ববিদ্যালয়/ক্যাম্পাস">
                    </div>
                    <div class="field full">
                        <label for="department">বিভাগ</label>
                        <input id="department" type="text" name="department" placeholder="বিভাগ/মেজর">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">অ্যাকাউন্ট তৈরি করুন</button>
                    <a href="<?= e(APP_BASE_URL . 'login.php') ?>" class="btn btn-secondary">আগে লগইন করেছেন?</a>
                </div>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
