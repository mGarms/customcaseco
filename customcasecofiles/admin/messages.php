<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ---------- Mark read / delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (ctype_digit((string)$id)) {
        if ($action === 'mark_read') {
            $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            set_flash('success', 'Message deleted.');
        }
    }
    redirect('admin/messages.php');
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages';
$activeAdminPage = 'messages';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="cc-card p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th class="ps-3">From</th><th>Subject</th><th>Date</th><th>Status</th><th class="pe-3 text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$messages): ?>
          <tr><td colspan="5" class="text-center py-5">
            <i class="bi bi-envelope" style="font-size:2rem; color:var(--cc-purple-pastel);"></i>
            <p class="text-muted mt-2 mb-0">No messages yet.</p>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
          <tr class="<?= $m['is_read'] ? '' : 'fw-semibold' ?>">
            <td class="ps-3"><?= e($m['name']) ?><br><span class="small text-muted fw-normal"><?= e($m['email']) ?></span></td>
            <td><?= e($m['subject']) ?></td>
            <td class="small text-muted fw-normal"><?= date('M j, Y g:i A', strtotime($m['created_at'])) ?></td>
            <td><span class="badge <?= $m['is_read'] ? 'bg-secondary' : 'bg-success' ?>"><?= $m['is_read'] ? 'Read' : 'New' ?></span></td>
            <td class="pe-3 text-end">
              <button class="btn btn-sm btn-cc-outline" data-bs-toggle="modal" data-bs-target="#viewMsgModal"
                      data-name="<?= e($m['name']) ?>" data-email="<?= e($m['email']) ?>"
                      data-subject="<?= e($m['subject']) ?>" data-message="<?= e($m['message']) ?>"
                      data-id="<?= (int)$m['id'] ?>" data-read="<?= (int)$m['is_read'] ?>">
                <i class="bi bi-eye"></i>
              </button>
              <form action="<?= BASE_URL ?>/admin/messages.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this message?">
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

<!-- View Message Modal -->
<div class="modal fade" id="viewMsgModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="viewMsgSubject"></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="small text-muted mb-3">From: <strong id="viewMsgName"></strong> (<span id="viewMsgEmail"></span>)</p>
      <p id="viewMsgBody" style="white-space: pre-wrap;"></p>
    </div>
    <div class="modal-footer">
      <a id="viewMsgReply" href="#" class="btn btn-cc-primary"><i class="bi bi-reply"></i> Reply by Email</a>
      <form action="<?= BASE_URL ?>/admin/messages.php" method="post" id="markReadForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="mark_read">
        <input type="hidden" name="id" id="markReadId">
      </form>
    </div>
  </div></div>
</div>

<script>
  document.getElementById('viewMsgModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('viewMsgSubject').textContent = btn.dataset.subject;
    document.getElementById('viewMsgName').textContent = btn.dataset.name;
    document.getElementById('viewMsgEmail').textContent = btn.dataset.email;
    document.getElementById('viewMsgBody').textContent = btn.dataset.message;
    document.getElementById('viewMsgReply').href = 'mailto:' + btn.dataset.email + '?subject=Re: ' + encodeURIComponent(btn.dataset.subject);
    document.getElementById('markReadId').value = btn.dataset.id;

    if (btn.dataset.read === '0') {
      document.getElementById('markReadForm').submit();
    }
  });
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>