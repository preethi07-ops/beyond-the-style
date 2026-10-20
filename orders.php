<?php
require_once __DIR__ . '/../includes/admin-header.php';

$status = get('status');
if (!in_array($status, ORDER_STATUSES, true)) {
    $status = '';
}
$page = max(1, (int) get('page', '1'));
$where = $status !== '' ? ' WHERE o.status = ?' : '';
$params = $status !== '' ? [$status] : [];
$total = (int) fetch_value('SELECT COUNT(*) FROM orders o' . $where, $params);
$pages = max(1, (int) ceil($total / 20));
$page = min($page, $pages);
$orders = fetch_all('SELECT o.* FROM orders o' . $where . ' ORDER BY o.created_at DESC, o.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20), $params);
$pageTitle = 'Orders';
?>
<div class="section-head"><h1>Orders <small class="muted">(<?= $total ?>)</small></h1></div>
<form method="get" class="filter-bar">
    <select name="status" aria-label="Filter by status">
        <option value="">All statuses</option>
        <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filter</button>
</form>
<?php if (!$orders): ?>
    <p class="empty">No orders found.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td data-label="Order">#<?= (int) $o['id'] ?></td>
                <td data-label="Customer"><?= e($o['full_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
                <td data-label="Date"><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                <td data-label="Total"><?= money($o['total']) ?></td>
                <td data-label="Payment"><?= e($o['payment_method']) ?> <small class="muted">(<?= e($o['payment_status']) ?>)</small></td>
                <td data-label="Status"><span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                <td><a class="btn btn-sm btn-outline" href="<?= e(url('admin/order.php?id=' . (int) $o['id'])) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pagination_links($page, $pages, array_filter(['status' => $status])) ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
