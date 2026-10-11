<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    $target = strpos($path, 'http') === 0 ? $path : APP_BASE_URL . ltrim($path, '/');
    header('Location: ' . $target);
    exit;
}

function flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        flash('error', 'দয়া করে আগে লগইন করুন।');
        redirect('login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();

    $user = currentUser();
    if (!$user || ($user['role'] ?? 'student') !== 'admin') {
        flash('error', 'এই পৃষ্ঠা দেখার অনুমতি আপনার নেই।');
        redirect('index.php');
    }
}

function userDisplayName(?array $user): string
{
    if (!$user) {
        return 'অজানা ব্যবহারকারী';
    }

    return trim((string) ($user['name'] ?? '')) !== '' ? (string) $user['name'] : 'ব্যবহারকারী';
}

function formatDate(string $date, string $fallback = 'নেই'): string
{
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return $fallback;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('d/m/Y', $timestamp);
}

function requestStatusLabel(string $status): string
{
    $labels = [
        'Pending' => 'অপেক্ষমাণ',
        'Approved' => 'অনুমোদিত',
        'Rejected' => 'প্রত্যাখ্যাত',
        'Borrowed' => 'ধার দেওয়া হয়েছে',
        'Return Requested' => 'ফেরতের অনুরোধ করা হয়েছে',
        'Returned' => 'ফেরত সম্পন্ন',
        'Cancelled' => 'বাতিল',
        'Overdue' => 'ফেরত দিতে দেরি হয়েছে',
    ];

    return $labels[$status] ?? $status;
}

function bookConditionLabel(string $value): string
{
    $labels = [
        'new' => 'নতুন',
        'almost_new' => 'প্রায় নতুন',
        'good' => 'ভালো',
        'usable' => 'ব্যবহারযোগ্য',
    ];

    return $labels[$value] ?? ucfirst($value);
}

function bookAvailabilityLabel(string $value): string
{
    $labels = [
        'available' => 'উপলব্ধ',
        'reserved' => 'সংরক্ষিত',
        'borrowed' => 'ধার দেওয়া হয়েছে',
        'inactive' => 'নিষ্ক্রিয়',
    ];

    return $labels[$value] ?? ucfirst($value);
}

function notificationTypeLabel(string $type): string
{
    $labels = [
        'request' => 'নতুন অনুরোধ',
        'approved' => 'অনুমোদিত',
        'rejected' => 'প্রত্যাখ্যাত',
        'handover' => 'হস্তান্তর নিশ্চিত',
        'return_requested' => 'ফেরতের অনুরোধ',
        'returned' => 'ফেরত নিশ্চিত',
        'due_soon' => 'সময়সীমার কাছাকাছি',
        'overdue' => 'ফেরত দিতে দেরি',
    ];

    return $labels[$type] ?? ucfirst($type);
}

function normalizeImageUpload(array $file): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        return null;
    }

    $ext = match ($mime) {
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
        default => '.jpg',
    };

    $folder = __DIR__ . '/../uploads/books';
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $filename = 'book_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
    $destination = $folder . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'uploads/books/' . $filename;
}

function createNotification(PDO $pdo, int $userId, string $type, string $message, ?string $relatedType = null, ?int $relatedId = null): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, message, related_type, related_id) VALUES (:user_id, :type, :message, :related_type, :related_id)');
    $stmt->execute([
        ':user_id' => $userId,
        ':type' => $type,
        ':message' => $message,
        ':related_type' => $relatedType,
        ':related_id' => $relatedId,
    ]);
}
