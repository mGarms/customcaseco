<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ---------- Handle add/edit/delete in one place ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name       = trim($_POST['name'] ?? '');
            $colorHex   = trim($_POST['color_hex'] ?? '');
            $extraPrice = $_POST['extra_price'] ?? '0';

            if ($name === '') throw new RuntimeException('Style name is required.');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorHex)) throw new RuntimeException('Please choose a valid color.');
            if (!is_numeric($extraPrice) || $extraPrice < 0) throw new RuntimeException('Extra price must be a valid positive number.');

            $stmt = $pdo->prepare('INSERT INTO case_designs (name, color_hex, extra_price) VALUES (:name, :color, :price)');
            $stmt->execute([':name' => $name, ':color' => $colorHex, ':price' => $extraPrice]);
            set_flash('success', 'Case style added.');
        }

        elseif ($action === 'edit') {
            $id         = $_POST['id'] ?? '';
            $name       = trim($_POST['name'] ?? '');
            $colorHex   = trim($_POST['color_hex'] ?? '');
            $extraPrice = $_POST['extra_price'] ?? '0';
            $isActive   = isset($_POST['is_active']) ? 1 : 0;

            if (!ctype_digit((string)$id)) throw new RuntimeException('Invalid style.');
            if ($name === '') throw new RuntimeException('Style name is required.');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorHex)) throw new RuntimeException('Please choose a valid color.');
            if (!is_numeric($extraPrice) || $extraPrice < 0) throw new RuntimeException('Extra price must be a valid positive number.');

            $stmt = $pdo->prepare(
                'UPDATE case_designs SET name=:name, color_hex=:color, extra_price=:price, is_active=:active WHERE id=:id'
            );
            $stmt->execute([':name' => $name, ':color' => $colorHex, ':price' => $extraPrice, ':active' => $isActive, ':id' => $id]);
            set_flash('success', 'Case style updated.');
        }

        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (!ctype_digit((string)$id)) throw new RuntimeException('Invalid style.');

            $stmt = $pdo->prepare('DELETE FROM case_designs WHERE id = :id');
            $stmt->execute([':id' => $id]);
            set_flash('success', 'Case style deleted.');
        }

        redirect('admin/case-styles.php');

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_flash('danger', 'This style cannot be deleted because customers have used it in custom designs. Deactivate it instead.');
        } else {
            set_flash('danger', 'Database error. Please try again.');
        }
        redirect('admin/case-styles.php');
    } catch (RuntimeException $e) {
        set_flash('danger', $e->getMessage());
        redirect('admin/case-styles.php');
    }
}

// ---------- Load data ----------
$styles = $pdo->query(
    "SELECT id, name, color_hex, extra_price, is_active,
            (SELECT COUNT(*) FROM custom_designs cd WHERE cd.case_design_id = case_designs.id) AS usage_count
     FROM case_designs
     ORDER BY name"
)->fetchAll();

$pageTitle = 'Case Styles';
$activeAdminPage = 'case-styles';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">These are the case colors/styles customers choose from in the designer.</p>
  <button class="btn btn-admin-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addStyleModal">
    <i class="bi bi-plus-lg"></i> Add Style
  </button>
</div>

<div class="cc-card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th class="ps-3">Preview</th><th>Name</th><th>Extra Price</th><th>Used In</th><th>Status</th><th class="pe-3 text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$styles): ?>
          <tr><td colspan="6" class="text-center py-5">
            <i class="bi bi-palette" style="font-size:2rem; color:var(--cc-purple-pastel);"></i>
            <p class="text-muted mt-2 mb-0">No case styles yet. Add one to get started.</p>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($styles as $s): ?>
          <tr>
            <td class="ps-3">
              <span style="display:inline-block; width:32px; height:32px; border-radius:50%;
                           background:<?= e($s['color_hex']) ?>; border:2px solid var(--cc-border); vertical-align:middle;"></span>
            </td>
            <td><?= e($s['name']) ?> <span class="small text-muted"><?= e($s['color_hex']) ?></span></td>
            <td><?= $s['extra_price'] > 0 ? '+' . format_price($s['extra_price']) : 'No extra charge' ?></td>
            <td class="small text-muted"><?= (int)$s['usage_count'] ?> design<?= $s['usage_count'] == 1 ? '' : 's' ?></td>
            <td><span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="pe-3 text-end">
              <button class="btn btn-sm btn-cc-outline" data-bs-toggle="modal" data-bs-target="#editStyleModal"
                      data-id="<?= (int)$s['id'] ?>" data-name="<?= e($s['name']) ?>"
                      data-color="<?= e($s['color_hex']) ?>" data-price="<?= e($s['extra_price']) ?>"
                      data-active="<?= (int)$s['is_active'] ?>">
                <i class="bi bi-pencil"></i>
              </button>
              <form action="<?= BASE_URL ?>/admin/case-styles.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Delete '<?= e($s['name']) ?>'? This cannot be undone.">
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

<!-- Add Modal -->
<div class="modal fade" id="addStyleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/case-styles.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Add Case Style</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Style Name</label>
          <input type="text" class="form-control" name="name" placeholder="e.g. Sunset Orange" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Color</label>
            <input type="color" class="form-control form-control-color w-100" name="color_hex" value="#c4b5fd" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Extra Price</label>
            <input type="number" step="0.01" min="0" class="form-control" name="extra_price" value="0.00" required>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Add Style</button></div>
    </form>
  </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editStyleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= BASE_URL ?>/admin/case-styles.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editStyleId">
      <div class="modal-header"><h5 class="modal-title">Edit Case Style</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Style Name</label>
          <input type="text" class="form-control" name="name" id="editStyleName" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Color</label>
            <input type="color" class="form-control form-control-color w-100" name="color_hex" id="editStyleColor" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Extra Price</label>
            <input type="number" step="0.01" min="0" class="form-control" name="extra_price" id="editStylePrice" required>
          </div>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="editStyleActive">
          <label class="form-check-label" for="editStyleActive">Active (visible in designer)</label>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-admin-primary">Save Changes</button></div>
    </form>
  </div></div>
</div>

<script>
  document.getElementById('editStyleModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('editStyleId').value = btn.dataset.id;
    document.getElementById('editStyleName').value = btn.dataset.name;
    document.getElementById('editStyleColor').value = btn.dataset.color;
    document.getElementById('editStylePrice').value = btn.dataset.price;
    document.getElementById('editStyleActive').checked = btn.dataset.active === '1';
  });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>