<?php
// =====================================================
// LOGIN (email or username + password)
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old = ['identifier' => ''];

$redirectTo = $_GET['redirect'] ?? 'index.php';
$redirectTo = ltrim($redirectTo, '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if ($throttleMsg = check_login_throttle()) {
        $errors[] = $throttleMsg;
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $old['identifier'] = $identifier;
    $redirectTo = ltrim($_POST['redirect'] ?? $redirectTo, '/');

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your email/username and password.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.full_name, u.password_hash, u.is_active, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email OR u.username = :username
             LIMIT 1'
        );
        // PDO native prepared statements require unique named placeholders.
        $stmt->execute([
            ':email' => $identifier,
            ':username' => $identifier
        ]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            register_failed_login();
            $errors[] = 'Incorrect email/username or password.';
        } elseif (!$user['is_active']) {
            $errors[] = 'This account has been deactivated. Please contact support.';
        } else {
            session_regenerate_id(true);
            clear_login_throttle();

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role_name'];

            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($redirectTo !== '' ? $redirectTo : 'index.php');
        }
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <h1>Welcome Back</h1>
    <p>Log in to design, order, and track your custom cases</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-xl-5">
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

        <form method="post" action="<?= BASE_URL ?>/login.php" data-once novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">

          <div class="mb-3">
            <label class="form-label" for="identifier">Email or Username</label>
            <input type="text" class="form-control" id="identifier" name="identifier"
                   value="<?= e($old['identifier']) ?>" required autofocus>
          </div>

          <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <button type="button" class="btn btn-cc-outline password-toggle-btn"
                      data-target="password" tabindex="-1" aria-label="Show password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-cc-primary w-100 btn-lg">Log In</button>
        </form>

        <p class="text-center small text-muted mt-4 mb-0">
          Don't have an account? <a href="<?= BASE_URL ?>/register.php">Sign up here</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>