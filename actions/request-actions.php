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

    if ($action === 'request_book') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));

        if ($bookId <= 0 || $startDate === '' || $dueDate === '') {
            throw new InvalidArgumentException('শুরুর ও শেষের তারিখ আবশ্যক।');
        }

        $book = $pdo->prepare('SELECT * FROM books WHERE id = :id AND is_active = 1 LIMIT 1');
        $book->execute([':id' => $bookId]);
        $item = $book->fetch();

        if (!$item) {
            throw new RuntimeException('বইটি খুঁজে পাওয়া যায়নি।');
        }

        if ((int) $item['owner_id'] === $userId) {
            throw new RuntimeException('আপনি নিজের বই ধার নিতে পারবেন না।');
        }

        $existing = $pdo->prepare('SELECT id FROM borrow_requests WHERE book_id = :book_id AND requester_id = :requester_id AND status IN ("Pending","Approved","Borrowed","Return Requested") LIMIT 1');
        $existing->execute([':book_id' => $bookId, ':requester_id' => $userId]);
        if ($existing->fetch()) {
            throw new RuntimeException('আপনার ইতিমধ্যে এই বইয়ের অনুরোধ আছে।');
        }

        $stmt = $pdo->prepare('INSERT INTO borrow_requests (book_id, requester_id, owner_id, start_date, due_date, status, message) VALUES (:book_id, :requester_id, :owner_id, :start_date, :due_date, :status, :message)');
        $stmt->execute([
            ':book_id' => $bookId,
            ':requester_id' => $userId,
            ':owner_id' => (int) $item['owner_id'],
            ':start_date' => $startDate,
            ':due_date' => $dueDate,
            ':status' => 'Pending',
            ':message' => trim((string) ($_POST['message'] ?? '')),
        ]);

        $requestId = (int) $pdo->lastInsertId();
        createNotification($pdo, (int) $item['owner_id'], 'request', 'নতুন বই ধার নেওয়ার অনুরোধ এসেছে।', 'borrow_request', $requestId);

        flash('success', 'অনুরোধ পাঠানো হয়েছে।');
        redirect('borrow-requests.php');
    }

    if ($action === 'approve_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['owner_id'] !== $userId) {
            throw new RuntimeException('অনুমোদনের জন্য আপনি এই অনুরোধের মালিক নন।');
        }

        if ($row['status'] !== 'Pending') {
            throw new RuntimeException('এই অনুরোধটি এখন অনুমোদনের জন্য উপলব্ধ নয়।');
        }

        $stmt = $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id');
        $stmt->execute([':status' => 'Approved', ':id' => $requestId]);

        createNotification($pdo, (int) $row['requester_id'], 'approved', 'আপনার বই ধার নেওয়ার অনুরোধ গ্রহণ করা হয়েছে।', 'borrow_request', $requestId);
        flash('success', 'অনুরোধ গ্রহণ করা হয়েছে।');
        redirect('borrow-requests.php');
    }

    if ($action === 'reject_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['owner_id'] !== $userId) {
            throw new RuntimeException('এই অনুরোধটি প্রত্যাখ্যান করার অধিকার আপনার নেই।');
        }

        $stmt = $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id');
        $stmt->execute([':status' => 'Rejected', ':id' => $requestId]);

        createNotification($pdo, (int) $row['requester_id'], 'rejected', 'আপনার বই ধার নেওয়ার অনুরোধ প্রত্যাখ্যাত হয়েছে।', 'borrow_request', $requestId);
        flash('success', 'অনুরোধ প্রত্যাখ্যাত হয়েছে।');
        redirect('borrow-requests.php');
    }

    if ($action === 'cancel_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $request = $pdo->prepare('SELECT * FROM borrow_requests WHERE id = :id LIMIT 1');
        $request->execute([':id' => $requestId]);
        $row = $request->fetch();

        if (!$row || (int) $row['requester_id'] !== $userId) {
            throw new RuntimeException('আপনি এই অনুরোধ বাতিল করতে পারবেন না।');
        }

        if ($row['status'] !== 'Pending') {
            throw new RuntimeException('শুধু অপেক্ষমাণ অনুরোধই বাতিল করা যাবে।');
        }

        $stmt = $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id');
        $stmt->execute([':status' => 'Cancelled', ':id' => $requestId]);
        flash('success', 'অপেক্ষমাণ অনুরোধ বাতিল হয়েছে।');
        redirect('borrow-requests.php');
    }

    throw new InvalidArgumentException('অপারেশনটি গ্রহণযোগ্য নয়।');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('borrow-requests.php');
}
