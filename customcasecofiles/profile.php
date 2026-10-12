<?php
// =====================================================
// PROFILE PAGE - view only
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$stmt = $pdo->prepare(
    'SELECT full_name, username, email, phone, address, profile_picture, created_at
     FROM users WHERE id = :id'
);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Extremely rare edge case: account was deleted from another tab/admin panel
if (!$user) {
    redirect('logout.php');
}

$pageTitle = 'My Profile';
require_once 'includes/header.php';

$avatarUrl = $user['profile_picture']
    ? BASE_URL . '/uploads/profile/' . e($user['profile_picture'])
    : null;
?>

<div class="cc-page-header">
  <div class="container">
    <h1>My Profile</h1>
    <p>Your account details</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="cc-card p-4 p-md-5">

        <div class="d-flex flex-column flex-sm-row align-items-center gap-4 mb-4 pb-4 border-bottom">
          <?php if ($avatarUrl): ?>
            <img src="<?= $avatarUrl ?>" alt="Profile picture"
                 class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-lavender"
                 style="width: 100px; height: 100px;">
              <i class="bi bi-person" style="font-size: 3rem; color: var(--cc-purple);"></i>
            </div>
          <?php endif; ?>

          <div class="text-center text-sm-start">
            <h2 class="h4 mb-1"><?= e($user['full_name']) ?></h2>
            <p class="text-muted mb-0">@<?= e($user['username']) ?></p>
            <p class="small text-muted mb-0">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <span class="text-muted small d-block">Email</span>
            <span class="fw-semibold"><?= e($user['email']) ?></span>
          </div>
          <div class="col-md-6">
            <span class="text-muted small d-block">Phone</span>
            <span class="fw-semibold"><?= e($user['phone'] ?: 'Not provided') ?></span>
          </div>
          <div class="col-12">
            <span class="text-muted small d-block">Address</span>
            <span class="fw-semibold"><?= nl2br(e($user['address'] ?: 'Not provided')) ?></span>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <a href="<?= BASE_URL ?>/editprofile.php" class="btn btn-cc-primary">
            <i class="bi bi-pencil"></i> Edit Profile
          </a>
          <a href="<?= BASE_URL ?>/orders.php" class="btn btn-cc-outline">
            <i class="bi bi-box-seam"></i> My Orders
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>