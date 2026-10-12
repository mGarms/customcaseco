<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$validStatuses = ['pending','processing','customized','shipped','delivered','cancelled'];

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(o.order_number LIKE :q OR u.full_name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
if (in_array($status, $validStatuses, true)) {
    $where[] = 'o.status = :status';
    $params[':status'] = $status;
}

$stmt = $pdo->prepare(
    "SELECT o.id, o.order_number, o.total, o.status, o.created_at, u.full_name, u.email
     FROM orders o JOIN users u ON u.id = o.user_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY o.created_at DESC"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
$activeAdminPage = 'orders';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="get" class="d-flex gap-2 mb-3">
  <input type="text" name="q" class="form-control" placeholder="Search order #, name, or email..." value="<?= e($search) ?>">
  <select name="status" class="form-select" style="max-width:200px;">
    <option value="">All Statuses</option>
    <?php foreach ($validStatuses as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-cc-outline"><i class="bi bi-search"></i></button>
  <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-cc-outline">Clear</a>
</form>

<div class="cc-card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th class="ps-3">Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th class="pe-3 text-end">Action</th></tr></thead>
      <tbody>
        <?php if (!$orders): ?><tr><td colspan="6" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="ps-3 fw-semibold"><?= e($o['order_number']) ?></td>
            <td><?= e($o['full_name']) ?><br><span class="small text-muted"><?= e($o['email']) ?></span></td>
            <td><?= format_price($o['total']) ?></td>
            <td><span class="cc-status cc-status-<?= e($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
            <td class="small text-muted"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
            <td class="pe-3 text-end">
              <a href="<?= BASE_URL ?>/admin/order-details.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-cc-outline">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>