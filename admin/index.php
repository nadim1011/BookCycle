<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pdo = getDb();
$studentCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$bookCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$activeBookCount = (int) $pdo->query('SELECT COUNT(*) FROM books WHERE is_active = 1')->fetchColumn();
$pendingRequestCount = (int) $pdo->query('SELECT COUNT(*) FROM borrow_requests WHERE status IN ("Pending", "Approved", "Borrowed", "Return Requested")')->fetchColumn();
$activeLoans = (int) $pdo->query('SELECT COUNT(*) FROM loans WHERE status IN ("active", "overdue")')->fetchColumn();
$overdue = (int) $pdo->query('SELECT COUNT(*) FROM loans WHERE status IN ("active", "overdue") AND due_date < CURDATE()')->fetchColumn();
$openReports = (int) $pdo->query('SELECT COUNT(*) FROM reports WHERE status = "open"')->fetchColumn();

$users = $pdo->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$books = $pdo->query('SELECT b.id, b.title, b.author, b.category, b.availability_status, b.is_active, u.name AS owner_name FROM books b LEFT JOIN users u ON u.id = b.owner_id ORDER BY b.created_at DESC')->fetchAll();
$requests = $pdo->query('SELECT r.id, r.status, r.start_date, r.due_date, r.created_at, b.title AS book_title, requester.name AS requester_name, owner.name AS owner_name FROM borrow_requests r LEFT JOIN books b ON b.id = r.book_id LEFT JOIN users requester ON requester.id = r.requester_id LEFT JOIN users owner ON owner.id = r.owner_id ORDER BY r.created_at DESC')->fetchAll();
$loans = $pdo->query('SELECT l.id, l.request_id, l.status, l.due_date, l.approved_at, b.title AS book_title, owner.name AS owner_name, borrower.name AS borrower_name FROM loans l LEFT JOIN books b ON b.id = l.book_id LEFT JOIN users owner ON owner.id = l.owner_id LEFT JOIN users borrower ON borrower.id = l.borrower_id ORDER BY l.created_at DESC')->fetchAll();
$reports = $pdo->query('SELECT r.id, r.reason, r.details, r.status, r.created_at, reporter.name AS reporter_name, reported_user.name AS reported_user_name, b.title AS reported_book_title FROM reports r LEFT JOIN users reporter ON reporter.id = r.reporter_id LEFT JOIN users reported_user ON reported_user.id = r.reported_user_id LEFT JOIN books b ON b.id = r.reported_book_id ORDER BY r.created_at DESC')->fetchAll();
$adminLogs = $pdo->query('SELECT l.action, l.details, l.created_at, u.name AS admin_name FROM admin_logs l LEFT JOIN users u ON u.id = l.admin_id ORDER BY l.created_at DESC LIMIT 30')->fetchAll();
$currentAdminId = (int) ($_SESSION['user_id'] ?? 0);

