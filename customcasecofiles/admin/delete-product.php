<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) redirect('admin/products.php');

$id = $_POST['id'] ?? '';
if (!ctype_digit((string)$id)) redirect('admin/products.php');

$stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if ($product) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($product['image']) {
        $file = __DIR__ . '/../uploads/products/' . $product['image'];
        if (file_exists($file)) unlink($file);
    }
    set_flash('success', 'Product deleted.');
} else {
    set_flash('danger', 'Product not found.');
}

redirect('admin/products.php');