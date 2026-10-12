<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ---------- Handle activate/deactivate ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $targetId = $_POST['user_id'] ?? '';

    if (ctype_digit((string)$targetId)) {
        if ((int)$targetId === (int)$_SESSION['user_id']) {
            // A logged-in admin can never deactivate their own account
            set_flash('danger', 'You cannot deactivate your own account.');
        } else {
            $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = :id');
            $stmt->execute([':id' => $targetId]);
            $current = $stmt->fetchColumn();

            if ($current !== false) {
                $stmt = $pdo->prepare('UPDATE users SET is_active = :active WHERE id = :id');
                $stmt->execute([':active' => $current ? 0 : 1, ':id' => $targetId]);
                set_flash('success', 'User ' . ($current ? 'deactivated' : 'activated') . '.');
            }
        }
    }
    redirect('admin/users.php');
}

// ---------- Search + list ----------
$search = trim($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.is_active, u.created_at, r.name AS role_name
     FROM users u JOIN roles r ON r.id = u.role_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
$activeAdminPage = 'users';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="get" class="d-flex gap-2 mb-3">
  <input type="text" name="q" class="form-control" placeholder="Search by name, username, or email..." value="<?= e($search) ?>">
  <button class="btn btn-cc-outline"><i class="bi bi-search"></i></button>
  <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-cc-outline">Clear</a>
</form>

<div class="cc-card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th class="ps-3">Name</th><th>Contact</th><th>Role</th><th>Joined</th><th>Status</th><th class="pe-3 text-end">Action</th></tr></thead>
      <tbody>
        <?php if (!$users): ?><tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="ps-3"><?= e($u['full_name']) ?><br><span class="small text-muted">@<?= e($u['username']) ?></span></td>
            <td class="small"><?= e($u['email']) ?><br><span class="text-muted"><?= e($u['phone'] ?: '—') ?></span></td>
            <td><span class="badge <?= $u['role_name'] === 'admin' ? 'bg-purple' : 'bg-secondary' ?>" style="<?= $u['role_name'] === 'admin' ? 'background:var(--cc-purple);' : '' ?>"><?= ucfirst($u['role_name']) ?></span></td>
            <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td><span class="badge <?= $u['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="pe-3 text-end">
              <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                <span class="small text-muted">This is you</span>
              <?php else: ?>
                <form action="<?= BASE_URL ?>/admin/users.php" method="post"
                      data-confirm="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> <?= e($u['full_name']) ?>?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                    <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>