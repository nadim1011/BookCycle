<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'এই অপারেশনটি অনুরোধের মাধ্যমে করা আবশ্যক।');
    redirect('dashboard.php');
}

requireLogin();

try {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $pdo = getDb();
    $userId = (int) $_SESSION['user_id'];

    if ($action === 'create_book') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $author = trim((string) ($_POST['author'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $condition = trim((string) ($_POST['condition_status'] ?? ''));
        $pickup = trim((string) ($_POST['pickup_instructions'] ?? ''));
        $university = trim((string) ($_POST['university'] ?? ''));

        if ($title === '' || $author === '' || $category === '' || $condition === '') {
            throw new InvalidArgumentException('বইয়ের নাম, লেখক, বিভাগ ও অবস্থা আবশ্যক।');
        }

        $cover = normalizeImageUpload($_FILES['cover_image'] ?? []);

        $stmt = $pdo->prepare('INSERT INTO books (owner_id, title, author, isbn, category, description, condition_status, cover_image, university, pickup_instructions, availability_status, is_active) VALUES (:owner_id, :title, :author, :isbn, :category, :description, :condition_status, :cover_image, :university, :pickup_instructions, :availability_status, 1)');
        $stmt->execute([
            ':owner_id' => $userId,
            ':title' => $title,
            ':author' => $author,
            ':isbn' => trim((string) ($_POST['isbn'] ?? '')),
            ':category' => $category,
            ':description' => $description,
            ':condition_status' => $condition,
            ':cover_image' => $cover,
            ':university' => $university,
            ':pickup_instructions' => $pickup,
            ':availability_status' => 'available',
        ]);

        flash('success', 'বইটি সফলভাবে তালিকাভুক্ত হয়েছে।');
        redirect('dashboard.php');
    }

    if ($action === 'update_book') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $book = $pdo->prepare('SELECT * FROM books WHERE id = :id AND owner_id = :owner_id LIMIT 1');
        $book->execute([':id' => $bookId, ':owner_id' => $userId]);
        $existing = $book->fetch();

        if (!$existing) {
            throw new RuntimeException('আপনি এই বইটি সম্পাদনা করার অনুমতি পাননি।');
        }

        if ($existing['availability_status'] === 'borrowed') {
            throw new RuntimeException('ধার দেওয়া বইকে অস্থায়ীভাবে উপলব্ধ করে দেওয়া যাবে না।');
        }

        $cover = $existing['cover_image'];
        if (!empty($_FILES['cover_image']['name'])) {
            $uploaded = normalizeImageUpload($_FILES['cover_image'] ?? []);
            if ($uploaded) {
                $cover = $uploaded;
            }
        }

        $stmt = $pdo->prepare('UPDATE books SET title = :title, author = :author, isbn = :isbn, category = :category, description = :description, condition_status = :condition_status, university = :university, pickup_instructions = :pickup_instructions, cover_image = :cover_image, availability_status = :availability_status, is_active = :is_active WHERE id = :id AND owner_id = :owner_id');
        $stmt->execute([
            ':title' => trim((string) ($_POST['title'] ?? '')),
            ':author' => trim((string) ($_POST['author'] ?? '')),
            ':isbn' => trim((string) ($_POST['isbn'] ?? '')),
            ':category' => trim((string) ($_POST['category'] ?? '')),
            ':description' => trim((string) ($_POST['description'] ?? '')),
            ':condition_status' => trim((string) ($_POST['condition_status'] ?? '')),
            ':university' => trim((string) ($_POST['university'] ?? '')),
            ':pickup_instructions' => trim((string) ($_POST['pickup_instructions'] ?? '')),
            ':cover_image' => $cover,
            ':availability_status' => trim((string) ($_POST['availability_status'] ?? 'available')),
            ':is_active' => isset($_POST['is_active']) ? 1 : 0,
            ':id' => $bookId,
            ':owner_id' => $userId,
        ]);

        flash('success', 'বইয়ের তথ্য আপডেট হয়েছে।');
        redirect('dashboard.php');
    }

    if ($action === 'delete_book') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE books SET is_active = 0, availability_status = "inactive" WHERE id = :id AND owner_id = :owner_id');
        $stmt->execute([':id' => $bookId, ':owner_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('এই বইটি মুছে ফেলার অনুমতি আপনার নেই।');
        }

        flash('success', 'বইটি নিষ্ক্রিয় করা হয়েছে।');
        redirect('dashboard.php');
    }

    throw new InvalidArgumentException('অজানা বই কার্যক্রম।');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('dashboard.php');
}
