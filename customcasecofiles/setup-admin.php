<?php
// =====================================================
// ONE-TIME ADMIN SETUP SCRIPT
// Creates a single admin account with a securely hashed password.
// DELETE THIS FILE after you've created your admin account.
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

// ---------- Change these two values before running ----------
$adminEmail    = 'admin@customcaseco.com';
$adminPassword = 'Admin12345!';   // change this to something only you know
// --------------------------------------------------------------

$adminUsername = 'admin';
$adminFullName = 'Store Administrator';

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please reload and try again.';
    }

    if (empty($errors)) {
        // Refuse to run twice -- stops someone re-running this and resetting
        // the password if the file is accidentally left on the server.
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute([':email' => $adminEmail, ':username' => $adminUsername]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email or username already exists. '
                      . 'If you need to promote an existing account instead, run this SQL in phpMyAdmin: '
                      . "UPDATE users SET role_id = 2 WHERE email = 'your-email@example.com';";
        } else {
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (role_id, full_name, username, email, password_hash, phone, address)
                 VALUES (2, :full_name, :username, :email, :password_hash, :phone, :address)'
            );
            $stmt->execute([
                ':full_name'     => $adminFullName,
                ':username'      => $adminUsername,
                ':email'         => $adminEmail,
                ':password_hash' => $hash,
                ':phone'         => 'N/A',
                ':address'       => 'N/A',
            ]);

            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Setup | Custom Case Co.</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 560px;">

  <?php if ($done): ?>
    <div class="cc-card p-4 p-md-5 text-center">
      <h1 class="h4 text-success mb-3">✅ Admin Account Created</h1>
      <p class="mb-1">You can now log in at <code>admin/login.php</code> with:</p>
      <div class="cc-card bg-lavender p-3 my-3 text-start">
        <p class="mb-1"><strong>Email:</strong> <?= e($adminEmail) ?></p>
        <p class="mb-1"><strong>Username:</strong> <?= e($adminUsername) ?></p>
        <p class="mb-0"><strong>Password:</strong> the one you set in this file</p>
      </div>
      <div class="alert alert-warning text-start">
        <strong>Important:</strong> delete <code>setup-admin.php</code> from your project now.
        Leaving it on the server would let anyone who finds the URL see this page.
      </div>
      <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-cc-primary">Go to Admin Login</a>
    </div>

  <?php else: ?>
    <div class="cc-card p-4 p-md-5">
      <h1 class="h4 mb-3">Create Admin Account</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <p class="text-muted">This will create one admin account using the credentials set at the top of <code>setup-admin.php</code>:</p>
      <div class="cc-card bg-lavender p-3 mb-4">
        <p class="mb-1"><strong>Email:</strong> <?= e($adminEmail) ?></p>
        <p class="mb-1"><strong>Username:</strong> <?= e($adminUsername) ?></p>
        <p class="mb-0"><strong>Password:</strong> (hidden — whatever you set in the file)</p>
      </div>

      <p class="small text-muted">
        Want different credentials? Edit the <code>$adminEmail</code>, <code>$adminPassword</code>,
        and <code>$adminUsername</code> variables near the top of this file before clicking confirm.
      </p>

      <form method="post" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="confirm" value="1">
        <button type="submit" class="btn btn-cc-primary w-100 btn-lg">Create Admin Account</button>
      </form>
    </div>
  <?php endif; ?>

</div>
</body>
</html>