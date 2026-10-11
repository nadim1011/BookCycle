<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
requireLogin();

$pdo = getDb();
$userId = (int) $_SESSION['user_id'];

$notifications = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
$notifications->execute([':user_id' => $userId]);
$notifications = $notifications->fetchAll();

$title = 'বিজ্ঞপ্তি';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / বিজ্ঞপ্তি</div>
            <h1>বিজ্ঞপ্তি</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="form-actions" style="margin-bottom: 20px;">
                <form method="post" action="<?= e(APP_BASE_URL . 'actions/profile-actions.php') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="mark_notifications_read">
                    <button type="submit" class="btn btn-primary">সবগুলো পড়া হয়েছে</button>
                </form>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="empty-state">কোনো বিজ্ঞপ্তি নেই।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ধরন</th>
                                <th>বার্তা</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?= e(notificationTypeLabel((string) $notification['type'])) ?></td>
                                    <td><?= e($notification['message']) ?></td>
                                    <td><?= e($notification['created_at']) ?></td>
                                    <td>
                                        <?php if ((int) $notification['is_read'] === 1): ?>
                                            <span class="badge badge-green">পঠিত</span>
                                        <?php else: ?>
                                            <span class="badge badge-yellow">নতুন</span>
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
