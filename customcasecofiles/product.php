<?php
// =====================================================
// PRODUCT DETAIL PAGE - product.php?id=1
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

// ---------- Validate the ID from the URL ----------
$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once 'includes/header.php';
    echo '<div class="container cc-section text-center">
            <h1 class="h3">Product Not Found</h1>
            <p class="text-muted">That link looks incorrect.</p>
            <a href="' . BASE_URL . '/shop.php" class="btn btn-cc-primary">Back to Shop</a>
          </div>';
    require_once 'includes/footer.php';
    exit;
}
$id = (int)$id;

// ---------- Fetch the product ----------
$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.description, p.price, p.stock, p.image,
            c.name AS category_name,
            m.id AS model_id, m.name AS model_name, b.name AS brand_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN phone_models m ON m.id = p.phone_model_id
     LEFT JOIN phone_brands b ON b.id = m.brand_id
     WHERE p.id = :id AND p.is_active = 1"
);
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once 'includes/header.php';
    echo '<div class="container cc-section text-center">
            <h1 class="h3">Product Not Found</h1>
            <p class="text-muted">This case may have been removed or is no longer available.</p>
            <a href="' . BASE_URL . '/shop.php" class="btn btn-cc-primary">Back to Shop</a>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

$pageTitle  = $product['name'];
$activePage = 'shop';
require_once 'includes/header.php';

$inStock  = $product['stock'] > 0;
$maxQty   = min($product['stock'], 10); // cap the selector at 10 even if stock is huge
?>

<div class="cc-page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2 small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop.php">Shop</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
      </ol>
    </nav>
    <h1><?= e($product['name']) ?></h1>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row g-5">

    <!-- Large image -->
    <div class="col-lg-6">
      <div class="cc-card cc-product-img" style="border-radius: var(--cc-radius); aspect-ratio: 1/1;">
        <?php if ($product['image']): ?>
          <img src="<?= BASE_URL ?>/uploads/products/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
        <?php else: ?>
          <i class="bi bi-phone" style="font-size: 6rem;"></i>
        <?php endif; ?>
      </div>
    </div>

    <!-- Details -->
    <div class="col-lg-6">
      <span class="cc-tag mb-2 d-inline-block"><?= e($product['category_name']) ?></span>
      <h2 class="h3"><?= e($product['name']) ?></h2>

      <p class="text-muted mb-3">
        <?= $product['description'] ? nl2br(e($product['description'])) : 'No description provided for this case.' ?>
      </p>

      <div class="cc-card p-3 mb-4">
        <div class="row g-2 small">
          <div class="col-6">
            <span class="text-muted d-block">Phone Model</span>
            <span class="fw-semibold">
              <?= $product['model_name'] ? e($product['brand_name'] . ' ' . $product['model_name']) : 'Universal fit' ?>
            </span>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Case Type</span>
            <span class="fw-semibold"><?= e($product['category_name']) ?></span>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Available Stock</span>
            <span class="fw-semibold">
              <?= $inStock ? (int)$product['stock'] . ' in stock' : 'Out of stock' ?>
            </span>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Price</span>
            <span class="cc-price"><?= format_price($product['price']) ?></span>
          </div>
        </div>
      </div>

      <?php if ($inStock): ?>
        <form action="<?= BASE_URL ?>/actions/card-add.php" method="post" data-once class="mb-3">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

          <label class="form-label" for="quantity">Quantity</label>
          <div class="d-flex align-items-center gap-2 mb-3" style="max-width: 160px;">
            <select class="form-select" id="quantity" name="quantity">
              <?php for ($i = 1; $i <= $maxQty; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-cc-primary btn-lg">
              <i class="bi bi-bag-plus"></i> Add to Cart
            </button>
            <a href="<?= BASE_URL ?>/customize.php<?= $product['model_id'] ? '?model=' . (int)$product['model_id'] : '' ?>"
               class="btn btn-cc-outline btn-lg">
              <i class="bi bi-magic"></i> Customize This Case
            </a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-warning">This case is currently out of stock.</div>
        <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-outline btn-lg">
          <i class="bi bi-magic"></i> Design Your Own Instead
        </a>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once 'includes/footer.php'; ?>