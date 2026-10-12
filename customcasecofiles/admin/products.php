<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

$where  = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = 'p.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
if ($category !== '' && ctype_digit($category)) {
    $where[] = 'p.category_id = :category';
    $params[':category'] = (int)$category;
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.price, p.stock, p.image, p.is_active, p.is_featured,
            c.name AS category_name, b.name AS brand_name, m.name AS model_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN phone_models m ON m.id = p.phone_model_id
     LEFT JOIN phone_brands b ON b.id = m.brand_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY p.created_at DESC"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Products';
$activeAdminPage = 'products';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2 flex-grow-1 me-3">
    <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
    <select name="category" class="form-select" style="max-width:200px;">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === $category ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-cc-outline"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= BASE_URL ?>/admin/add-product.php" class="btn btn-admin-primary text-nowrap">
    <i class="bi bi-plus-lg"></i> Add Product
  </a>
</div>

<div class="cc-card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th class="ps-3">Image</th><th>Name</th><th>Category</th><th>Model</th>
          <th>Price</th><th>Stock</th><th>Status</th><th class="pe-3 text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$products): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td class="ps-3">
              <div style="width:44px;height:44px;border-radius:8px;overflow:hidden;background:var(--cc-lavender-bg);display:flex;align-items:center;justify-content:center;">
                <?php if ($p['image']): ?>
                  <img src="<?= BASE_URL ?>/uploads/products/<?= e($p['image']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?><i class="bi bi-phone"></i><?php endif; ?>
              </div>
            </td>
            <td><?= e($p['name']) ?> <?php if ($p['is_featured']): ?><span class="cc-tag">Featured</span><?php endif; ?></td>
            <td><?= e($p['category_name']) ?></td>
            <td class="small text-muted"><?= $p['model_name'] ? e($p['brand_name'] . ' ' . $p['model_name']) : 'Universal' ?></td>
            <td><?= format_price($p['price']) ?></td>
            <td><?= (int)$p['stock'] ?></td>
            <td>
              <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="pe-3 text-end">
              <a href="<?= BASE_URL ?>/admin/edit-product.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-cc-outline">
                <i class="bi bi-pencil"></i>
              </a>
              <form action="<?= BASE_URL ?>/admin/delete-product.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Delete '<?= e($p['name']) ?>'? This cannot be undone.">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>