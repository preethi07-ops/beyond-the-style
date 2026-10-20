<?php
require_once __DIR__ . '/includes/auth.php';

$next = safe_next(get('next', post('next')), 'index.php');
if (current_user()) {
    redirect($next);
}

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = mb_strtolower(post('email'));
    $password = $_POST['password'] ?? '';
    if (login_blocked('user')) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '') {
        $error = 'Please enter your email address and password.';
    } else {
        $row = fetch_one('SELECT id, password_hash FROM users WHERE email = ?', [$email]);
        // Always run password_verify so response time does not reveal whether the email exists.
        $hash = $row['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuAbCdEfGhIjKlMnOpQrStUvWxYzAbCdE';
        if (password_verify($password, $hash) && $row) {
            login_reset('user');
            login_user((int) $row['id']);
            flash('success', 'Welcome back!');
            redirect($next);
        }
        login_failed('user');
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Log In';
$authTitle = 'Welcome Back!! Login to Continue';
include __DIR__ . '/includes/auth-top.php';
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php if ($error): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <div class="input-box">
        <input type="email" name="email" placeholder="Email Address" value="<?= e($email) ?>" autocomplete="email" required>
    </div>
    <div class="input-box">
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
    </div>
    <button type="submit">Login</button>

    <div class="links">
        <p>Don't have an account? <a href="<?= e(url('register.php?next=' . rawurlencode($next))) ?>">Register</a></p>
        <p><a href="<?= e(url('index.php')) ?>">&larr; Back to store</a></p>
    </div>
</form>
<?php include __DIR__ . '/includes/auth-bottom.php'; ?>
