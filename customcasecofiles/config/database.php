<?php
// =====================================================
// Database connection (PDO + MySQL)
// InfinityFree configuration
// =====================================================
require_once __DIR__ . '/config.php';

define('DB_HOST',    'sql206.infinityfree.com');
define('DB_NAME',    'if0_43101176_customcaseco');
define('DB_USER',    'if0_43101176');
define('DB_PASS',    'hMtz5Vvsadiv');
define('DB_CHARSET', 'utf8mb4');

// Keep true temporarily while testing. Set to false after the
// database connection is confirmed.
define('DB_DEBUG', false);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());

    http_response_code(500);

    if (DB_DEBUG) {
        exit(
            '<h3>Database connection failed</h3><p>'
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</p>'
        );
    }

    exit('Sorry, something went wrong. Please try again later.');
}
