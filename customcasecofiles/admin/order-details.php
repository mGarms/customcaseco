<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) { set_flash('danger', 'Invalid order.'); redirect('admin/orders.php'); }
$id = (int)$id;

$stmt = $pdo->prepare(
    'SELECT o.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
     FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = :id'
);
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();
if (!$order) { set_flash('danger', 'Order not found.'); redirect('admin/orders.php'); }

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

$stmt = $pdo->prepare('SELECT method, status FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$stmt->execute([':id' => $id]);
$payment = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT h.status, h.note, h.changed_at, u.full_name AS changed_by_name
     FROM order_status_history h LEFT JOIN users u ON u.id = h.changed_by
     WHERE h.order_id = :id ORDER BY h.changed_at ASC'
);
$stmt->execute([':id' => $id]);
$history = $stmt->fetchAll();

$statuses = ['pending','processing','customized','shipped','delivered','cancelled'];
$paymentLabels = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash'];

$pageTitle = 'Order ' . $order['order_number'];
$activeAdminPage = 'orders';
require_once __DIR__ . '/includes/admin_header.php';
?>

<a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-cc-outline btn-sm mb-3"><i class="bi bi-arrow-left"></i> Back to Orders</a>

<div class="row g-4">
  <div class="col-lg-8">

    <div class="cc-card p-4 mb-4">
      <h2 class="h6 mb-3">Customer Information</h2>
      <p class="mb-1"><strong><?= e($order['customer_name']) ?></strong></p>
      <p class="small text-muted mb-1"><?= e($order['customer_email']) ?></p>
      <p class="small text-muted mb-0"><?= e($order['customer_phone']) ?></p>
    </div>

    <div class="cc-card p-4 mb-4">
      <h2 class="h6 mb-3">Order Items</h2>
      <?php foreach ($items as $item): ?>
        <div class="d-flex gap-3 py-3 border-bottom">
          <div style="width:70px;height:70px;border-radius:12px;overflow:hidden;background:var(--cc-lavender-bg);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
            <?php if ($item['custom_design_id'] && $item['uploaded_image']): ?>
              <img src="<?= BASE_URL ?>/uploads/custom_designs/<?= e($item['uploaded_image']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php elseif ($item['custom_design_id']): ?>
              <div style="width:100%;height:100%;background:<?= e($item['color_hex'] ?? '#c4b5fd') ?>;display:flex;align-items:center;justify-content:center;"><i class="bi bi-phone text-white"></i></div>
            <?php else: ?><i class="bi bi-phone"></i><?php endif; ?>
          </div>
          <div class="flex-grow-1">
            <h3 class="h6 mb-1"><?= e($item['item_name']) ?></h3>
            <?php if ($item['custom_design_id']): ?>
              <p class="small text-muted mb-1"><?= e($item['brand_name'] . ' ' . $item['model_name']) ?> · <?= e($item['design_name']) ?></p>
              <?php if ($item['custom_text']): ?>
                <p class="small mb-0">Text: "<?= e($item['custom_text']) ?>" <span class="badge" style="background:<?= e($item['text_color']) ?>; color:#333;">&nbsp;</span></p>
              <?php endif; ?>
            <?php endif; ?>
            <p class="small text-muted mb-0">Qty: <?= (int)$item['quantity'] ?> × <?= format_price($item['unit_price']) ?></p>
          </div>
          <div class="text-end"><span class="cc-price"><?= format_price($item['line_total']) ?></span></div>
        </div>
      <?php endforeach; ?>
      <div class="pt-3 d-flex justify-content-between fw-bold fs-5">
        <span>Total</span><span class="cc-price"><?= format_price($order['total']) ?></span>
      </div>
    </div>

    <?php if ($history): ?>
      <div class="cc-card p-4">
        <h2 class="h6 mb-3">Status History</h2>
        <?php foreach ($history as $h): ?>
          <div class="d-flex gap-3 mb-3">
            <span class="cc-status cc-status-<?= e($h['status']) ?>"><?= ucfirst($h['status']) ?></span>
            <div>
              <?php if ($h['note']): ?><p class="mb-0 small"><?= e($h['note']) ?></p><?php endif; ?>
              <p class="mb-0 small text-muted">
                <?= date('M j, Y g:i A', strtotime($h['changed_at'])) ?>
                <?= $h['changed_by_name'] ? ' by ' . e($h['changed_by_name']) : '' ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="cc-card p-4 mb-4">
      <h2 class="h6 mb-3">Update Status</h2>
      <form action="<?= BASE_URL ?>/admin/update-order.php" method="post" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
        <select class="form-select mb-2" name="status" required>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea class="form-control mb-2" name="note" rows="2" placeholder="Optional note (e.g. tracking number)"></textarea>
        <button type="submit" class="btn btn-admin-primary w-100">Update Status</button>
      </form>
    </div>

    <div class="cc-card p-4 mb-4">
      <h2 class="h6 mb-3">Delivery Information</h2>
      <p class="small mb-1"><strong><?= e($order['shipping_name']) ?></strong></p>
      <p class="small mb-1"><i class="bi bi-telephone"></i> <?= e($order['shipping_phone']) ?></p>
      <p class="small mb-0"><i class="bi bi-geo-alt"></i> <?= nl2br(e($order['shipping_address'])) ?></p>
    </div>

    <div class="cc-card p-4">
      <h2 class="h6 mb-3">Payment</h2>
      <p class="small mb-1"><?= e($paymentLabels[$payment['method'] ?? 'cod'] ?? 'N/A') ?></p>
      <span class="cc-tag"><?= ucfirst($payment['status'] ?? 'pending') ?></span>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>