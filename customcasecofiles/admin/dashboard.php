<?php
// =====================================================
// ADMIN DASHBOARD
// =====================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

// ---------- Stats ----------
$totalUsers = $pdo->query(
    "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'customer'"
)->fetchColumn();

$totalProducts = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

$totalOrders     = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();

$totalSales = $pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'"
)->fetchColumn();

// ---------- Recent orders ----------
$recentOrders = $pdo->query(
    "SELECT o.id, o.order_number, o.total, o.status, o.created_at, u.full_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetchAll();

// ---------- Recent users ----------
$recentUsers = $pdo->query(
    "SELECT full_name, email, created_at
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE r.name = 'customer'
     ORDER BY u.created_at DESC
     LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeAdminPage = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: var(--cc-purple);"><i class="bi bi-people"></i></div>
      <div>
        <div class="admin-stat-value"><?= (int)$totalUsers ?></div>
        <div class="admin-stat-label">Total Users</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: var(--cc-pink-dark);"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="admin-stat-value"><?= (int)$totalProducts ?></div>
        <div class="admin-stat-label">Total Products</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: #1a5b99;"><i class="bi bi-receipt"></i></div>
      <div>
        <div class="admin-stat-value"><?= (int)$totalOrders ?></div>
        <div class="admin-stat-label">Total Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: #c17f00;"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="admin-stat-value"><?= (int)$pendingOrders ?></div>
        <div class="admin-stat-label">Pending Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: #1d7a45;"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="admin-stat-value"><?= (int)$completedOrders ?></div>
        <div class="admin-stat-label">Completed Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="admin-stat-card">
      <div class="admin-stat-icon" style="background: var(--cc-purple-dark);"><i class="bi bi-currency-dollar"></i></div>
      <div>
        <div class="admin-stat-value"><?= format_price($totalSales) ?></div>
        <div class="admin-stat-label">Total Sales</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Recent orders -->
  <div class="col-lg-7">
    <div class="cc-card p-0 overflow-hidden">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Recent Orders</h2>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="small">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-3">Order #</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
              <th class="pe-3">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recentOrders): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td class="ps-3">
                  <a href="<?= BASE_URL ?>/admin/order-details.php?id=<?= (int)$o['id'] ?>"><?= e($o['order_number']) ?></a>
                </td>
                <td><?= e($o['full_name']) ?></td>
                <td><?= format_price($o['total']) ?></td>
                <td><span class="cc-status cc-status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                <td class="pe-3 small text-muted"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent users -->
  <div class="col-lg-5">
    <div class="cc-card p-0 overflow-hidden">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Recent Users</h2>
        <a href="<?= BASE_URL ?>/admin/users.php" class="small">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr><th class="ps-3">Name</th><th>Email</th><th class="pe-3">Joined</th></tr>
          </thead>
          <tbody>
            <?php if (!$recentUsers): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">No users yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentUsers as $u): ?>
              <tr>
                <td class="ps-3"><?= e($u['full_name']) ?></td>
                <td class="small text-muted"><?= e($u['email']) ?></td>
                <td class="pe-3 small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>