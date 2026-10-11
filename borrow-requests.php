<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
requireLogin();

$pdo = getDb();
$userId = (int) $_SESSION['user_id'];

$incoming = $pdo->prepare('SELECT br.*, b.title, u.name AS requester_name FROM borrow_requests br LEFT JOIN books b ON b.id = br.book_id LEFT JOIN users u ON u.id = br.requester_id WHERE br.owner_id = :owner_id ORDER BY br.created_at DESC');
$incoming->execute([':owner_id' => $userId]);
$incoming = $incoming->fetchAll();

$outgoing = $pdo->prepare('SELECT br.*, b.title, u.name AS owner_name FROM borrow_requests br LEFT JOIN books b ON b.id = br.book_id LEFT JOIN users u ON u.id = br.owner_id WHERE br.requester_id = :requester_id ORDER BY br.created_at DESC');
$outgoing->execute([':requester_id' => $userId]);
$outgoing = $outgoing->fetchAll();

$title = 'অনুরোধ ব্যবস্থাপনা';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / অনুরোধ</div>
            <h1>ধার নেওয়ার অনুরোধ</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>আমার কাছে আসা অনুরোধ</h2>
            </div>
            <?php if (empty($incoming)): ?>
                <div class="empty-state">এখনো কোনো অনুরোধ আসেনি।</div>
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
                                        <?php elseif ($request['status'] === 'Approved'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/loan-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="confirm_handover">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-small">হস্তান্তর নিশ্চিত</button>
                                            </form>
                                        <?php elseif ($request['status'] === 'Return Requested'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/loan-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="confirm_return">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-small">ফেরত নিশ্চিত</button>
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
                <div class="empty-state">আপনার পাঠানো কোনো অনুরোধ নেই।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>বই</th>
                                <th>দাতা</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outgoing as $request): ?>
                                <tr>
                                    <td><?= e($request['title']) ?></td>
                                    <td><?= e($request['owner_name']) ?></td>
                                    <td><?= e(formatDate($request['start_date'])) ?> → <?= e(formatDate($request['due_date'])) ?></td>
                                    <td><?= e(requestStatusLabel((string) $request['status'])) ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/request-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-small" data-confirm="এই অনুরোধ বাতিল করবেন?">বাতিল</button>
                                            </form>
                                        <?php elseif ($request['status'] === 'Borrowed'): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'actions/loan-actions.php') ?>" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="request_return">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-small">ফেরতের অনুরোধ</button>
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
