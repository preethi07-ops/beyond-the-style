<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int) get('id', post('id'));
$order = fetch_one('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) {
    flash('error', 'Order not found.');
    redirect('admin/orders.php');
}
if (is_post()) {
    csrf_check();
    [$ok, $msg] = update_order_status($id, post('status'));
    flash($ok ? 'success' : 'error', $msg);
    redirect('admin/order.php?id=' . $id);
}
$items = fetch_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$id]);
$pageTitle = 'Order #' . $id;
?>
<p class="breadcrumb"><a href="<?= e(url('admin/orders.php')) ?>">&larr; All orders</a></p>
<div class="section-head">
    <h1>Order #<?= (int) $order['id'] ?></h1>
    <span class="status status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
</div>
<div class="two-col">
    <div class="panel">
        <h3>Items</h3>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td data-label="Product"><div class="cart-product"><img src="<?= e(product_image_url($it['image'])) ?>" alt="">
                        <div><strong><?= e($it['product_name']) ?></strong><small class="muted"><?= e($it['size']) ?> &middot; <?= e($it['color']) ?></small></div></div></td>
                    <td data-label="Price"><?= money($it['price']) ?></td>
                    <td data-label="Qty"><?= (int) $it['quantity'] ?></td>
                    <td data-label="Total"><?= money($it['price'] * $it['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <p class="sum-row sum-total"><span>Order total</span><span><?= money($order['total']) ?></span></p>
    </div>
    <div class="panel">
        <h3>Update status</h3>
        <form method="post" class="stock-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <select name="status" aria-label="Order status" <?= $order['status'] === 'cancelled' ? 'disabled' : '' ?>>
                <?php foreach (ORDER_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit" <?= $order['status'] === 'cancelled' ? 'disabled' : '' ?>>Update</button>
        </form>
        <p class="muted small">Cancelling an order returns its items to stock. Marking it delivered marks it as paid.</p>
        <h3>Customer</h3>
        <p><?= e($order['full_name']) ?><br><?= e($order['email']) ?><br><?= e($order['phone']) ?></p>
        <h3>Delivery address</h3>
        <p><?= e($order['address']) ?><br><?= e($order['city']) ?>, <?= e($order['state']) ?> <?= e($order['postal_code']) ?></p>
        <h3>Payment</h3>
        <p><?= e($order['payment_method']) ?> (<?= e($order['payment_status']) ?>)<br>
           <small class="muted">Placed <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></small></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
