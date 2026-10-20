<?php
require_once __DIR__ . '/config.php';

/**
 * Returns the shared PDO connection (created on first use).
 * Shows a clear setup message if MySQL is not running or the database was not imported.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('BTS database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('<h2>Database connection failed</h2><p>Make sure MySQL is running in XAMPP, '
            . 'that <code>database.sql</code> has been imported, and that the credentials in '
            . '<code>includes/config.php</code> are correct.</p>');
    }
    return $pdo;
}

/** Run a prepared statement and return it. */
function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch_all(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $row = query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function fetch_value(string $sql, array $params = [])
{
    $row = query($sql, $params)->fetch(PDO::FETCH_NUM);
    return $row === false ? null : $row[0];
}
