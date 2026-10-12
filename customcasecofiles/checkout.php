<?php
// =====================================================
// CHECKOUT
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();
$userId = $_SESSION['user_id'];

// ---------- Load the cart ----------
$stmt = $pdo->prepare(
    "SELECT ci.id AS cart_item_id, ci.quantity,
            p.id AS product_id, p.name AS product_name, p.price AS product_price, p.stock AS product_stock,
            cd.id AS design_id, cd.price AS design_price, cd.custom_text,
            cdesign.name AS design_name, m.name AS model_name, b.name AS brand_name
     FROM cart_items ci
     JOIN carts c ON c.id = ci.cart_id
     LEFT JOIN products p ON p.id = ci.product_id
     LEFT JOIN custom_designs cd ON cd.id = ci.custom_design_id
     LEFT JOIN case_designs cdesign ON cdesign.id = cd.case_design_id
     LEFT JOIN phone_models m ON m.id = cd.phone_model_id
     LEFT JOIN phone_brands b ON b.id = m.brand_id
     WHERE c.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$items = $stmt->fetchAll();

if (!$items) {
    set_flash('warning', 'Your cart is empty.');
    redirect('cart.php');
}

// ---------- Load user info to prefill the form ----------
$stmt = $pdo->prepare('SELECT full_name, phone, address, first_discount_used FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$subtotal = 0;
foreach ($items as $item) {
    $unitPrice = $item['product_id'] ? $item['product_price'] : $item['design_price'];
    $subtotal += $unitPrice * $item['quantity'];
}
$willGetDiscount = !$user['first_discount_used'];
$discount = $willGetDiscount ? round($subtotal * FIRST_ORDER_DISCOUNT, 2) : 0;
$total = $subtotal - $discount;

$errors = [];
$old = [
    'shipping_name'    => $user['full_name'],
    'shipping_phone'   => $user['phone'],
    'shipping_address' => $user['address'],
    'payment_method'   => 'cod',
];

// =====================================================
// HANDLE ORDER PLACEMENT
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $shippingName    = trim($_POST['shipping_name'] ?? '');
    $shippingPhone   = trim($_POST['shipping_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod   = $_POST['payment_method'] ?? 'cod';

    $old = compact('shippingName', 'shippingPhone', 'shippingAddress', 'paymentMethod');
    $old = [
        'shipping_name' => $shippingName, 'shipping_phone' => $shippingPhone,
        'shipping_address' => $shippingAddress, 'payment_method' => $paymentMethod,
    ];

    if ($shippingName === '')    $errors[] = 'Full name is required.';
    if ($shippingPhone === '')   $errors[] = 'Contact number is required.';
    if ($shippingAddress === '') $errors[] = 'Delivery address is required.';
    if (!in_array($paymentMethod, ['cod', 'gcash'], true)) $errors[] = 'Please choose a valid payment method.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Re-check stock for every product line INSIDE the transaction,
            // in case something changed since the cart page loaded.
            foreach ($items as $item) {
                if ($item['product_id']) {
                    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
                    $stmt->execute([':id' => $item['product_id']]);
                    $currentStock = $stmt->fetchColumn();

                    if ($currentStock === false || $currentStock < $item['quantity']) {
                        throw new RuntimeException(
                            'Sorry, "' . $item['product_name'] . '" no longer has enough stock. Please update your cart.'
                        );
                    }
                }
            }

            // Recompute totals server-side (never trust anything from the browser)
            $subtotal = 0;
            foreach ($items as $item) {
                $unitPrice = $item['product_id'] ? $item['product_price'] : $item['design_price'];
                $subtotal += $unitPrice * $item['quantity'];
            }
            $willGetDiscount = !$user['first_discount_used'];
            $discount = $willGetDiscount ? round($subtotal * FIRST_ORDER_DISCOUNT, 2) : 0;
            $total = $subtotal - $discount;

            $orderNumber = 'CCC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $stmt = $pdo->prepare(
                'INSERT INTO orders
                    (order_number, user_id, subtotal, discount, shipping_fee, total, status,
                     shipping_name, shipping_phone, shipping_address)
                 VALUES
                    (:order_number, :user_id, :subtotal, :discount, 0, :total, "pending",
                     :shipping_name, :shipping_phone, :shipping_address)'
            );
            $stmt->execute([
                ':order_number'      => $orderNumber,
                ':user_id'           => $userId,
                ':subtotal'          => $subtotal,
                ':discount'          => $discount,
                ':total'             => $total,
                ':shipping_name'     => $shippingName,
                ':shipping_phone'    => $shippingPhone,
                ':shipping_address'  => $shippingAddress,
            ]);
            $orderId = $pdo->lastInsertId();

            // Order items + stock reduction
            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, custom_design_id, item_name, unit_price, quantity, line_total)
                 VALUES (:order_id, :product_id, :design_id, :item_name, :unit_price, :qty, :line_total)'
            );
            $stockStmt = $pdo->prepare(
                'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty2'
            );

            foreach ($items as $item) {
                if ($item['product_id']) {
                    $unitPrice = $item['product_price'];
                    $itemName  = $item['product_name'];

                    $stockStmt->execute([':qty' => $item['quantity'], ':id' => $item['product_id'], ':qty2' => $item['quantity']]);
                    if ($stockStmt->rowCount() === 0) {
                        throw new RuntimeException('Stock changed while placing your order. Please try again.');
                    }
                } else {
                    $unitPrice = $item['design_price'];
                    $itemName  = 'Custom Case - ' . $item['brand_name'] . ' ' . $item['model_name']
                               . ' (' . $item['design_name'] . ')';
                }

                $itemStmt->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $item['product_id'] ?: null,
                    ':design_id'  => $item['design_id'] ?: null,
                    ':item_name'  => $itemName,
                    ':unit_price' => $unitPrice,
                    ':qty'        => $item['quantity'],
                    ':line_total' => $unitPrice * $item['quantity'],
                ]);
            }

            // Payment record (no real gateway — recorded as pending for both methods)
            $stmt = $pdo->prepare(
                'INSERT INTO payments (order_id, method, amount, status) VALUES (:order_id, :method, :amount, "pending")'
            );
            $stmt->execute([':order_id' => $orderId, ':method' => $paymentMethod, ':amount' => $total]);

            // Status history entry
            $stmt = $pdo->prepare(
                'INSERT INTO order_status_history (order_id, status, note) VALUES (:order_id, "pending", "Order placed")'
            );
            $stmt->execute([':order_id' => $orderId]);

            // Mark the first-order discount as used
            if ($willGetDiscount) {
                $stmt = $pdo->prepare('UPDATE users SET first_discount_used = 1 WHERE id = :id');
                $stmt->execute([':id' => $userId]);
            }

            // Clear the cart
            $stmt = $pdo->prepare(
                'DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);

            $pdo->commit();

            redirect('order-success.php?order=' . urlencode($orderNumber));

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Checkout failed: ' . $e->getMessage());
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Something went wrong placing your order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <h1><i class="bi bi-credit-card"></i> Checkout</h1>
    <p>Review your order and confirm delivery details</p>
  </div>
</div>

<div class="container cc-section pt-0">

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/checkout.php" data-once novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
      <div class="col-lg-7">

        <div class="cc-card p-4 mb-4">
          <h2 class="h6 mb-3">Customer & Delivery Information</h2>

          <div class="mb-3">
            <label class="form-label" for="shipping_name">Full Name</label>
            <input type="text" class="form-control" id="shipping_name" name="shipping_name"
                   value="<?= e($old['shipping_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="shipping_phone">Contact Number</label>
            <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone"
                   value="<?= e($old['shipping_phone']) ?>" required>
          </div>
          <div class="mb-0">
            <label class="form-label" for="shipping_address">Delivery Address</label>
            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?= e($old['shipping_address']) ?></textarea>
          </div>
        </div>

        <div class="cc-card p-4">
          <h2 class="h6 mb-3">Payment Method</h2>

          <div class="form-check mb-2 p-3 border rounded-3">
            <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod"
                   <?= $old['payment_method'] === 'cod' ? 'checked' : '' ?>>
            <label class="form-check-label w-100" for="pay_cod">
              <strong>Cash on Delivery</strong>
              <p class="small text-muted mb-0">Pay in cash when your order arrives.</p>
            </label>
          </div>

          <div class="form-check p-3 border rounded-3">
            <input class="form-check-input" type="radio" name="payment_method" id="pay_gcash" value="gcash"
                   <?= $old['payment_method'] === 'gcash' ? 'checked' : '' ?>>
            <label class="form-check-label w-100" for="pay_gcash">
              <strong>GCash</strong>
              <p class="small text-muted mb-0">
                Demo mode: no real payment is processed. Your order will be marked
                "pending payment" for manual confirmation.
              </p>
            </label>
          </div>
        </div>

      </div>

      <div class="col-lg-5">
        <div class="cc-card p-4" style="position: sticky; top: 90px;">
          <h2 class="h6 mb-3">Order Summary</h2>

          <?php foreach ($items as $item):
            $isProduct = (bool)$item['product_id'];
            $unitPrice = $isProduct ? $item['product_price'] : $item['design_price'];
          ?>
            <div class="d-flex justify-content-between small mb-2">
              <span>
                <?= $isProduct ? e($item['product_name']) : 'Custom: ' . e($item['brand_name'] . ' ' . $item['model_name']) ?>
                × <?= (int)$item['quantity'] ?>
              </span>
              <span><?= format_price($unitPrice * $item['quantity']) ?></span>
            </div>
          <?php endforeach; ?>

          <hr>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span><?= format_price($subtotal) ?></span>
          </div>
          <?php if ($discount > 0): ?>
            <div class="d-flex justify-content-between mb-2 text-success">
              <span>First Order Discount (20%)</span>
              <span>-<?= format_price($discount) ?></span>
            </div>
          <?php endif; ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
            <span>Total</span>
            <span class="cc-price"><?= format_price($total) ?></span>
          </div>

          <button type="submit" class="btn btn-cc-primary w-100 btn-lg">
            <i class="bi bi-check-circle"></i> Place Order
          </button>
          <a href="<?= BASE_URL ?>/cart.php" class="btn btn-cc-outline w-100 mt-2">Back to Cart</a>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>