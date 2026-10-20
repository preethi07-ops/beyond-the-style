<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_admin()) {
    redirect('admin/dashboard.php');
}
$error = '';
$username = '';
if (is_post()) {
    csrf_check();
    $username = post('username');
    $password = (string) ($_POST['password'] ?? '');
    if (login_blocked('admin')) {
        $error = 'Too many failed attempts. Please wait a few minutes.';
    } else {
        $row = fetch_one('SELECT id, password_hash FROM admins WHERE username = ?', [$username]);
        $hash = $row['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuAbCdEfGhIjKlMnOpQrStUvWxYzAbCdE';
        if (password_verify($password, $hash) && $row) {
            login_reset('admin');
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $row['id'];
            redirect('admin/dashboard.php');
        }
        login_failed('admin');
        $error = 'Invalid username or password.';
    }
}
$pageTitle = 'Admin Login';
$authTitle = 'Admin Panel';
include __DIR__ . '/../includes/auth-top.php';
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="input-box"><input type="text" name="username" placeholder="Username" value="<?= e($username) ?>" autocomplete="username" required></div>
    <div class="input-box"><input type="password" name="password" placeholder="Password" autocomplete="current-password" required></div>
    <button type="submit">Login</button>
    <div class="links"><p><a href="<?= e(url('index.php')) ?>">&larr; Back to store</a></p></div>
</form>
<?php include __DIR__ . '/../includes/auth-bottom.php'; ?>
