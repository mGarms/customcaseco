<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect('cart.php');
}

$cartItemId = $_POST['cart_item_id'] ?? '';
if (!ctype_digit((string)$cartItemId)) redirect('cart.php');

$stmt = $pdo->prepare(
    'DELETE ci FROM cart_items ci
     JOIN carts c ON c.id = ci.cart_id
     WHERE ci.id = :id AND c.user_id = :uid'
);
$stmt->execute([':id' => $cartItemId, ':uid' => $_SESSION['user_id']]);

set_flash('success', 'Item removed from cart.');
redirect('cart.php');