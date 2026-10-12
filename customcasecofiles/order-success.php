<?php
// =====================================================
// ORDER CONFIRMATION
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$orderNumber = $_GET['order'] ?? '';

$stmt = $pdo->prepare(
    'SELECT * FROM orders WHERE order_number = :num AND user_id = :uid'
);
$stmt->execute([':num' => $orderNumber, ':uid' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$stmt->execute([':id' => $order['id']]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Order Confirmed';
require_once 'includes/header.php';
?>

<div class="container cc-section text-center">
  <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #1d7a45;"></i>
  <h1 class="h3 mt-3">Thank You for Your Order!</h1>
  <p class="text-muted">Order <strong><?= e($order['order_number']) ?></strong> has been placed.</p>

  <div class="cc-card p-4 mx-auto text-start mt-4" style="max-width: 500px;">
    <?php foreach ($orderItems as $item): ?>
      <div class="d-flex justify-content-between small mb-2">
        <span><?= e($item['item_name']) ?> × <?= (int)$item['quantity'] ?></span>
        <span><?= format_price($item['line_total']) ?></span>
      </div>
    <?php endforeach; ?>
    <hr>
    <div class="d-flex justify-content-between fw-bold">
      <span>Total Paid</span>
      <span class="cc-price"><?= format_price($order['total']) ?></span>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-center mt-4">
    <a href="<?= BASE_URL ?>/orders.php" class="btn btn-cc-primary">View My Orders</a>
    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline">Continue Shopping</a>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>