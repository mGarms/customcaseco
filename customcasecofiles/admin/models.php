<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$errors = [];

// ---------- Handle all POST actions in one place ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_brand') {
            $name = trim($_POST['brand_name'] ?? '');
            if ($name === '') throw new RuntimeException('Brand name is required.');
            $stmt = $pdo->prepare('INSERT INTO phone_brands (name) VALUES (:name)');
            $stmt->execute([':name' => $name]);
            set_flash('success', 'Brand added.');
        }

        elseif ($action === 'edit_brand') {
            $id   = $_POST['brand_id'] ?? '';
            $name = trim($_POST['brand_name'] ?? '');
            if (!ctype_digit((string)$id) || $name === '') throw new RuntimeException('Invalid brand data.');
            $stmt = $pdo->prepare('UPDATE phone_brands SET name = :name WHERE id = :id');
            $stmt->execute([':name' => $name, ':id' => $id]);
            set_flash('success', 'Brand updated.');
        }

        elseif ($action === 'delete_brand') {
            $id = $_POST['brand_id'] ?? '';
            if (!ctype_digit((string)$id)) throw new RuntimeException('Invalid brand.');
            $stmt = $pdo->prepare('DELETE FROM phone_brands WHERE id = :id');
            $stmt->execute([':id' => $id]);
            set_flash('success', 'Brand deleted.');
        }

        elseif ($action === 'add_model') {
            $brandId = $_POST['brand_id'] ?? '';
            $name    = trim($_POST['model_name'] ?? '');
            if (!ctype_digit((string)$brandId) || $name === '') throw new RuntimeException('Please choose a brand and enter a model name.');
            $stmt = $pdo->prepare('INSERT INTO phone_models (brand_id, name) VALUES (:brand, :name)');
            $stmt->execute([':brand' => $brandId, ':name' => $name]);
            set_flash('success', 'Model added.');
        }

        elseif ($action === 'edit_model') {
            $id      = $_POST['model_id'] ?? '';
            $brandId = $_POST['brand_id'] ?? '';
            $name    = trim($_POST['model_name'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if (!ctype_digit((string)$id) || !ctype_digit((string)$brandId) || $name === '') throw new RuntimeException('Invalid model data.');
            $stmt = $pdo->prepare('UPDATE phone_models SET brand_id=:brand, name=:name, is_active=:active WHERE id=:id');
            $stmt->execute([':brand' => $brandId, ':name' => $name, ':active' => $isActive, ':id' => $id]);
            set_flash('success', 'Model updated.');
        }

        elseif ($action === 'delete_model') {
            $id = $_POST['model_id'] ?? '';
            if (!ctype_digit((string)$id)) throw new RuntimeException('Invalid model.');
            $stmt = $pdo->prepare('DELETE FROM phone_models WHERE id = :id');
            $stmt->execute([':id' => $id]);
            set_flash('success', 'Model deleted.');
        }

        redirect('admin/models.php');

    } catch (PDOException $e) {
        // Foreign key violation: this brand/model is used by products or custom designs
        if ($e->getCode() === '23000') {
            set_flash('danger', 'This item cannot be deleted because it is used by existing products or custom designs. Deactivate it instead, or remove those records first.');
        } else {
            set_flash('danger', 'Database error. Please try again.');
        }
        redirect('admin/models.php');
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());
        redirect('admin/models.php');
    }
}

// ---------- Load data ----------
$brands = $pdo->query('SELECT id, name FROM phone_brands ORDER BY name')->fetchAll();
$models = $pdo->query(
    'SELECT m.id, m.name, m.is_active, m.brand_id, b.name AS brand_name
     FROM phone_models m JOIN phone_brands b ON b.id = m.brand_id
     ORDER BY b.name, m.name'
)->fetchAll();

