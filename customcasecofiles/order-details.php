<?php
// =====================================================
// ORDER DETAILS - order-details.php?id=1
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) {
    set_flash('danger', 'Invalid order.');
    redirect('orders.php');
}
$id = (int)$id;

// IMPORTANT: WHERE also checks user_id, so a user can never view someone else's order
// by guessing an ID in the URL.
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Order not found.');
    redirect('orders.php');
}

// Items (with custom design details when applicable)
$stmt = $pdo->prepare(
    "SELECT oi.*, cd.uploaded_image, cd.custom_text, cd.text_color,
            cdesign.name AS design_name, cdesign.color_hex,
            m.name AS model_name, b.name AS brand_name
     FROM order_items oi
     LEFT JOIN custom_designs cd ON cd.id = oi.custom_design_id
     LEFT JOIN case_designs cdesign ON cdesign.id = cd.case_design_id
     LEFT JOIN phone_models m ON m.id = cd.phone_model_id
     LEFT JOIN phone_brands b ON b.id = m.brand_id
     WHERE oi.order_id = :id"
);
$stmt->execute([':id' => $id]);
$items = $stmt->fetchAll();

// Payment
$stmt = $pdo->prepare('SELECT method, status FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$stmt->execute([':id' => $id]);
$payment = $stmt->fetch();

// Status history (for the timeline)
$stmt = $pdo->prepare('SELECT status, note, changed_at FROM order_status_history WHERE order_id = :id ORDER BY changed_at ASC');
$stmt->execute([':id' => $id]);
$history = $stmt->fetchAll();

$paymentLabels = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];

$pageTitle = 'Order ' . $order['order_number'];
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2 small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/orders.php">My Orders</a></li>
        <li class="breadcrumb-item active"><?= e($order['order_number']) ?></li>
      </ol>
    </nav>
    <h1>Order <?= e($order['order_number']) ?></h1>
    <p>Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row g-4">

    <div class="col-lg-8">

      <!-- Items -->
      <div class="cc-card p-4 mb-4">
        <h2 class="h6 mb-3">Items</h2>
        <?php foreach ($items as $item): ?>
          <div class="d-flex gap-3 py-3 border-bottom">
            <div class="cc-product-img flex-shrink-0" style="width:70px; height:70px; border-radius:12px;">
              <?php if ($item['custom_design_id'] && $item['uploaded_image']): ?>
                <img src="<?= BASE_URL ?>/uploads/custom_designs/<?= e($item['uploaded_image']) ?>" alt="">
              <?php elseif ($item['custom_design_id']): ?>
                <div style="width:100%; height:100%; background:<?= e($item['color_hex'] ?? '#c4b5fd') ?>;
                            display:flex; align-items:center; justify-content:center;">
                  <i class="bi bi-phone text-white"></i>
                </div>
              <?php else: ?>
                <i class="bi bi-phone"></i>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1">
              <h3 class="h6 mb-1"><?= e($item['item_name']) ?></h3>
              <?php if ($item['custom_design_id']): ?>
                <p class="small text-muted mb-1">
                  <?= e($item['brand_name'] . ' ' . $item['model_name']) ?> · <?= e($item['design_name']) ?>
                </p>
                <?php if ($item['custom_text']): ?>
                  <p class="small mb-0"><i class="bi bi-fonts"></i> "<?= e($item['custom_text']) ?>"</p>
                <?php endif; ?>
              <?php endif; ?>
              <p class="small text-muted mb-0">Qty: <?= (int)$item['quantity'] ?> × <?= format_price($item['unit_price']) ?></p>
            </div>
            <div class="text-end">
              <span class="cc-price"><?= format_price($item['line_total']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="pt-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Subtotal</span>
            <span><?= format_price($order['subtotal']) ?></span>
          </div>
          <?php if ($order['discount'] > 0): ?>
            <div class="d-flex justify-content-between mb-1 text-success">
              <span>Discount</span>
              <span>-<?= format_price($order['discount']) ?></span>
            </div>
          <?php endif; ?>
          <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
            <span>Total</span>
            <span class="cc-price"><?= format_price($order['total']) ?></span>
          </div>
        </div>
      </div>

      <!-- Status timeline -->
      <?php if ($history): ?>
        <div class="cc-card p-4">
          <h2 class="h6 mb-3">Order Timeline</h2>
          <?php foreach ($history as $h): ?>
            <div class="d-flex gap-3 mb-3">
              <span class="cc-status cc-status-<?= e($h['status']) ?>"><?= e(ucfirst($h['status'])) ?></span>
              <div>
                <?php if ($h['note']): ?><p class="mb-0 small"><?= e($h['note']) ?></p><?php endif; ?>
                <p class="mb-0 small text-muted"><?= date('M j, Y g:i A', strtotime($h['changed_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>

    <div class="col-lg-4">
      <div class="cc-card p-4 mb-4">
        <h2 class="h6 mb-3">Current Status</h2>
        <span class="cc-status cc-status-<?= e($order['status']) ?> fs-6">
          <?= e(ucfirst($order['status'])) ?>
        </span>
      </div>

      <div class="cc-card p-4 mb-4">
        <h2 class="h6 mb-3">Delivery Information</h2>
        <p class="small mb-1"><strong><?= e($order['shipping_name']) ?></strong></p>
        <p class="small mb-1"><i class="bi bi-telephone"></i> <?= e($order['shipping_phone']) ?></p>
        <p class="small mb-0"><i class="bi bi-geo-alt"></i> <?= nl2br(e($order['shipping_address'])) ?></p>
      </div>

      <div class="cc-card p-4">
        <h2 class="h6 mb-3">Payment Method</h2>
        <p class="small mb-1"><?= e($paymentLabels[$payment['method'] ?? 'cod'] ?? 'Cash on Delivery') ?></p>
        <span class="cc-tag"><?= e(ucfirst($payment['status'] ?? 'pending')) ?></span>
      </div>
    </div>

  </div>

  <div class="mt-4">
    <a href="<?= BASE_URL ?>/orders.php" class="btn btn-cc-outline"><i class="bi bi-arrow-left"></i> Back to My Orders</a>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>