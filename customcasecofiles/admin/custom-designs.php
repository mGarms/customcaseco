<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ---------- Handle delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = $_POST['id'] ?? '';
    if (ctype_digit((string)$id)) {
        try {
            $stmt = $pdo->prepare('SELECT uploaded_image FROM custom_designs WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $design = $stmt->fetch();

            $stmt = $pdo->prepare('DELETE FROM custom_designs WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if ($design && $design['uploaded_image']) {
                $file = __DIR__ . '/../uploads/custom_designs/' . $design['uploaded_image'];
                if (file_exists($file)) unlink($file);
            }
            set_flash('success', 'Custom design deleted.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                set_flash('danger', 'This design cannot be deleted because it is part of a placed order.');
            } else {
                set_flash('danger', 'Database error. Please try again.');
            }
        }
    }
    redirect('admin/custom-designs.php');
}

// ---------- Search ----------
$search = trim($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE :q OR u.email LIKE :q OR cd.custom_text LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$stmt = $pdo->prepare(
    "SELECT cd.id, cd.uploaded_image, cd.custom_text, cd.text_color, cd.price, cd.created_at,
            u.full_name AS customer_name, u.email AS customer_email,
            m.name AS model_name, b.name AS brand_name,
            cdesign.name AS style_name, cdesign.color_hex,
            (SELECT oi.order_id FROM order_items oi WHERE oi.custom_design_id = cd.id LIMIT 1) AS linked_order_id,
            (SELECT o.order_number FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.custom_design_id = cd.id LIMIT 1) AS linked_order_number
     FROM custom_designs cd
     JOIN users u ON u.id = cd.user_id
     JOIN phone_models m ON m.id = cd.phone_model_id
     JOIN phone_brands b ON b.id = m.brand_id
     JOIN case_designs cdesign ON cdesign.id = cd.case_design_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY cd.created_at DESC"
);
$stmt->execute($params);
$designs = $stmt->fetchAll();

$pageTitle = 'Custom Designs';
$activeAdminPage = 'custom-designs';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="get" class="d-flex gap-2 mb-3">
  <input type="text" name="q" class="form-control" placeholder="Search by customer, email, or custom text..." value="<?= e($search) ?>">
  <button class="btn btn-cc-outline"><i class="bi bi-search"></i></button>
  <a href="<?= BASE_URL ?>/admin/custom-designs.php" class="btn btn-cc-outline">Clear</a>
</form>

<?php if (!$designs): ?>
  <div class="cc-card p-5 text-center">
    <i class="bi bi-magic" style="font-size: 2.5rem; color: var(--cc-purple-pastel);"></i>
    <p class="text-muted mt-2 mb-0">No custom designs found.</p>
  </div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($designs as $d): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="cc-card p-0 overflow-hidden h-100 d-flex flex-column">

          <div style="aspect-ratio:1/1; background:<?= e($d['color_hex']) ?>; position:relative; display:flex; align-items:center; justify-content:center;">
            <?php if ($d['uploaded_image']): ?>
              <img src="<?= BASE_URL ?>/uploads/custom_designs/<?= e($d['uploaded_image']) ?>"
                   style="max-width:70%; max-height:60%; border-radius:6px; object-fit:cover;" alt="">
            <?php else: ?>
              <i class="bi bi-phone text-white" style="font-size:2rem;"></i>
            <?php endif; ?>
            <?php if ($d['custom_text']): ?>
              <span style="position:absolute; bottom:8%; left:50%; transform:translateX(-50%); width:85%;
                           text-align:center; font-weight:700; font-size:.85rem; color:<?= e($d['text_color']) ?>;
                           text-shadow:0 1px 3px rgba(0,0,0,.4); word-wrap:break-word;">
                <?= e($d['custom_text']) ?>
              </span>
            <?php endif; ?>
          </div>

          <div class="p-3 flex-grow-1 d-flex flex-column">
            <p class="small fw-semibold mb-1"><?= e($d['customer_name']) ?></p>
            <p class="small text-muted mb-1"><?= e($d['brand_name'] . ' ' . $d['model_name']) ?> · <?= e($d['style_name']) ?></p>
            <p class="small text-muted mb-2"><?= date('M j, Y', strtotime($d['created_at'])) ?></p>

            <?php if ($d['linked_order_number']): ?>
              <a href="<?= BASE_URL ?>/admin/order-details.php?id=<?= (int)$d['linked_order_id'] ?>" class="cc-tag mb-2 d-inline-block">
                Order <?= e($d['linked_order_number']) ?>
              </a>
            <?php else: ?>
              <span class="badge bg-secondary mb-2 align-self-start">In cart only</span>
            <?php endif; ?>

            <div class="mt-auto d-flex justify-content-between align-items-center">
              <span class="cc-price small"><?= format_price($d['price']) ?></span>
              <?php if (!$d['linked_order_number']): ?>
                <form action="<?= BASE_URL ?>/admin/custom-designs.php" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"
                          data-confirm="Delete this custom design? This also removes it from the customer's cart.">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>