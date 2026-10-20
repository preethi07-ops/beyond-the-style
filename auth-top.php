<?php
/** Opening markup for the standalone login / register pages (keeps the original glass-card look). */
require_once __DIR__ . '/functions.php';
$authBox = $authBox ?? 'login-box';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Account') ?> | <?= e(SITE_NAME) ?></title>
    <link rel="icon" href="<?= e(asset_url('images/logo.jpeg')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
<?php foreach (take_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?> auth-flash" role="alert"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<div class="<?= e($authBox) ?> auth-box">
    <h1>Beyond The Style</h1>
    <p><?= e($authTitle ?? '') ?></p>
