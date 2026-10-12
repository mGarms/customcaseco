<?php
// Custom Case Co. - Products API
// GET /api/products.php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);

    exit;
}

try {
    $sql = "
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.is_featured,
            c.name AS category,
            pb.name AS brand,
            pm.name AS phone_model
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        LEFT JOIN phone_models pm
            ON p.phone_model_id = pm.id
        LEFT JOIN phone_brands pb
            ON pm.brand_id = pb.id
        WHERE p.is_active = 1
        ORDER BY p.id ASC
    ";

    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'dataset' => 'products',
        'count' => count($products),
        'data' => $products
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve products.'
    ]);
}