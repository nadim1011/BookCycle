<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
requireLogin();

$pdo = getDb();
$user = currentUser();
$userId = (int) $user['id'];

$myBooks = $pdo->prepare('SELECT * FROM books WHERE owner_id = :owner_id ORDER BY created_at DESC');
$myBooks->execute([':owner_id' => $userId]);
$myBooks = $myBooks->fetchAll();

$incoming = $pdo->prepare('SELECT br.*, b.title, u.name AS requester_name FROM borrow_requests br LEFT JOIN books b ON b.id = br.book_id LEFT JOIN users u ON u.id = br.requester_id WHERE br.owner_id = :owner_id ORDER BY br.created_at DESC');
$incoming->execute([':owner_id' => $userId]);
$incoming = $incoming->fetchAll();

$outgoing = $pdo->prepare('SELECT br.*, b.title, u.name AS owner_name FROM borrow_requests br LEFT JOIN books b ON b.id = br.book_id LEFT JOIN users u ON u.id = br.owner_id WHERE br.requester_id = :requester_id ORDER BY br.created_at DESC');
$outgoing->execute([':requester_id' => $userId]);
$outgoing = $outgoing->fetchAll();

$borrowed = $pdo->prepare('SELECT l.*, b.title, u.name AS owner_name FROM loans l LEFT JOIN books b ON b.id = l.book_id LEFT JOIN users u ON u.id = l.owner_id WHERE l.borrower_id = :borrower_id ORDER BY l.created_at DESC');
$borrowed->execute([':borrower_id' => $userId]);
$borrowed = $borrowed->fetchAll();

$lent = $pdo->prepare('SELECT l.*, b.title, u.name AS borrower_name FROM loans l LEFT JOIN books b ON b.id = l.book_id LEFT JOIN users u ON u.id = l.borrower_id WHERE l.owner_id = :owner_id ORDER BY l.created_at DESC');
$lent->execute([':owner_id' => $userId]);
$lent = $lent->fetchAll();

$title = 'ড্যাশবোর্ড';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / ড্যাশবোর্ড</div>
            <h1>স্বাগতম, <?= e(userDisplayName($user)) ?>!</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid cards-4">
                <div class="card card-body">
                    <h3>আমার বই</h3>
                    <p><?= count($myBooks) ?>টি</p>
                </div>
                <div class="card card-body">
                    <h3>আগত অনুরোধ</h3>
                    <p><?= count($incoming) ?>টি</p>
                </div>
                <div class="card card-body">
                    <h3>প্রেরিত অনুরোধ</h3>
                    <p><?= count($outgoing) ?>টি</p>
                </div>
                <div class="card card-body">
                    <h3>সক্রিয় ধার</h3>
                    <p><?= count($borrowed) + count($lent) ?>টি</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>আমার বই</h2>
                <a class="btn btn-primary" href="<?= e(APP_BASE_URL . 'add-book.php') ?>">নতুন বই যোগ করুন</a>
            </div>

            <?php if (empty($myBooks)): ?>
                <div class="empty-state">আপনার এখনও কোনো বই নেই। প্রথম বইটি যোগ করে শুরু করুন।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>বই</th>
                                <th>বিভাগ</th>
                                <th>অবস্থা</th>
                                <th>প্রাপ্যতা</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myBooks as $book): ?>
                                <tr>
                                    <td><?= e($book['title']) ?></td>
                                    <td><?= e($book['category']) ?></td>
                                    <td><?= e(bookConditionLabel((string) $book['condition_status'])) ?></td>
                                    <td><?= e(bookAvailabilityLabel((string) $book['availability_status'])) ?></td>
                                    <td>
                                        <a href="<?= e(APP_BASE_URL . 'edit-book.php?id=' . (int) $book['id']) ?>" class="btn btn-secondary btn-small">সম্পাদনা</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>আগত অনুরোধ</h2>
            </div>
            <?php if (empty($incoming)): ?>
                <div class="empty-state">কোনো আগত অনুরোধ নেই।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>বই</th>
                                <th>অনুরোধকারী</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incoming as $request): ?>
                                <tr>
                                    <td><?= e($request['title']) ?></td>
                                    <td><?= e($request['requester_name']) ?></td>
                                    <td><?= e(formatDate($request['start_date'])) ?> → <?= e(formatDate($request['due_date'])) ?></td>
                                    <td><?= e(requestStatusLabel((string) $request['status'])) ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/request-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="approve_request">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-small">গ্রহণ</button>
                                            </form>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/request-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="reject_request">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-small" data-confirm="এই অনুরোধ প্রত্যাখ্যান করবেন?">প্রত্যাখ্যান</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-blue"><?= e(requestStatusLabel((string) $request['status'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>আমার পাঠানো অনুরোধ</h2>
            </div>
            <?php if (empty($outgoing)): ?>
                <div class="empty-state">আপনি এখনো কোনো অনুরোধ পাঠাননি।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>বই</th>
                                <th>দাতা</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outgoing as $request): ?>
                                <tr>
                                    <td><?= e($request['title']) ?></td>
                                    <td><?= e($request['owner_name']) ?></td>
                                    <td><?= e(formatDate($request['start_date'])) ?> → <?= e(formatDate($request['due_date'])) ?></td>
                                    <td>
                                        <?= e(requestStatusLabel((string) $request['status'])) ?>
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/request-actions.php') ?>" style="display:inline; margin-top:8px;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-small" data-confirm="এই অনুরোধ বাতিল করবেন?">বাতিল</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
