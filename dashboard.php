<?php
require_once __DIR__ . '/../includes/admin-header.php';

$pageTitle = 'Dashboard';
$stats = [
    'Products'  => (int) fetch_value('SELECT COUNT(*) FROM products'),
    'Customers' => (int) fetch_value('SELECT COUNT(*) FROM users'),
    'Orders'    => (int) fetch_value('SELECT COUNT(*) FROM orders'),
    'Pending'   => (int) fetch_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
    'Revenue'   => money(fetch_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled'")),
];
$lowStock = fetch_all('SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC, name LIMIT 8');
$recent = fetch_all('SELECT id, full_name, total, status, created_at FROM orders ORDER BY created_at DESC, id DESC LIMIT 8');
?>
<h1>Dashboard</h1>
<div class="stat-grid">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat"><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="two-col">
    <div class="panel">
        <h3>Recent orders</h3>
        <?php if (!$recent): ?><p class="muted">No orders yet.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr>
                    <td><a href="<?= e(url('admin/order.php?id=' . (int) $o['id'])) ?>">#<?= (int) $o['id'] ?></a></td>
                    <td><?= e($o['full_name']) ?></td>
                    <td><?= money($o['total']) ?></td>
                    <td><span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="panel">
        <h3>Low stock (5 or fewer)</h3>
        <?php if (!$lowStock): ?><p class="muted">All products are well stocked.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Product</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($lowStock as $p): ?>
                <tr>
                    <td><a href="<?= e(url('admin/edit-product.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?></a></td>
                    <td><span class="<?= (int) $p['stock'] === 0 ? 'text-error' : '' ?>"><?= (int) $p['stock'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
