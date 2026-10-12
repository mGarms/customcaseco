<?php
// =====================================================
// EDIT PROFILE - update info, optional picture upload
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();
$userId = $_SESSION['user_id'];

// ---------- Load current data (also used to repopulate the form) ----------
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$errors = [];
$old = [
    'full_name' => $user['full_name'],
    'username'  => $user['username'],
    'email'     => $user['email'],
    'phone'     => $user['phone'],
    'address'   => $user['address'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $removePicture = isset($_POST['remove_picture']);

    $old = compact('fullName', 'username', 'email', 'phone', 'address');
    $old = ['full_name' => $fullName, 'username' => $username, 'email' => $email, 'phone' => $phone, 'address' => $address];

    // ---------- Required fields ----------
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if ($phone === '')    $errors[] = 'Phone number is required.';
    if ($address === '')  $errors[] = 'Address is required.';

    // ---------- Format checks ----------
    if ($username !== '' && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters, using only letters, numbers, and underscores.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // ---------- Duplicate check, excluding the current user ----------
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id LIMIT 1'
        );
        $stmt->execute([':email' => $email, ':username' => $username, ':id' => $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'That email or username is already taken by another account.';
        }
    }

    // ---------- Profile picture upload (optional) ----------
    $newPictureFilename = null; // null = no change
    $uploadDir = __DIR__ . '/uploads/profile/';
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (empty($errors) && !empty($_FILES['profile_picture']['name'])) {
        $file = $_FILES['profile_picture'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading your picture. Please try again.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Profile picture must be smaller than 2MB.';
        } else {
            // Check the REAL file content, not just the extension the browser reports
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowedTypes[$mime])) {
                $errors[] = 'Profile picture must be a JPG, PNG, or WEBP image.';
            } else {
                $ext = $allowedTypes[$mime];
                $newPictureFilename = 'user_' . $userId . '_' . time() . '.' . $ext;
            }
        }
    }

    // ---------- Save changes ----------
    if (empty($errors)) {
        // Work out the final picture value: keep, replace, or remove
        $finalPicture = $user['profile_picture']; // default: unchanged
        if ($newPictureFilename) {
            $finalPicture = $newPictureFilename;
        } elseif ($removePicture) {
            $finalPicture = null;
        }

        $stmt = $pdo->prepare(
            'UPDATE users
             SET full_name = :full_name, username = :username, email = :email,
                 phone = :phone, address = :address, profile_picture = :profile_picture
             WHERE id = :id'
        );
        $stmt->execute([
            ':full_name'       => $fullName,
            ':username'        => $username,
            ':email'           => $email,
            ':phone'           => $phone,
            ':address'         => $address,
            ':profile_picture' => $finalPicture,
            ':id'              => $userId,
        ]);

        // Move the uploaded file only after the DB update succeeds
        if ($newPictureFilename) {
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $newPictureFilename);
            // Clean up the old picture file, if there was one
            if ($user['profile_picture'] && file_exists($uploadDir . $user['profile_picture'])) {
                unlink($uploadDir . $user['profile_picture']);
            }
        } elseif ($removePicture && $user['profile_picture'] && file_exists($uploadDir . $user['profile_picture'])) {
            unlink($uploadDir . $user['profile_picture']);
        }

        // Keep the session name in sync with the navbar
        $_SESSION['full_name'] = $fullName;

        set_flash('success', 'Your profile has been updated.');
        redirect('profile.php');
    }
}

$pageTitle = 'Edit Profile';
require_once 'includes/header.php';

$avatarUrl = $user['profile_picture']
    ? BASE_URL . '/uploads/profile/' . e($user['profile_picture'])
    : null;
?>

<div class="cc-page-header">
  <div class="container">
    <h1>Edit Profile</h1>
    <p>Update your account information</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row justify-content-center">
    <div class="col-lg-7">
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

        <form method="post" action="<?= BASE_URL ?>/editprofile.php"
              enctype="multipart/form-data" id="editProfileForm" data-once novalidate>
          <?= csrf_field() ?>

          <!-- Profile picture -->
          <div class="text-center mb-4">
            <img id="avatarPreview"
                 src="<?= $avatarUrl ?: 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23c4b5fd%22/></svg>' ?>"
                 alt="Profile picture preview"
                 class="rounded-circle mb-2" style="width: 110px; height: 110px; object-fit: cover;">
            <div>
              <label for="profile_picture" class="btn btn-cc-outline btn-sm">
                <i class="bi bi-camera"></i> Change Picture
              </label>
              <input type="file" id="profile_picture" name="profile_picture"
                     accept="image/jpeg,image/png,image/webp" class="d-none">
            </div>
            <?php if ($avatarUrl): ?>
              <div class="form-check d-inline-flex align-items-center gap-1 mt-2">
                <input class="form-check-input" type="checkbox" id="remove_picture" name="remove_picture">
                <label class="form-check-label small" for="remove_picture">Remove current picture</label>
              </div>
            <?php endif; ?>
            <div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
          </div>

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

          <div class="mb-3">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone"
                   value="<?= e($old['phone']) ?>" required>
          </div>

          <div class="mb-4">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="2" required><?= e($old['address']) ?></textarea>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-cc-primary btn-lg">
              <i class="bi bi-check-lg"></i> Save Changes
            </button>
            <a href="<?= BASE_URL ?>/profile.php" class="btn btn-cc-outline btn-lg">Cancel</a>
          </div>
        </form>

        <p class="small text-muted mt-4 mb-0">
          Want to change your password? That's handled separately for security —
          a dedicated change-password option is coming in a later update.
        </p>

      </div>
    </div>
  </div>
</div>

<script>
  // Live preview of a newly chosen picture, before upload
  document.getElementById('profile_picture').addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;

    // Basic client-side check (server re-validates regardless)
    var allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      alert('Please choose a JPG, PNG, or WEBP image.');
      e.target.value = '';
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      alert('Image must be smaller than 2MB.');
      e.target.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function (ev) {
      document.getElementById('avatarPreview').src = ev.target.result;
    };
    reader.readAsDataURL(file);

    // If a new file is chosen, uncheck "remove picture" so they don't conflict
    var removeBox = document.getElementById('remove_picture');
    if (removeBox) removeBox.checked = false;
  });

  // Simple client-side validation before submit (server validates again regardless)
  document.getElementById('editProfileForm').addEventListener('submit', function (e) {
    var username = document.getElementById('username').value.trim();
    var email = document.getElementById('email').value.trim();
    var errors = [];

    if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
      errors.push('Username must be 3-30 characters (letters, numbers, underscores only).');
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.push('Please enter a valid email address.');
    }

    if (errors.length) {
      e.preventDefault();
      alert(errors.join('\n'));
      // Re-enable the submit button since data-once disabled it
      var btn = this.querySelector('[type="submit"]');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes'; }
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>