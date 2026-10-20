<?php
require_once __DIR__ . '/functions.php';

/* ---------- Customer ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = fetch_one('SELECT id, name, email, phone, address, city, state, postal_code, created_at FROM users WHERE id = ?', [$_SESSION['user_id']]);
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        $self = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) && !is_post() ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect('login.php?next=' . rawurlencode($self));
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    cart_merge_guest_into_user($userId);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Admin (separate session key, separate table) ---------- */

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $admin = null;
        if (!empty($_SESSION['admin_id'])) {
            $admin = fetch_one('SELECT id, username FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
            if (!$admin) {
                unset($_SESSION['admin_id']);
            }
        }
    }
    return $admin;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('admin/index.php');
    }
    return $admin;
}