$pageTitle = 'Phone Brands & Models';
$activeAdminPage = 'models';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-4">

  <!-- Brands -->
  <div class="col-lg-5">
    <div class="cc-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Phone Brands</h2>
        <button class="btn btn-sm btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
          <i class="bi bi-plus-lg"></i> Add Brand
        </button>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($brands as $b): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <?= e($b['name']) ?>
            <div>
              <button class="btn btn-sm btn-cc-outline" data-bs-toggle="modal" data-bs-target="#editBrandModal"
                      data-id="<?= (int)$b['id'] ?>" data-name="<?= e($b['name']) ?>"><i class="bi bi-pencil"></i></button>
              <form action="<?= BASE_URL ?>/admin/models.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_brand">
                <input type="hidden" name="brand_id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Delete '<?= e($b['name']) ?>'? Its models will also be deleted.">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Models -->
  <div class="col-lg-7">
    <div class="cc-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Phone Models</h2>
        <button class="btn btn-sm btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addModelModal">
          <i class="bi bi-plus-lg"></i> Add Model
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Model</th><th>Brand</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($models as $m): ?>
              <tr>
                <td><?= e($m['name']) ?></td>
                <td><?= e($m['brand_name']) ?></td>
                <td><span class="badge <?= $m['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $m['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-cc-outline" data-bs-toggle="modal" data-bs-target="#editModelModal"
                          data-id="<?= (int)$m['id'] ?>" data-name="<?= e($m['name']) ?>"
                          data-brand="<?= (int)$m['brand_id'] ?>" data-active="<?= (int)$m['is_active'] ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form action="<?= BASE_URL ?>/admin/models.php" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_model">
                    <input type="hidden" name="model_id" value="<?= (int)$m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Delete '<?= e($m['name']) ?>'?"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/models.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_brand">
      <div class="modal-header"><h5 class="modal-title">Add Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><input type="text" class="form-control" name="brand_name" placeholder="e.g. OnePlus" required></div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Add</button></div>
    </form>
  </div></div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/models.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_brand">
      <input type="hidden" name="brand_id" id="editBrandId">
      <div class="modal-header"><h5 class="modal-title">Edit Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><input type="text" class="form-control" name="brand_name" id="editBrandName" required></div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Save</button></div>
    </form>
  </div></div>
</div>

<!-- Add Model Modal -->
<div class="modal fade" id="addModelModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/models.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_model">
      <div class="modal-header"><h5 class="modal-title">Add Model</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Brand</label>
        <select class="form-select mb-3" name="brand_id" required>
          <?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label">Model Name</label>
        <input type="text" class="form-control" name="model_name" placeholder="e.g. iPhone 16" required>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Add</button></div>
    </form>
  </div></div>
</div>

<!-- Edit Model Modal -->
<div class="modal fade" id="editModelModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/models.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_model">
      <input type="hidden" name="model_id" id="editModelId">
      <div class="modal-header"><h5 class="modal-title">Edit Model</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Brand</label>
        <select class="form-select mb-3" name="brand_id" id="editModelBrand" required>
          <?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label">Model Name</label>
        <input type="text" class="form-control mb-3" name="model_name" id="editModelName" required>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="editModelActive">
          <label class="form-check-label" for="editModelActive">Active</label>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Save</button></div>
    </form>
  </div></div>
</div>

<script>
  document.getElementById('editBrandModal').addEventListener('show.bs.modal', function (e) {
    document.getElementById('editBrandId').value = e.relatedTarget.dataset.id;
    document.getElementById('editBrandName').value = e.relatedTarget.dataset.name;
  });
  document.getElementById('editModelModal').addEventListener('show.bs.modal', function (e) {
    document.getElementById('editModelId').value = e.relatedTarget.dataset.id;
    document.getElementById('editModelName').value = e.relatedTarget.dataset.name;
    document.getElementById('editModelBrand').value = e.relatedTarget.dataset.brand;
    document.getElementById('editModelActive').checked = e.relatedTarget.dataset.active === '1';
  });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>