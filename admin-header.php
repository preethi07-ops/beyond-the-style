<?php
require_once __DIR__ . '/auth.php';
$admin = require_admin();
$pageTitle = $pageTitle ?? 'Admin';
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'dashboard.php'  => 'Dashboard',
    'products.php'   => 'Products',
    'categories.php' => 'Categories',
    'orders.php'     => 'Orders',
    'users.php'      => 'Customers',
    'messages.php'   => 'Messages',
];
// Pages that belong to a menu item.
$parent = ['add-product.php' => 'products.php', 'edit-product.php' => 'products.php', 'order.php' => 'orders.php'];
$active = $parent[$current] ?? $current;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> | Admin | <?= e(SITE_NAME) ?></title>
    <link rel="icon" href="<?= e(asset_url('images/logo.jpeg')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="admin">
<div class="admin-shell">
    <aside class="admin-side">
        <div class="admin-brand">
            <img src="<?= e(asset_url('images/logo.jpeg')) ?>" alt="" width="44" height="44">
            <strong>BTS Admin</strong>
        </div>
        <nav>
            <?php foreach ($nav as $file => $label): ?>
                <a href="<?= e(url('admin/' . $file)) ?>" class="<?= $active === $file ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View store &nearr;</a>
            <a href="<?= e(url('admin/logout.php?t=' . csrf_token())) ?>">Log out (<?= e($admin['username']) ?>)</a>
        </nav>
    </aside>
    <div class="admin-main">
        <?php foreach (take_flashes() as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
