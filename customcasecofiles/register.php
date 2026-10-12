<?php
// =====================================================
// USER REGISTRATION
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

// Already logged in? No need to register again.
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    // ---------- Collect + trim input ----------
    $fullName        = trim($_POST['full_name'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone           = trim($_POST['phone'] ?? '');
    $address         = trim($_POST['address'] ?? '');

    $old = compact('fullName', 'username', 'email', 'phone', 'address'); // repopulate form on error
    $old = ['full_name' => $fullName, 'username' => $username, 'email' => $email, 'phone' => $phone, 'address' => $address];

    // ---------- Required fields ----------
    if ($fullName === '')  $errors[] = 'Full name is required.';
    if ($username === '')  $errors[] = 'Username is required.';
    if ($email === '')     $errors[] = 'Email is required.';
    if ($password === '')  $errors[] = 'Password is required.';
    if ($phone === '')     $errors[] = 'Phone number is required.';
    if ($address === '')   $errors[] = 'Address is required.';

    // ---------- Format checks ----------
    if ($username !== '' && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters, using only letters, numbers, and underscores.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // ---------- Duplicate checks (only if format checks passed) ----------
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute([':email' => $email, ':username' => $username]);
        if ($stmt->fetch()) {
            $errors[] = 'That email or username is already registered.';
        }
    }

    // ---------- Create the account ----------
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, full_name, username, email, password_hash, phone, address)
             VALUES (1, :full_name, :username, :email, :password_hash, :phone, :address)'
        );
        $stmt->execute([
            ':full_name'      => $fullName,
            ':username'       => $username,
            ':email'          => $email,
            ':password_hash'  => $hash,
            ':phone'          => $phone,
            ':address'        => $address,
        ]);

        $newUserId = $pdo->lastInsertId();

        session_regenerate_id(true);   // <-- add this line

        $_SESSION['user_id']   = $newUserId;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role']      = 'customer';

        set_flash('success', 'Welcome to Custom Case Co., ' . $fullName . '! Your account has been created.');
        redirect('index.php');
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <h1>Create Your Account</h1>
    <p>Join Custom Case Co. and start designing</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
      <div class="cc-card p-4 p-md-5">

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/register.php" data-once novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" class="form-control" id="full_name" name="full_name"
                   value="<?= e($old['full_name']) ?>" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="username">Username</label>
              <input type="text" class="form-control" id="username" name="username"
                     value="<?= e($old['username']) ?>" required>
              <div class="form-text">3-30 characters: letters, numbers, underscores.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="email">Email</label>
              <input type="email" class="form-control" id="email" name="email"
                     value="<?= e($old['email']) ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="password">Password</label>
              <input type="password" class="form-control" id="password" name="password" minlength="8" required>
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="confirm_password">Confirm Password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone"
                   value="<?= e($old['phone']) ?>" required>
          </div>

          <div class="mb-4">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="2" required><?= e($old['address']) ?></textarea>
          </div>

          <button type="submit" class="btn btn-cc-primary w-100 btn-lg">Create Account</button>
        </form>

        <p class="text-center small text-muted mt-4 mb-0">
          Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in here</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>