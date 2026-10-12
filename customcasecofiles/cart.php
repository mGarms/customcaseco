<?php
// =====================================================
// SHOPPING CART
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$pageTitle  = 'Your Cart';
$activePage = 'cart';
require_once 'includes/header.php';

$stmt = $pdo->prepare(
    "SELECT ci.id AS cart_item_id, ci.quantity,
            p.id AS product_id, p.name AS product_name, p.price AS product_price,
            p.image AS product_image, p.stock AS product_stock,
            cd.id AS design_id, cd.uploaded_image AS design_image, cd.custom_text,
            cd.price AS design_price,
            cdesign.name AS design_name, cdesign.color_hex,
            m.name AS model_name, b.name AS brand_name
     FROM cart_items ci
     JOIN carts c ON c.id = ci.cart_id
     LEFT JOIN products p ON p.id = ci.product_id
     LEFT JOIN custom_designs cd ON cd.id = ci.custom_design_id
     LEFT JOIN case_designs cdesign ON cdesign.id = cd.case_design_id
     LEFT JOIN phone_models m ON m.id = cd.phone_model_id
     LEFT JOIN phone_brands b ON b.id = m.brand_id
     WHERE c.user_id = :uid
     ORDER BY ci.id DESC"
);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$items = $stmt->fetchAll();

$subtotal = 0;
foreach ($items as $item) {
    $unitPrice = $item['product_id'] ? $item['product_price'] : $item['design_price'];
    $subtotal += $unitPrice * $item['quantity'];
}

$firstDiscountEligible = false;
if ($subtotal > 0) {
    $stmt = $pdo->prepare('SELECT first_discount_used FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $firstDiscountEligible = !$stmt->fetchColumn();
}
?>

<div class="cc-page-header">
  <div class="container">
    <h1><i class="bi bi-bag"></i> Your Cart</h1>
    <p><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> in your cart</p>
  </div>
</div>

<div class="container cc-section pt-0">

  <?php if (!$items): ?>
    <div class="cc-card p-5 text-center">
      <i class="bi bi-bag-x" style="font-size: 2.5rem; color: var(--cc-purple-pastel);"></i>
      <h2 class="h5 mt-3">Your cart is empty</h2>
      <p class="text-muted mb-3">Browse the shop or design your own case to get started.</p>
      <div class="d-flex gap-2 justify-content-center">
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-primary">Browse Shop</a>
        <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-outline">Design a Case</a>
      </div>
    </div>
  <?php else: ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <?php foreach ($items as $item):
          $isProduct = (bool)$item['product_id'];
          $unitPrice = $isProduct ? $item['product_price'] : $item['design_price'];
          $lineTotal = $unitPrice * $item['quantity'];
          $maxQty = $isProduct ? max(1, (int)$item['product_stock']) : 10;
        ?>
          <div class="cc-card p-3 mb-3">
            <div class="row g-3 align-items-center">

              <div class="col-3 col-md-2">
                <div class="cc-product-img" style="border-radius: 12px; aspect-ratio: 1/1;">
                  <?php if ($isProduct && $item['product_image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/products/<?= e($item['product_image']) ?>" alt="">
                  <?php elseif (!$isProduct && $item['design_image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/custom_designs/<?= e($item['design_image']) ?>" alt="">
                  <?php else: ?>
                    <div style="width:100%; height:100%; background: <?= e($item['color_hex'] ?? '#c4b5fd') ?>;
                                display:flex; align-items:center; justify-content:center;">
                      <i class="bi bi-phone text-white"></i>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-9 col-md-4">
                <?php if ($isProduct): ?>
                  <h3 class="h6 mb-1"><?= e($item['product_name']) ?></h3>
                  <span class="cc-tag">Ready-made</span>
                <?php else: ?>
                  <h3 class="h6 mb-1">Custom Case</h3>
                  <p class="small text-muted mb-1">
                    <?= e($item['brand_name'] . ' ' . $item['model_name']) ?> · <?= e($item['design_name']) ?>
                  </p>
                  <?php if ($item['custom_text']): ?>
                    <p class="small mb-0"><i class="bi bi-fonts"></i> "<?= e($item['custom_text']) ?>"</p>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <div class="col-6 col-md-2">
                <form action="<?= BASE_URL ?>/actions/cart-update.php" method="post" class="d-flex align-items-center gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int)$item['cart_item_id'] ?>">
                  <select name="quantity" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                    <?php for ($i = 1; $i <= $maxQty; $i++): ?>
                      <option value="<?= $i ?>" <?= $i == $item['quantity'] ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </form>
                <?php if ($isProduct): ?>
                  <p class="small text-muted mt-1 mb-0"><?= (int)$item['product_stock'] ?> in stock</p>
                <?php endif; ?>
              </div>

              <div class="col-4 col-md-2 text-md-end">
                <span class="cc-price"><?= format_price($lineTotal) ?></span>
                <p class="small text-muted mb-0"><?= format_price($unitPrice) ?> each</p>
              </div>

              <div class="col-2 col-md-2 text-end">
                <form action="<?= BASE_URL ?>/actions/cart-remove.php" method="post"
                      data-confirm="Remove this item from your cart?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int)$item['cart_item_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove item">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>

            </div>
          </div>
        <?php endforeach; ?>

        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline"><i class="bi bi-arrow-left"></i> Continue Shopping</a>
      </div>

      <!-- Order summary -->
      <div class="col-lg-4">
        <div class="cc-card p-4" style="position: sticky; top: 90px;">
          <h2 class="h6 mb-3">Order Summary</h2>

          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span><?= format_price($subtotal) ?></span>
          </div>

          <?php if ($firstDiscountEligible): ?>
            <div class="alert alert-success small py-2 mb-3">
              <i class="bi bi-gift"></i> 20% off your first order will be applied at checkout!
            </div>
          <?php endif; ?>

          <hr>
          <div class="d-flex justify-content-between fw-bold mb-4">
            <span>Estimated Total</span>
            <span class="cc-price"><?= format_price($subtotal) ?></span>
          </div>

          <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-cc-primary w-100 btn-lg">
            Proceed to Checkout <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>