<?php
/**
 * Beyond The Style - configuration.
 * Default values match a fresh XAMPP install (user "root", empty password).
 * Change them here if your MySQL setup is different.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'beyond_the_style');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Beyond The Style');
define('CURRENCY', '₹');
define('PER_PAGE', 12);

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/assets/uploads/products/');
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024);

// URL of the project folder, e.g. "/Beyond-The-Style" (auto-detected).
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (basename($scriptDir) === 'admin') {
    $scriptDir = dirname($scriptDir);
}
define('BASE_URL', rtrim($scriptDir === '/' || $scriptDir === '.' ? '' : $scriptDir, '/'));

// Sessions: HttpOnly cookies, same-site, strict mode.
if (session_status() === PHP_SESSION_NONE) {
    session_name('bts_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
