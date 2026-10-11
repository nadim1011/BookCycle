<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$adminLogin = isset($_GET['admin']) && $_GET['admin'] === '1';

if (isLoggedIn()) {
    $signedInUser = currentUser();
    if (($signedInUser['role'] ?? '') === 'admin') {
        redirect('admin/index.php');
    }
    if (!$adminLogin) {
        redirect('dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('ইমেইল ও পাসওয়ার্ড দিন।');
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            throw new InvalidArgumentException('ইমেইল বা পাসওয়ার্ড সঠিক নয়।');
        }

        if (!empty($user['locked_until']) && $user['locked_until'] > date('Y-m-d H:i:s')) {
            throw new InvalidArgumentException('এই অ্যাকাউন্ট সাময়িকভাবে লক আছে। পরে আবার চেষ্টা করুন।');
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $failed = (int) $user['failed_login_attempts'] + 1;
            $lockUntil = $failed >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
            $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :failed, locked_until = :locked WHERE id = :id');
            $stmt->execute([':failed' => $failed, ':locked' => $lockUntil, ':id' => (int) $user['id']]);
            throw new InvalidArgumentException('ইমেইল বা পাসওয়ার্ড সঠিক নয়।');
        }

        if ($adminLogin && $user['role'] !== 'admin') {
            throw new InvalidArgumentException('এই অ্যাকাউন্টের অ্যাডমিন প্রবেশাধিকার নেই।');
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        session_regenerate_id(true);

        $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id')->execute([':id' => (int) $user['id']]);

        flash('success', 'স্বাগতম! আপনি সফলভাবে লগইন করেছেন।');
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$title = 'লগইন';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / <?= $adminLogin ? 'অ্যাডমিন লগইন' : 'লগইন' ?></div>
            <h1><?= $adminLogin ? 'অ্যাডমিন লগইন' : 'লগইন' ?></h1>
            <?php if ($adminLogin): ?>
                <p>এই প্রবেশদ্বার শুধু সক্রিয় অ্যাডমিন অ্যাকাউন্টের জন্য।</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="form-shell" method="post" action="<?= e(APP_BASE_URL . 'login.php' . ($adminLogin ? '?admin=1' : '')) ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="email">ইমেইল</label>
                        <input id="email" type="email" name="email" placeholder="you@example.com" required>
                    </div>
                    <div class="field full">
                        <label for="password">পাসওয়ার্ড</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input id="password" type="password" name="password" placeholder="পাসওয়ার্ড" required>
                            <button type="button" class="btn btn-secondary password-toggle" data-target="password">দেখুন</button>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">লগইন</button>
                    <?php if ($adminLogin): ?>
                        <a href="<?= e(APP_BASE_URL . 'login.php') ?>" class="btn btn-secondary">সাধারণ লগইন</a>
                    <?php else: ?>
                        <a href="<?= e(APP_BASE_URL . 'register.php') ?>" class="btn btn-secondary">নতুন অ্যাকাউন্ট</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
