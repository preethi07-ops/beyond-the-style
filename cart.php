<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Your Cart';
$items = cart_items();
$total = cart_total($items);
$hasProblem = false;
foreach ($items as $it) {
    if (!$it['in_stock']) {
        $hasProblem = true;
    }
}
include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h2>Shopping Cart</h2>
    <?php if (!$items): ?>
        <div class="empty">
            <p>Your cart is empty.</p>
            <a class="btn" href="<?= e(url('products.php')) ?>">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="table-wrap">
                <table class="table cart-table">
                    <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): $p = $it['product']; ?>
                        <tr>
                            <td data-label="Product">
                                <div class="cart-product">
                                    <img src="<?= e(product_image_url($p['image'])) ?>" alt="">
                                    <div>
                                        <a href="<?= e(url('product.php?id=' . (int) $p['id'])) ?>"><strong><?= e($p['name']) ?></strong></a>
                                        <small class="muted">Size: <?= e($it['size']) ?> &middot; Colour: <?= e($it['color']) ?></small>
                                        <?php if (!$it['in_stock']): ?>
                                            <small class="text-error">Only <?= (int) $p['stock'] ?> in stock &ndash; please reduce the quantity.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Price"><?= money($it['unit_price']) ?></td>
                            <td data-label="Quantity">
                                <form class="qty-form" method="post" action="<?= e(url('cart_action.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                    <button class="qty-btn" type="submit" name="quantity" value="<?= $it['quantity'] - 1 ?>" aria-label="Decrease quantity">&minus;</button>
                                    <span class="qty-val"><?= (int) $it['quantity'] ?></span>
                                    <button class="qty-btn" type="submit" name="quantity" value="<?= $it['quantity'] + 1 ?>" aria-label="Increase quantity" <?= $it['quantity'] >= (int) $p['stock'] ? 'disabled' : '' ?>>+</button>
                                </form>
                            </td>
                            <td data-label="Total"><strong><?= money($it['line_total']) ?></strong></td>
                            <td>
                                <form method="post" action="<?= e(url('cart_action.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                    <button class="link-btn" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="panel summary">
                <h3>Order Summary</h3>
                <p class="sum-row"><span>Items</span><span><?= cart_count() ?></span></p>
                <p class="sum-row"><span>Subtotal</span><span><?= money($total) ?></span></p>
                <p class="sum-row"><span>Shipping</span><span>Free</span></p>
                <p class="sum-row sum-total"><span>Total</span><span><?= money($total) ?></span></p>
                <?php if ($hasProblem): ?>
                    <p class="text-error">Please fix the stock issues above before checking out.</p>
                    <span class="btn btn-block disabled" aria-disabled="true">Proceed to Checkout</span>
                <?php else: ?>
                    <a class="btn btn-block" href="<?= e(url('checkout.php')) ?>">Proceed to Checkout</a>
                <?php endif; ?>
                <a class="btn btn-outline btn-block" href="<?= e(url('products.php')) ?>">Continue shopping</a>
                <form method="post" action="<?= e(url('cart_action.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <button class="link-btn" type="submit">Empty cart</button>
                </form>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
