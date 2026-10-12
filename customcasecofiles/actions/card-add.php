<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request. Please try again.');
    redirect('shop.php');
}

$productId = $_POST['product_id'] ?? '';
$quantity  = $_POST['quantity'] ?? 1;

if (!ctype_digit((string)$productId)) {
    set_flash('danger', 'Invalid product.');
    redirect('shop.php');
}
$quantity = ctype_digit((string)$quantity) ? (int)$quantity : 1;
$quantity = max(1, $quantity);

$stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = :id AND is_active = 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'That product is no longer available.');
    redirect('shop.php');
}
if ($product['stock'] <= 0) {
    set_flash('danger', 'Sorry, that case is out of stock.');
    redirect('shop.php');
}

$userId = $_SESSION['user_id'];
$cartId = get_or_create_cart_id($pdo, $userId);

$stmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :pid');
$stmt->execute([':cart_id' => $cartId, ':pid' => $productId]);
$existing = $stmt->fetch();

if ($existing) {
    $newQty = min($product['stock'], $existing['quantity'] + $quantity);
    $stmt = $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE id = :id');
    $stmt->execute([':qty' => $newQty, ':id' => $existing['id']]);
} else {
    $newQty = min($product['stock'], $quantity);
    $stmt = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :pid, :qty)');
    $stmt->execute([':cart_id' => $cartId, ':pid' => $productId, ':qty' => $newQty]);
}

set_flash('success', e($product['name']) . ' added to your cart.');

$referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/shop.php');
header('Location: ' . $referer);
exit;