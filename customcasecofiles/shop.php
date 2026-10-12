<?php
// =====================================================
// SHOP PAGE - product grid with search, filters, sorting
// =====================================================
$pageTitle  = 'Shop';
$activePage = 'shop';
require_once 'config/database.php';
require_once 'includes/header.php';

// ---------- Read filters from the URL (GET) ----------
$search    = trim($_GET['q'] ?? '');
$category  = $_GET['category'] ?? '';
$brand     = $_GET['brand'] ?? '';
$model     = $_GET['model'] ?? '';
$sort      = $_GET['sort'] ?? 'newest';
$inStockOnly = isset($_GET['in_stock']);

$allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

// ---------- Build the product query safely ----------
$where  = ['p.is_active = 1'];
$params = [];

if ($search !== '') {
    $where[] = 'p.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
if ($category !== '' && ctype_digit($category)) {
    $where[] = 'p.category_id = :category';
    $params[':category'] = (int)$category;
}
if ($brand !== '' && ctype_digit($brand)) {
    $where[] = 'b.id = :brand';
    $params[':brand'] = (int)$brand;
}
if ($model !== '' && ctype_digit($model)) {
    $where[] = 'p.phone_model_id = :model';
    $params[':model'] = (int)$model;
}
if ($inStockOnly) {
    $where[] = 'p.stock > 0';
}

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    default      => 'p.created_at DESC',
};

$sql = "SELECT p.id, p.name, p.price, p.stock, p.image,
               c.name AS category_name,
               m.name AS model_name, b.name AS brand_name
        FROM products p
        JOIN categories c   ON c.id = p.category_id
        LEFT JOIN phone_models m ON m.id = p.phone_model_id
        LEFT JOIN phone_brands b ON b.id = m.brand_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ---------- Data for the filter dropdowns ----------
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
$brands     = $pdo->query('SELECT id, name FROM phone_brands WHERE is_active = 1 ORDER BY name')->fetchAll();
$models     = $pdo->query('SELECT id, brand_id, name FROM phone_models WHERE is_active = 1 ORDER BY name')->fetchAll();

$modelsJson = json_encode($models);
?>

<div class="cc-page-header">
  <div class="container">
    <h1>Shop All Cases</h1>
    <p>Find the perfect ready-made case for your phone</p>
  </div>
</div>

<div class="container cc-section pt-0">

  <!-- =========== FILTER BAR =========== -->
  <div class="cc-card p-3 p-md-4 mb-4">
    <form method="get" action="<?= BASE_URL ?>/shop.php" id="filterForm" class="row g-3 align-items-end">

      <div class="col-12 col-md-4">
        <label class="form-label" for="q">Search</label>
        <input type="text" class="form-control" id="q" name="q"
               placeholder="Search cases..." value="<?= e($search) ?>">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="category">Category</label>
        <select class="form-select" id="category" name="category">
          <option value="">All</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= (string)$cat['id'] === $category ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="brand">Brand</label>
        <select class="form-select" id="brand" name="brand">
          <option value="">All</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= (string)$b['id'] === $brand ? 'selected' : '' ?>>
              <?= e($b['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="model">Model</label>
        <select class="form-select" id="model" name="model">
          <option value="">All</option>
          <?php foreach ($models as $m): ?>
            <option value="<?= (int)$m['id'] ?>"
                    data-brand="<?= (int)$m['brand_id'] ?>"
                    <?= (string)$m['id'] === $model ? 'selected' : '' ?>>
              <?= e($m['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="sort">Sort by</label>
        <select class="form-select" id="sort" name="sort">
          <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
          <option value="name_asc"   <?= $sort === 'name_asc'   ? 'selected' : '' ?>>Name: A to Z</option>
        </select>
      </div>

      <div class="col-12 d-flex flex-wrap align-items-center gap-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="in_stock" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
          <label class="form-check-label" for="in_stock">In Stock Only</label>
        </div>
        <button type="submit" class="btn btn-cc-primary"><i class="bi bi-funnel"></i> Apply Filters</button>
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline">Clear</a>
      </div>
    </form>
  </div>

  <!-- =========== RESULTS COUNT =========== -->
  <p class="text-muted mb-3">
    <?= count($products) ?> case<?= count($products) === 1 ? '' : 's' ?> found
  </p>

  <!-- =========== PRODUCT GRID =========== -->
  <?php if ($products): ?>
    <div class="row g-4">
      <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="cc-card cc-card-hover cc-product-card">

            <div class="cc-product-img">
              <?php if ($product['image']): ?>
                <img src="<?= BASE_URL ?>/uploads/products/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
              <?php else: ?>
                <i class="bi bi-phone"></i>
              <?php endif; ?>
            </div>

            <div class="cc-product-body">
              <span class="cc-tag mb-2 align-self-start"><?= e($product['category_name']) ?></span>
              <h3 class="h6 mb-1"><?= e($product['name']) ?></h3>

              <p class="small text-muted mb-2">
                <i class="bi bi-phone"></i>
                <?= $product['model_name'] ? e($product['brand_name'] . ' ' . $product['model_name']) : 'Universal fit' ?>
              </p>

              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="cc-price"><?= format_price($product['price']) ?></span>
                <?php if ($product['stock'] > 0): ?>
                  <span class="small text-muted"><?= (int)$product['stock'] ?> in stock</span>
                <?php else: ?>
                  <span class="small text-danger fw-semibold">Out of stock</span>
                <?php endif; ?>
              </div>

              <div class="d-grid gap-2 mt-auto">
                <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$product['id'] ?>"
                   class="btn btn-cc-outline btn-sm">View Details</a>

                <form action="<?= BASE_URL ?>/actions/card-add.php" method="post" data-once>
                  <?= csrf_field() ?>
                  <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="btn btn-cc-primary btn-sm w-100"
                          <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-bag-plus"></i> Add to Cart
                  </button>
                </form>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="cc-card p-5 text-center">
      <i class="bi bi-search" style="font-size: 2.5rem; color: var(--cc-purple-pastel);"></i>
      <h3 class="h5 mt-3">No cases match your filters</h3>
      <p class="text-muted mb-3">Try clearing a filter or searching a different term.</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-primary">Clear Filters</a>
    </div>
  <?php endif; ?>

</div>

<script>
  (function () {
    var allModels   = <?= $modelsJson ?>;
    var brandSelect = document.getElementById('brand');
    var modelSelect = document.getElementById('model');

    function refreshModelOptions() {
      var selectedBrand = brandSelect.value;
      var currentModel   = modelSelect.value;

      modelSelect.innerHTML = '<option value="">All</option>';
      allModels.forEach(function (m) {
        if (selectedBrand === '' || String(m.brand_id) === selectedBrand) {
          var opt = document.createElement('option');
          opt.value = m.id;
          opt.textContent = m.name;
          if (String(m.id) === currentModel) opt.selected = true;
          modelSelect.appendChild(opt);
        }
      });
    }

    brandSelect.addEventListener('change', function () {
      modelSelect.value = '';
      refreshModelOptions();
    });

    refreshModelOptions();
  })();
</script>

<?php require_once 'includes/footer.php'; ?>
