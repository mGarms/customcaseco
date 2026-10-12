<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect('cart.php');
}

$cartItemId = $_POST['cart_item_id'] ?? '';
$quantity   = $_POST['quantity'] ?? 1;

if (!ctype_digit((string)$cartItemId)) redirect('cart.php');
$quantity = ctype_digit((string)$quantity) ? (int)$quantity : 1;
$quantity = max(1, $quantity);

// Make sure this cart item actually belongs to the logged-in user
$stmt = $pdo->prepare(
    'SELECT ci.id, ci.product_id, ci.custom_design_id, p.stock
     FROM cart_items ci
     JOIN carts c ON c.id = ci.cart_id
     LEFT JOIN products p ON p.id = ci.product_id
     WHERE ci.id = :id AND c.user_id = :uid'
);
$stmt->execute([':id' => $cartItemId, ':uid' => $_SESSION['user_id']]);
$item = $stmt->fetch();

if (!$item) redirect('cart.php');

// Cap at available stock for real products; cap custom designs at 10 (made to order)
if ($item['product_id']) {
    $quantity = min($quantity, max(1, (int)$item['stock']));
} else {
    $quantity = min($quantity, 10);
}

$stmt = $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE id = :id');
$stmt->execute([':qty' => $quantity, ':id' => $cartItemId]);

set_flash('success', 'Cart updated.');
redirect('cart.php');