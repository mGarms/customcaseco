<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) redirect('admin/orders.php');

$orderId = $_POST['order_id'] ?? '';
$status  = $_POST['status'] ?? '';
$note    = trim($_POST['note'] ?? '');
$validStatuses = ['pending','processing','shipped','delivered','cancelled'];

if (!ctype_digit((string)$orderId) || !in_array($status, $validStatuses, true)) {
    set_flash('danger', 'Invalid status update.');
    redirect('admin/orders.php');
}

$stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
$stmt->execute([':status' => $status, ':id' => $orderId]);

$stmt = $pdo->prepare(
    'INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (:id, :status, :note, :admin)'
);
$stmt->execute([
    ':id' => $orderId, ':status' => $status,
    ':note' => $note ?: null, ':admin' => $_SESSION['user_id'],
]);

set_flash('success', 'Order status updated to "' . ucfirst($status) . '".');
redirect('admin/order-details.php?id=' . (int)$orderId);