$title = 'অ্যাডমিন কন্ট্রোল প্যানেল';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="admin-page">
    <section class="page-head admin-page-head">
        <div class="container">
            <div class="breadcrumbs">হোম / অ্যাডমিন</div>
            <p class="profile-eyebrow">BookCycle Control Center</p>
            <h1>অ্যাডমিন কন্ট্রোল প্যানেল</h1>
            <p class="profile-intro">সদস্য, বই, ধার-লেনদেন এবং অভিযোগ এক জায়গা থেকে পরিচালনা করুন।</p>
            <nav class="admin-shortcuts" aria-label="অ্যাডমিন বিভাগ">
                <a href="#users">সদস্য</a>
                <a href="#books">বই</a>
                <a href="#requests">অনুরোধ</a>
                <a href="#loans">লোন</a>
                <a href="#reports">অভিযোগ</a>
                <a href="#activity">অ্যাক্টিভিটি</a>
            </nav>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid admin-stat-grid">
                <article class="admin-stat"><span>সদস্য</span><strong><?= e((string) $studentCount) ?></strong></article>
                <article class="admin-stat"><span>সক্রিয় বই</span><strong><?= e((string) $activeBookCount) ?><small> / <?= e((string) $bookCount) ?></small></strong></article>
                <article class="admin-stat"><span>চলমান অনুরোধ</span><strong><?= e((string) $pendingRequestCount) ?></strong></article>
                <article class="admin-stat"><span>সক্রিয় লোন</span><strong><?= e((string) $activeLoans) ?><small> · বিলম্বিত <?= e((string) $overdue) ?></small></strong></article>
                <article class="admin-stat"><span>খোলা অভিযোগ</span><strong><?= e((string) $openReports) ?></strong></article>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="users">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">অ্যাকাউন্ট ও প্রবেশাধিকার</span><h2>সদস্য ব্যবস্থাপনা</h2></div></div>
            <div class="panel admin-table-panel table-wrap">
                <table>
                    <thead><tr><th>সদস্য</th><th>ভূমিকা</th><th>অবস্থা</th><th>যোগদান</th><th>নিয়ন্ত্রণ</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $member): ?>
                        <tr>
                            <td><strong><?= e($member['name']) ?></strong><br><small><?= e($member['email']) ?></small></td>
                            <td><?= $member['role'] === 'admin' ? 'অ্যাডমিন' : 'সদস্য' ?></td>
                            <td><?= (int) $member['is_active'] === 1 ? '<span class="badge badge-green">সক্রিয়</span>' : '<span class="badge badge-red">স্থগিত</span>' ?></td>
                            <td><?= e(formatDate((string) $member['created_at'])) ?></td>
                            <td>
                                <?php if ((int) $member['id'] === $currentAdminId): ?>
                                    <span class="admin-self-note">আপনার অ্যাকাউন্ট</span>
                                <?php else: ?>
                                    <div class="admin-row-actions">
                                        <form method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_user_active">
                                            <button class="btn btn-small <?= (int) $member['is_active'] === 1 ? 'btn-danger' : 'btn-secondary' ?>" type="submit"><?= (int) $member['is_active'] === 1 ? 'স্থগিত' : 'সক্রিয় করুন' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_user_role">
                                            <button class="btn btn-small btn-secondary" type="submit" data-confirm="এই সদস্যের অ্যাডমিন ভূমিকা পরিবর্তন করবেন?"><?= $member['role'] === 'admin' ? 'সদস্য করুন' : 'অ্যাডমিন করুন' ?></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?><tr><td colspan="5">কোনো সদস্য নেই।</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="books">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">বইয়ের তালিকা</span><h2>বই ব্যবস্থাপনা</h2></div></div>
            <div class="panel admin-table-panel table-wrap">
                <table>
                    <thead><tr><th>বই</th><th>দাতা</th><th>প্রাপ্যতা</th><th>প্রকাশনা</th><th>নিয়ন্ত্রণ</th></tr></thead>
                    <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><strong><?= e($book['title']) ?></strong><br><small><?= e($book['author']) ?> · <?= e($book['category']) ?></small></td>
                            <td><?= e($book['owner_name'] ?? 'অজানা') ?></td>
                            <td><?= e(bookAvailabilityLabel((string) $book['availability_status'])) ?></td>
                            <td><?= (int) $book['is_active'] === 1 ? '<span class="badge badge-green">প্রকাশিত</span>' : '<span class="badge badge-red">লুকানো</span>' ?></td>
                            <td>
                                <form method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                    <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_book">
                                    <button class="btn btn-small <?= (int) $book['is_active'] === 1 ? 'btn-danger' : 'btn-secondary' ?>" type="submit"><?= (int) $book['is_active'] === 1 ? 'লুকান' : 'প্রকাশ করুন' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$books): ?><tr><td colspan="5">কোনো বই নেই।</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="requests">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">বই ধার প্রক্রিয়া</span><h2>ধার অনুরোধ</h2></div></div>
            <div class="panel admin-table-panel table-wrap">
                <table>
                    <thead><tr><th>বই</th><th>অনুরোধকারী / দাতা</th><th>সময়সীমা</th><th>অবস্থা</th><th>নিয়ন্ত্রণ</th></tr></thead>
                    <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><strong><?= e($request['book_title'] ?? 'বই পাওয়া যায়নি') ?></strong><br><small>#<?= (int) $request['id'] ?></small></td>
                            <td><?= e($request['requester_name'] ?? 'অজানা') ?><br><small>দাতা: <?= e($request['owner_name'] ?? 'অজানা') ?></small></td>
                            <td><?= e(formatDate((string) $request['start_date'])) ?> – <?= e(formatDate((string) $request['due_date'])) ?></td>
                            <td><?= e(requestStatusLabel((string) $request['status'])) ?></td>
                            <td>
                                <?php if (in_array($request['status'], ['Pending', 'Approved'], true)): ?>
                                    <div class="admin-row-actions">
                                        <?php foreach (['Rejected' => 'প্রত্যাখ্যান', 'Cancelled' => 'বাতিল'] as $status => $label): ?>
                                            <form method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                                <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                                <input type="hidden" name="action" value="set_request_status"><input type="hidden" name="status" value="<?= e($status) ?>">
                                                <button class="btn btn-small btn-secondary" type="submit"><?= e($label) ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?><span class="admin-self-note">কোনো পদক্ষেপ নেই</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$requests): ?><tr><td colspan="5">কোনো ধার অনুরোধ নেই।</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="loans">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">চলমান ও সম্পন্ন লেনদেন</span><h2>লোন ব্যবস্থাপনা</h2></div></div>
            <div class="panel admin-table-panel table-wrap">
                <table>
                    <thead><tr><th>বই</th><th>দাতা / গ্রহীতা</th><th>ফেরতের তারিখ</th><th>অবস্থা</th><th>নিয়ন্ত্রণ</th></tr></thead>
                    <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><strong><?= e($loan['book_title'] ?? 'বই পাওয়া যায়নি') ?></strong><br><small>লোন #<?= (int) $loan['id'] ?></small></td>
                            <td><?= e($loan['owner_name'] ?? 'অজানা') ?><br><small>গ্রহীতা: <?= e($loan['borrower_name'] ?? 'অজানা') ?></small></td>
                            <td><?= e(formatDate((string) $loan['due_date'])) ?></td>
                            <td><?= e((string) $loan['status']) ?><?= in_array($loan['status'], ['active', 'overdue'], true) && $loan['due_date'] < date('Y-m-d') ? ' · বিলম্বিত' : '' ?></td>
                            <td>
                                <?php if (in_array($loan['status'], ['active', 'overdue'], true)): ?>
                                    <form method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                        <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
                                        <input type="hidden" name="action" value="return_loan">
                                        <button class="btn btn-small btn-secondary" type="submit" data-confirm="এই লোনটি ফেরত হয়েছে হিসেবে চিহ্নিত করবেন?">ফেরত নিশ্চিত</button>
                                    </form>
                                <?php else: ?><span class="admin-self-note">সম্পন্ন</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$loans): ?><tr><td colspan="5">কোনো লোন নেই।</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="reports">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">সদস্যদের পাঠানো রিপোর্ট</span><h2>অভিযোগ ব্যবস্থাপনা</h2></div></div>
            <div class="panel admin-table-panel table-wrap">
                <table>
                    <thead><tr><th>অভিযোগ</th><th>প্রতিবেদক</th><th>যার/যে বিষয়ে</th><th>অবস্থা ও নিয়ন্ত্রণ</th></tr></thead>
                    <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><strong><?= e($report['reason']) ?></strong><br><small><?= e($report['details'] ?? '') ?></small></td>
                            <td><?= e($report['reporter_name'] ?? 'অজানা') ?></td>
                            <td><?= e($report['reported_user_name'] ?? $report['reported_book_title'] ?? 'নির্দিষ্ট নয়') ?></td>
                            <td>
                                <form class="admin-row-actions" method="post" action="<?= e(APP_BASE_URL . 'admin/actions.php') ?>">
                                    <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $report['id'] ?>">
                                    <input type="hidden" name="action" value="set_report_status">
                                    <select name="status" aria-label="অভিযোগের অবস্থা">
                                        <?php foreach (['open' => 'খোলা', 'reviewed' => 'পর্যালোচিত', 'resolved' => 'সমাধান', 'rejected' => 'প্রত্যাখ্যাত'] as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= $report['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-small btn-secondary" type="submit">সংরক্ষণ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reports): ?><tr><td colspan="4">কোনো অভিযোগ নেই।</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section admin-section" id="activity">
        <div class="container">
            <div class="section-header"><div><span class="profile-section-kicker">পরিবর্তনের রেকর্ড</span><h2>অ্যাডমিন অ্যাক্টিভিটি</h2></div></div>
            <?php if (!$adminLogs): ?>
                <div class="empty-state">অ্যাডমিন কার্যক্রমের রেকর্ড এখানে দেখা যাবে।</div>
            <?php else: ?>
                <div class="panel admin-table-panel table-wrap">
                    <table>
                        <thead><tr><th>অ্যাডমিন</th><th>কার্যক্রম</th><th>বিস্তারিত</th><th>সময়</th></tr></thead>
                        <tbody>
                        <?php foreach ($adminLogs as $log): ?>
                            <tr><td><?= e($log['admin_name'] ?? 'অজানা') ?></td><td><?= e($log['action']) ?></td><td><?= e($log['details'] ?? '') ?></td><td><?= e(formatDate((string) $log['created_at'])) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
