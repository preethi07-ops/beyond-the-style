<?php
require_once __DIR__ . '/../includes/admin-header.php';

$page = max(1, (int) get('page', '1'));
$total = (int) fetch_value('SELECT COUNT(*) FROM users');
$pages = max(1, (int) ceil($total / 25));
$page = min($page, $pages);
$users = fetch_all('SELECT u.id, u.name, u.email, u.phone, u.city, u.created_at,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
    FROM users u ORDER BY u.id DESC LIMIT 25 OFFSET ' . (($page - 1) * 25));
$pageTitle = 'Customers';
?>
<h1>Registered customers <small class="muted">(<?= $total ?>)</small></h1>
<?php if (!$users): ?>
    <p class="empty">No customers have registered yet.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Orders</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td data-label="#"><?= (int) $u['id'] ?></td>
                <td data-label="Name"><?= e($u['name']) ?></td>
                <td data-label="Email"><?= e($u['email']) ?></td>
                <td data-label="Phone"><?= e($u['phone'] ?: '-') ?></td>
                <td data-label="City"><?= e($u['city'] ?: '-') ?></td>
                <td data-label="Orders"><?= (int) $u['order_count'] ?></td>
                <td data-label="Joined"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pagination_links($page, $pages, []) ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
