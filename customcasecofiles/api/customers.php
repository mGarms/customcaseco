<?php
// Custom Case Co. - Customers API
// GET /api/customers.php
// Admin-only: exposes customer PII, must never be public.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}

try {
    $sql = "
        SELECT id, full_name, username, email, phone, created_at
        FROM users
        WHERE role_id = 1 AND is_active = 1
        ORDER BY id ASC
    ";
    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'dataset' => 'customers',
        'count' => count($customers),
        'data' => $customers
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to retrieve customers.']);
}