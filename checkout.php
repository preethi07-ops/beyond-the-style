<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$items = cart_items();
if (!$items) {
    flash('error', 'Your cart is empty.');
    redirect('cart.php');
}
$total = cart_total($items);

$form = [
    'full_name'   => $user['name'],
    'email'       => $user['email'],
    'phone'       => $user['phone'],
    'address'     => $user['address'],
    'city'        => $user['city'],
    'state'       => $user['state'],
    'postal_code' => $user['postal_code'],
    'payment'     => 'cod',
];
$errors = [];

if (is_post()) {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = post($k, $form[$k]);
    }
    foreach (['full_name' => 'Full name', 'address' => 'Address', 'city' => 'City', 'state' => 'State'] as $k => $label) {
        if ($form[$k] === '' || mb_strlen($form[$k]) > 255) {
            $errors[$k] = $label . ' is required.';
        }
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $form['phone'])) {
        $errors['phone'] = 'Enter a valid phone number (7-20 digits).';
    }
    if (!preg_match('/^[A-Za-z0-9\- ]{4,10}$/', $form['postal_code'])) {
        $errors['postal_code'] = 'Enter a valid postal code.';
    }
    if (!in_array($form['payment'], ['cod', 'demo'], true)) {
        $errors['payment'] = 'Choose a payment method.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Reserve stock atomically: the UPDATE only succeeds if enough units remain.
            foreach ($items as $it) {
                $stmt = query('UPDATE products SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ? AND stock >= ?',
                    [$it['quantity'], $it['quantity'], $it['product']['id'], $it['quantity']]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Sorry, "' . $it['product']['name'] . '" no longer has enough stock. Please update your cart.');
                }
            }
            $paymentLabel = $form['payment'] === 'demo' ? 'Demo online payment' : 'Cash on Delivery';
            query('INSERT INTO orders (user_id, full_name, email, phone, address, city, state, postal_code, total, payment_method, payment_status, status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$user['id'], $form['full_name'], $form['email'], $form['phone'], $form['address'], $form['city'], $form['state'],
                 $form['postal_code'], $total, $paymentLabel, $form['payment'] === 'demo' ? 'paid' : 'unpaid', 'pending', date('Y-m-d H:i:s')]);
            $orderId = (int) $pdo->lastInsertId();
            foreach ($items as $it) {
                query('INSERT INTO order_items (order_id, product_id, product_name, image, price, size, color, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$orderId, $it['product']['id'], $it['product']['name'], $it['product']['image'], $it['unit_price'], $it['size'], $it['color'], $it['quantity']]);
            }
            query('DELETE FROM cart WHERE user_id = ?', [$user['id']]);
            // Remember the delivery details for next time.
            query('UPDATE users SET phone = ?, address = ?, city = ?, state = ?, postal_code = ? WHERE id = ?',
                [$form['phone'], $form['address'], $form['city'], $form['state'], $form['postal_code'], $user['id']]);
            $pdo->commit();
            flash('success', 'Thank you! Your order #' . $orderId . ' has been placed.');
            redirect('order.php?id=' . $orderId);
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            flash('error', $ex->getMessage());
            redirect('cart.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('BTS checkout failed: ' . $ex->getMessage());
            $errors['general'] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
$field = function (string $name, string $label, string $type = 'text', string $extra = '') use ($form, $errors) {
    echo '<label>' . e($label)
        . '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($form[$name]) . '" ' . $extra
        . ' required class="' . (isset($errors[$name]) ? 'invalid' : '') . '">';
    if (isset($errors[$name])) {
        echo '<small class="text-error">' . e($errors[$name]) . '</small>';
    }
    echo '</label>';
};
?>
<section class="section">
    <h2>Checkout</h2>
    <?php if (isset($errors['general'])): ?><div class="flash flash-error"><?= e($errors['general']) ?></div><?php endif; ?>
    <div class="cart-layout">
        <form method="post" class="panel form-grid" novalidate>
            <?= csrf_field() ?>
            <h3 class="span-2">Delivery details</h3>
            <?php $field('full_name', 'Full name'); ?>
            <?php $field('email', 'Email', 'email'); ?>
            <?php $field('phone', 'Phone', 'tel'); ?>
            <?php $field('postal_code', 'Postal code'); ?>
            <div class="span-2"><?php $field('address', 'Address'); ?></div>
            <?php $field('city', 'City'); ?>
            <?php $field('state', 'State'); ?>

            <h3 class="span-2">Payment</h3>
            <div class="span-2 radio-list">
                <label class="radio"><input type="radio" name="payment" value="cod" <?= $form['payment'] === 'cod' ? 'checked' : '' ?>> Cash on Delivery</label>
                <label class="radio"><input type="radio" name="payment" value="demo" <?= $form['payment'] === 'demo' ? 'checked' : '' ?>> Demo online payment <small class="muted">(simulated &ndash; no card details are collected and nothing is charged)</small></label>
                <?php if (isset($errors['payment'])): ?><small class="text-error"><?= e($errors['payment']) ?></small><?php endif; ?>
            </div>
            <button class="btn btn-lg span-2" type="submit">Place Order &middot; <?= money($total) ?></button>
        </form>

        <aside class="panel summary">
            <h3>Your order</h3>
            <?php foreach ($items as $it): ?>
                <p class="sum-row">
                    <span><?= e($it['product']['name']) ?> <small class="muted">(<?= e($it['size']) ?>, <?= e($it['color']) ?>) &times; <?= (int) $it['quantity'] ?></small></span>
                    <span><?= money($it['line_total']) ?></span>
                </p>
            <?php endforeach; ?>
            <p class="sum-row"><span>Shipping</span><span>Free</span></p>
            <p class="sum-row sum-total"><span>Total</span><span><?= money($total) ?></span></p>
            <a class="link-btn" href="<?= e(url('cart.php')) ?>">&larr; Edit cart</a>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
