<?php
require_once __DIR__ . '/../includes/auth.php';

if (hash_equals(csrf_token(), (string) get('t'))) {
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
    flash('success', 'You have been logged out of the admin panel.');
}
redirect('admin/index.php');
