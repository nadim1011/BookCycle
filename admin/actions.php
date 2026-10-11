<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'এই অপারেশনটি POST-এ করতে হবে।');
    redirect('admin/index.php');
}

requireAdmin();

$admin = currentUser();
$adminId = (int) ($admin['id'] ?? 0);
$pdo = getDb();

$logAction = static function (string $action, string $details) use ($pdo, $adminId): void {
    $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details) VALUES (:admin_id, :action, :details)');
    $stmt->execute([
        ':admin_id' => $adminId,
        ':action' => $action,
        ':details' => $details,
    ]);
};

try {
    verifyCsrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('সঠিক রেকর্ড নির্বাচন করুন।');
    }

    if ($action === 'toggle_user_active' || $action === 'toggle_user_role') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT role, is_active FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $target = $stmt->fetch();

        if (!$target) {
            throw new RuntimeException('ব্যবহারকারীকে খুঁজে পাওয়া যায়নি।');
        }
        if ($id === $adminId) {
            throw new RuntimeException('নিজের অ্যাকাউন্টের ভূমিকা বা অবস্থা পরিবর্তন করা যাবে না।');
        }

        if ($action === 'toggle_user_active') {
            $nextActive = (int) $target['is_active'] === 1 ? 0 : 1;
            if ($nextActive === 0 && $target['role'] === 'admin') {
                $activeAdmins = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin" AND is_active = 1')->fetchColumn();
                if ($activeAdmins <= 1) {
                    throw new RuntimeException('সর্বশেষ সক্রিয় অ্যাডমিনকে স্থগিত করা যাবে না।');
                }
            }

            $update = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
            $update->execute([':is_active' => $nextActive, ':id' => $id]);
            $logAction('user_status', 'User #' . $id . ' active=' . $nextActive);
        } else {
            $nextRole = $target['role'] === 'admin' ? 'student' : 'admin';
            if ($nextRole === 'student' && (int) $target['is_active'] === 1) {
                $activeAdmins = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin" AND is_active = 1')->fetchColumn();
                if ($activeAdmins <= 1) {
                    throw new RuntimeException('সর্বশেষ সক্রিয় অ্যাডমিনের ভূমিকা পরিবর্তন করা যাবে না।');
                }
            }

            $update = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $update->execute([':role' => $nextRole, ':id' => $id]);
            $logAction('user_role', 'User #' . $id . ' role=' . $nextRole);
        }

        $pdo->commit();
        flash('success', 'ব্যবহারকারীর তথ্য আপডেট হয়েছে।');
        redirect('admin/index.php#users');
    }

    if ($action === 'toggle_book') {
        $stmt = $pdo->prepare('SELECT is_active FROM books WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch();
        if (!$book) {
            throw new RuntimeException('বইটি খুঁজে পাওয়া যায়নি।');
        }

        $nextActive = (int) $book['is_active'] === 1 ? 0 : 1;
        $update = $pdo->prepare('UPDATE books SET is_active = :is_active WHERE id = :id');
        $update->execute([':is_active' => $nextActive, ':id' => $id]);
        $logAction('book_status', 'Book #' . $id . ' active=' . $nextActive);
        flash('success', 'বইয়ের প্রকাশনা-অবস্থা আপডেট হয়েছে।');
        redirect('admin/index.php#books');
    }

    if ($action === 'set_request_status') {
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['Rejected', 'Cancelled'], true)) {
            throw new InvalidArgumentException('এই অনুরোধের জন্য নির্বাচিত অবস্থা গ্রহণযোগ্য নয়।');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT status FROM borrow_requests WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch();
        if (!$request || !in_array($request['status'], ['Pending', 'Approved'], true)) {
            throw new RuntimeException('শুধু অপেক্ষমাণ বা অনুমোদিত অনুরোধ বাতিল করা যাবে।');
        }

        $loanCheck = $pdo->prepare('SELECT id FROM loans WHERE request_id = :request_id LIMIT 1');
        $loanCheck->execute([':request_id' => $id]);
        if ($loanCheck->fetch()) {
            throw new RuntimeException('এই অনুরোধের সঙ্গে লোন যুক্ত আছে; লোন ব্যবস্থাপনা থেকে ফেরত নিশ্চিত করুন।');
        }

        $update = $pdo->prepare('UPDATE borrow_requests SET status = :status WHERE id = :id');
        $update->execute([':status' => $status, ':id' => $id]);
        $logAction('request_status', 'Request #' . $id . ' status=' . $status);
        $pdo->commit();
        flash('success', 'ধার অনুরোধের অবস্থা আপডেট হয়েছে।');
        redirect('admin/index.php#requests');
    }

    if ($action === 'return_loan') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, request_id, book_id, status FROM loans WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $loan = $stmt->fetch();
        if (!$loan || !in_array($loan['status'], ['active', 'overdue'], true)) {
            throw new RuntimeException('শুধু সক্রিয় বা বিলম্বিত লোনের ফেরত নিশ্চিত করা যাবে।');
        }

        $updateLoan = $pdo->prepare('UPDATE loans SET status = "returned", returned_at = NOW() WHERE id = :id');
        $updateLoan->execute([':id' => $id]);
        $updateRequest = $pdo->prepare('UPDATE borrow_requests SET status = "Returned" WHERE id = :id');
        $updateRequest->execute([':id' => (int) $loan['request_id']]);
        $updateBook = $pdo->prepare('UPDATE books SET availability_status = CASE WHEN is_active = 1 THEN "available" ELSE "inactive" END WHERE id = :id');
        $updateBook->execute([':id' => (int) $loan['book_id']]);

        $logAction('loan_return', 'Loan #' . $id . ' marked returned by admin');
        $pdo->commit();
        flash('success', 'লোনটি ফেরত হয়েছে হিসেবে চিহ্নিত করা হয়েছে।');
        redirect('admin/index.php#loans');
    }

    if ($action === 'set_report_status') {
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['open', 'reviewed', 'resolved', 'rejected'], true)) {
            throw new InvalidArgumentException('অভিযোগের অবস্থা গ্রহণযোগ্য নয়।');
        }

        $stmt = $pdo->prepare('UPDATE reports SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM reports WHERE id = :id LIMIT 1');
            $exists->execute([':id' => $id]);
            if (!$exists->fetch()) {
                throw new RuntimeException('অভিযোগটি খুঁজে পাওয়া যায়নি।');
            }
        }

        $logAction('report_status', 'Report #' . $id . ' status=' . $status);
        flash('success', 'অভিযোগের অবস্থা আপডেট হয়েছে।');
        redirect('admin/index.php#reports');
    }

    throw new InvalidArgumentException('অজানা অ্যাডমিন কার্যক্রম।');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) {
        flash('error', $e->getMessage());
    } else {
        error_log('Admin action failed: ' . $e->getMessage());
        flash('error', 'অ্যাডমিন কার্যক্রমটি সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।');
    }
    redirect('admin/index.php');
}
