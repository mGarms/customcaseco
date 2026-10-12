<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload-helper.php';
require_admin();

$id = $_GET['id'] ?? '';
if (!ctype_digit((string)$id)) { set_flash('danger', 'Invalid product.'); redirect('admin/products.php'); }
$id = (int)$id;

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();
if (!$product) { set_flash('danger', 'Product not found.'); redirect('admin/products.php'); }

$errors = [];
$old = [
    'name' => $product['name'], 'description' => $product['description'], 'price' => $product['price'],
    'stock' => $product['stock'], 'category_id' => $product['category_id'], 'phone_model_id' => $product['phone_model_id'],
    'is_featured' => $product['is_featured'], 'is_active' => $product['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) $errors[] = 'Session expired. Please try again.';

    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $stock       = $_POST['stock'] ?? '';
    $categoryId  = $_POST['category_id'] ?? '';
    $modelId     = $_POST['phone_model_id'] ?? '';
    $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    $old = ['name' => $name, 'description' => $description, 'price' => $price, 'stock' => $stock,
            'category_id' => $categoryId, 'phone_model_id' => $modelId, 'is_featured' => $isFeatured, 'is_active' => $isActive];

    if ($name === '') $errors[] = 'Product name is required.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be a valid positive number.';
    if (!ctype_digit((string)$stock) || $stock < 0) $errors[] = 'Stock must be a valid whole number.';
    if (!ctype_digit((string)$categoryId)) $errors[] = 'Please select a category.';

    $modelId = ($modelId !== '' && ctype_digit((string)$modelId)) ? (int)$modelId : null;

    $newImage = $product['image']; // unchanged by default
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        $upload = handle_image_upload($_FILES['image'], __DIR__ . '/../uploads/products/', 'product_');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        } else {
            $newImage = $upload['filename'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id=:cat, phone_model_id=:model, name=:name, description=:desc,
             price=:price, stock=:stock, image=:image, is_featured=:featured, is_active=:active
             WHERE id=:id'
        );
        $stmt->execute([
            ':cat' => $categoryId, ':model' => $modelId, ':name' => $name, ':desc' => $description ?: null,
            ':price' => $price, ':stock' => $stock, ':image' => $newImage,
            ':featured' => $isFeatured, ':active' => $isActive, ':id' => $id,
        ]);

        // Delete the old image file only after a successful update with a new one
        if ($newImage !== $product['image'] && $product['image']) {
            $old_file = __DIR__ . '/../uploads/products/' . $product['image'];
            if (file_exists($old_file)) unlink($old_file);
        }

        set_flash('success', 'Product updated successfully.');
        redirect('admin/products.php');
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$brands     = $pdo->query('SELECT id, name FROM phone_brands ORDER BY name')->fetchAll();
$models     = $pdo->query('SELECT id, brand_id, name FROM phone_models ORDER BY name')->fetchAll();
$modelsJson = json_encode($models);

$pageTitle = 'Edit Product';
$activeAdminPage = 'products';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="cc-card p-4" style="max-width:700px;">
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <?php if ($product['image']): ?>
    <img src="<?= BASE_URL ?>/uploads/products/<?= e($product['image']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:12px;" class="mb-3">
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" data-once novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" class="form-control" name="name" value="<?= e($old['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="3"><?= e($old['description']) ?></textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Price</label>
        <input type="number" step="0.01" min="0" class="form-control" name="price" value="<?= e($old['price']) ?>" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Stock</label>
        <input type="number" min="0" class="form-control" name="stock" value="<?= e($old['stock']) ?>" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
      <select class="form-select" name="category_id" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === (string)$old['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Phone Brand</label>
        <select class="form-select" id="brand">
          <option value="">Universal (no specific brand)</option>
          <?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Phone Model</label>
        <select class="form-select" id="model" name="phone_model_id">
          <option value="">Select brand first</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Replace Image (optional)</label>
      <input type="file" class="form-control" name="image" accept="image/jpeg,image/png">
    </div>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?= $old['is_featured'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_featured">Featured on homepage</label>
    </div>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $old['is_active'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_active">Active (visible in shop)</label>
    </div>
    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
    <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-cc-outline">Cancel</a>
  </form>
</div>

<script>
(function () {
  var allModels = <?= $modelsJson ?>;
  var preselect = '<?= e($old['phone_model_id']) ?>';
  var brand = document.getElementById('brand'), model = document.getElementById('model');

  function refresh() {
    model.innerHTML = '';
    if (!brand.value) { model.innerHTML = '<option value="">Select brand first</option>'; return; }
    model.innerHTML = '<option value="">Select model</option>';
    allModels.filter(function (m) { return String(m.brand_id) === brand.value; }).forEach(function (m) {
      var opt = document.createElement('option');
      opt.value = m.id; opt.textContent = m.name;
      if (String(m.id) === preselect) opt.selected = true;
      model.appendChild(opt);
    });
  }
  brand.addEventListener('change', refresh);

  if (preselect) {
    var found = allModels.find(function (m) { return String(m.id) === preselect; });
    if (found) { brand.value = found.brand_id; refresh(); }
  } else { refresh(); }
})();
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>