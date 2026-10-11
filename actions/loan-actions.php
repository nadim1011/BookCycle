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

    if ($action === 'confirm_handover') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['owner_id'] !== $userId) {
            throw new RuntimeException('আপনি এই হস্তান্তর নিশ্চিত করতে পারবেন না।');
        }

        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT * FROM books WHERE id = :id FOR UPDATE');
        $lock->execute([':id' => (int) $row['book_id']]);
        $book = $lock->fetch();
        if (!$book || $book['availability_status'] === 'borrowed') {
            $pdo->rollBack();
            throw new RuntimeException('এই বইটি ইতিমধ্যে অন্য কারও জন্য বরাদ্দ আছে।');
        }

        $exists = $pdo->prepare('SELECT id FROM loans WHERE request_id = :request_id LIMIT 1');
        $exists->execute([':request_id' => $requestId]);
        if ($exists->fetch()) {
            $pdo->rollBack();
            throw new RuntimeException('এই অনুরোধের জন্য ইতিমধ্যে লোন তৈরি হয়েছে।');
        }

        $pdo->prepare('INSERT INTO loans (request_id, book_id, owner_id, borrower_id, due_date, status, approved_at) VALUES (:request_id, :book_id, :owner_id, :borrower_id, :due_date, :status, NOW())')->execute([
            ':request_id' => $requestId,
            ':book_id' => (int) $row['book_id'],
            ':owner_id' => (int) $row['owner_id'],
            ':borrower_id' => (int) $row['requester_id'],
            ':due_date' => $row['due_date'],
            ':status' => 'active',
        ]);

        $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id')->execute([':status' => 'Borrowed', ':id' => $requestId]);
        $pdo->prepare('UPDATE books SET availability_status = :status WHERE id = :id')->execute([':status' => 'borrowed', ':id' => (int) $row['book_id']]);
        $pdo->commit();

        createNotification($pdo, (int) $row['requester_id'], 'handover', 'বইটি হস্তান্তর করা হয়েছে।', 'loan', (int) $pdo->lastInsertId());
        flash('success', 'বই হস্তান্তর নিশ্চিত হয়েছে।');
        redirect('borrow-requests.php');
    }

    if ($action === 'request_return') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['requester_id'] !== $userId) {
            throw new RuntimeException('আপনি ফেরতের অনুরোধ করতে পারবেন না।');
        }

        $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id')->execute([':status' => 'Return Requested', ':id' => $requestId]);
        createNotification($pdo, (int) $row['owner_id'], 'return_requested', 'বই ফেরত দেওয়ার অনুরোধ এসেছে।', 'loan', $requestId);
        flash('success', 'ফেরতের অনুরোধ পাঠানো হয়েছে।');
        redirect('borrow-requests.php');
    }

    if ($action === 'confirm_return') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['owner_id'] !== $userId) {
            throw new RuntimeException('আপনি ফেরত নিশ্চিত করতে পারবেন না।');
        }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id')->execute([':status' => 'Returned', ':id' => $requestId]);
        $pdo->prepare('UPDATE loans SET status = :status, returned_at = NOW() WHERE request_id = :request_id')->execute([':status' => 'returned', ':request_id' => $requestId]);
        $pdo->prepare('UPDATE books SET availability_status = :status WHERE id = :id')->execute([':status' => 'available', ':id' => (int) $row['book_id']]);
        $pdo->commit();

        createNotification($pdo, (int) $row['requester_id'], 'returned', 'বইটি ফেরত পাওয়া গেছে এবং আবার উপলব্ধ করা হয়েছে।', 'loan', $requestId);
        flash('success', 'ফেরত নিশ্চিত হয়েছে।');
        redirect('borrow-requests.php');
    }

    throw new InvalidArgumentException('অজানা লোন কার্যক্রম।');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('borrow-requests.php');
}
