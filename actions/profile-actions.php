<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'এই অপারেশনটি POST-এ করতে হবে।');
    redirect('dashboard.php');
}

requireLogin();

try {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $pdo = getDb();
    $userId = (int) $_SESSION['user_id'];

    if ($action === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $university = trim((string) ($_POST['university'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $contactPref = trim((string) ($_POST['contact_pref'] ?? 'email'));

        if ($name === '') {
            throw new InvalidArgumentException('নাম আবশ্যক।');
        }

        $current = $pdo->prepare('SELECT profile_image FROM users WHERE id = :id LIMIT 1');
        $current->execute([':id' => $userId]);
        $user = $current->fetch();

        $profileImage = $user['profile_image'] ?? null;
        if (!empty($_FILES['profile_image']['name'])) {
            $upload = normalizeImageUpload($_FILES['profile_image'] ?? []);
            if ($upload) {
                $profileImage = $upload;
            }
        }

        $pdo->prepare('UPDATE users SET name = :name, university = :university, department = :department, bio = :bio, contact_pref = :contact_pref, profile_image = :profile_image WHERE id = :id')->execute([
            ':name' => $name,
            ':university' => $university,
            ':department' => $department,
            ':bio' => $bio,
            ':contact_pref' => $contactPref,
            ':profile_image' => $profileImage,
            ':id' => $userId,
        ]);

        flash('success', 'প্রোফাইল আপডেট হয়েছে।');
        redirect('profile.php');
    }

    if ($action === 'update_password') {
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।');
        }

        $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute([
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $userId,
        ]);

        flash('success', 'পাসওয়ার্ড আপডেট হয়েছে।');
        redirect('profile.php');
    }

    if ($action === 'mark_notifications_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        flash('success', 'সব বিজ্ঞপ্তি পড়া হয়েছে।');
        redirect('notifications.php');
    }

    throw new InvalidArgumentException('অজানা প্রোফাইল কার্যক্রম।');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('profile.php');
}
