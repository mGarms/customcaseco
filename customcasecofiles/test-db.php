<?php
// TEMPORARY database diagnostic. DELETE THIS FILE AFTER TESTING.
try {
    require_once __DIR__ . '/config/database.php';

    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo '<h2>Database connection: SUCCESS</h2>';
    echo '<p>Host: ' . htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>Database: ' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>MySQL: ' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<h3>Tables</h3><pre>' . htmlspecialchars(implode("\n", $tables), ENT_QUOTES, 'UTF-8') . '</pre>';

} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Database test FAILED</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
