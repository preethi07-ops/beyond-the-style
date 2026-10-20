<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$errors = [];
$pwErrors = [];
$form = $user;

if (is_post()) {
    csrf_check();
    if (post('action') === 'password') {
        $row = fetch_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        if (!password_verify($current, $row['password_hash'])) {
            $pwErrors[] = 'Your current password is incorrect.';
        }
        if ($msg = valid_password($new)) {
            $pwErrors[] = $msg;
        }
        if ($new !== $confirm) {
            $pwErrors[] = 'The new passwords do not match.';
        }
        if (!$pwErrors) {
            query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            redirect('profile.php');
        }
    } else {
        foreach (['name', 'phone', 'address', 'city', 'state', 'postal_code'] as $k) {
            $form[$k] = post($k);
        }
        if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
            $errors[] = 'Please enter your name.';
        }
        if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $form['phone'])) {
            $errors[] = 'Enter a valid phone number.';
        }
        if ($form['postal_code'] !== '' && !preg_match('/^[A-Za-z0-9\- ]{4,10}$/', $form['postal_code'])) {
            $errors[] = 'Enter a valid postal code.';
        }
        foreach (['address', 'city', 'state'] as $k) {
            if (mb_strlen($form[$k]) > 255) {
                $errors[] = 'One of the address fields is too long.';
                break;
            }
        }
        if (!$errors) {
            query('UPDATE users SET name = ?, phone = ?, address = ?, city = ?, state = ?, postal_code = ? WHERE id = ?',
                [$form['name'], $form['phone'], $form['address'], $form['city'], $form['state'], $form['postal_code'], $user['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }
}

$orderCount = (int) fetch_value('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$user['id']]);
$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h2>My Account</h2>
    <p class="muted">Member since <?= e(date('d M Y', strtotime($user['created_at']))) ?> &middot;
        <a href="<?= e(url('orders.php')) ?>"><?= $orderCount ?> order<?= $orderCount === 1 ? '' : 's' ?></a> &middot;
        <a href="<?= e(url('wishlist.php')) ?>">Wishlist</a></p>

    <div class="two-col">
        <form method="post" class="panel form-grid" novalidate>
            <?= csrf_field() ?>
            <h3 class="span-2">Profile details</h3>
            <?php foreach ($errors as $err): ?><div class="flash flash-error span-2"><?= e($err) ?></div><?php endforeach; ?>
            <label>Full name <input type="text" name="name" value="<?= e($form['name']) ?>" required></label>
            <label>Email <input type="email" value="<?= e($user['email']) ?>" disabled></label>
            <label>Phone <input type="tel" name="phone" value="<?= e($form['phone']) ?>"></label>
            <label>Postal code <input type="text" name="postal_code" value="<?= e($form['postal_code']) ?>"></label>
            <label class="span-2">Address <input type="text" name="address" value="<?= e($form['address']) ?>"></label>
            <label>City <input type="text" name="city" value="<?= e($form['city']) ?>"></label>
            <label>State <input type="text" name="state" value="<?= e($form['state']) ?>"></label>
            <button class="btn span-2" type="submit">Save changes</button>
        </form>

        <form method="post" class="panel" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <h3>Change password</h3>
            <?php foreach ($pwErrors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
            <label>Current password <input type="password" name="current" autocomplete="current-password" required></label>
            <label>New password <input type="password" name="new" autocomplete="new-password" required></label>
            <label>Confirm new password <input type="password" name="confirm" autocomplete="new-password" required></label>
            <button class="btn" type="submit">Update password</button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
