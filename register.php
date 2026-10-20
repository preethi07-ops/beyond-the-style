<?php
require_once __DIR__ . '/includes/auth.php';

$next = safe_next(get('next', post('next')), 'index.php');
if (current_user()) {
    redirect($next);
}

$errors = [];
$name = '';
$email = '';
if (is_post()) {
    csrf_check();
    $name = post('name');
    $email = mb_strtolower(post('email'));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $password = is_string($password) ? $password : '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($msg = valid_password($password)) {
        $errors[] = $msg;
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }
    if (!$errors && fetch_value('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
        $errors[] = 'An account with this email already exists. Try logging in instead.';
    }
    if (!$errors) {
        try {
            query('INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
            login_user((int) db()->lastInsertId());
            flash('success', 'Welcome to Beyond The Style, ' . $name . '!');
            redirect($next);
        } catch (PDOException $ex) {
            // A duplicate-email race hits the UNIQUE key.
            error_log('BTS registration failed: ' . $ex->getMessage());
            $errors[] = 'We could not create your account. The email may already be registered.';
        }
    }
}

$pageTitle = 'Register';
$authTitle = 'Create your account';
$authBox = 'register-box';
include __DIR__ . '/includes/auth-top.php';
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php if ($errors): ?>
        <div class="auth-error" role="alert">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="input-box">
        <input type="text" name="name" placeholder="Full Name" value="<?= e($name) ?>" autocomplete="name" required>
    </div>
    <div class="input-box">
        <input type="email" name="email" placeholder="Email Address" value="<?= e($email) ?>" autocomplete="email" required>
    </div>
    <div class="input-box">
        <input type="password" name="password" placeholder="Password (8+ characters, letters &amp; numbers)" autocomplete="new-password" required>
    </div>
    <div class="input-box">
        <input type="password" name="confirm" placeholder="Confirm Password" autocomplete="new-password" required>
    </div>
    <button type="submit">Register</button>

    <div class="links">
        <p>Already have an account? <a href="<?= e(url('login.php?next=' . rawurlencode($next))) ?>">Login</a></p>
        <p><a href="<?= e(url('index.php')) ?>">&larr; Back to store</a></p>
    </div>
</form>
<?php include __DIR__ . '/includes/auth-bottom.php'; ?>
