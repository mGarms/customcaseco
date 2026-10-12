<?php
// =====================================================
// MY ORDERS - list of the logged-in user's past orders
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$pageTitle = 'My Orders';
require_once 'includes/header.php';

$stmt = $pdo->prepare(
    'SELECT id, order_number, total, status, created_at
     FROM orders
     WHERE user_id = :uid
     ORDER BY created_at DESC'
);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="cc-page-header">
  <div class="container">
    <h1><i class="bi bi-box-seam"></i> My Orders</h1>
    <p>Track and review your past purchases</p>
  </div>
</div>

<div class="container cc-section pt-0">

  <?php if (!$orders): ?>
    <div class="cc-card p-5 text-center">
      <i class="bi bi-inbox" style="font-size: 2.5rem; color: var(--cc-purple-pastel);"></i>
      <h2 class="h5 mt-3">No orders yet</h2>
      <p class="text-muted mb-3">Once you place an order, it will show up here.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-primary">Start Shopping</a>
    </div>
  <?php else: ?>

    <div class="cc-card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-lavender">
            <tr>
              <th class="ps-4">Order #</th>
              <th>Date</th>
              <th>Total</th>
              <th>Status</th>
              <th class="text-end pe-4">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td class="ps-4 fw-semibold"><?= e($order['order_number']) ?></td>
                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                <td class="cc-price"><?= format_price($order['total']) ?></td>
                <td>
                  <span class="cc-status cc-status-<?= e($order['status']) ?>">
                    <?= e(ucfirst($order['status'])) ?>
                  </span>
                </td>
                <td class="text-end pe-4">
                  <a href="<?= BASE_URL ?>/order-details.php?id=<?= (int)$order['id'] ?>"
                     class="btn btn-cc-outline btn-sm">View Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>