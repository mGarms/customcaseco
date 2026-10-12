<?php
// =====================================================
// CONTACT PAGE
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

// If the visitor is logged in, prefill their name/email for convenience
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $me = $stmt->fetch();
    if ($me) {
        $old['name']  = $me['full_name'];
        $old['email'] = $me['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $old = ['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message];

    if ($name === '')    $errors[] = 'Please enter your name.';
    if ($email === '')   $errors[] = 'Please enter your email.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($subject === '') $errors[] = 'Please enter a subject.';
    if ($message === '') $errors[] = 'Please enter a message.';
    if (mb_strlen($message) > 2000) $errors[] = 'Message must be 2000 characters or fewer.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)'
        );
        $stmt->execute([':name' => $name, ':email' => $email, ':subject' => $subject, ':message' => $message]);

        set_flash('success', 'Thanks for reaching out! We\'ll get back to you soon.');
        redirect('contact.php');
    }
}

$pageTitle  = 'Contact Us';
$activePage = 'contact';
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <h1><i class="bi bi-envelope-heart"></i> Get in Touch</h1>
    <p>Questions about your order, a design idea, or just want to say hi?</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <div class="row g-4">

    <!-- Contact info -->
    <div class="col-lg-4">
      <div class="cc-card p-4 mb-3">
        <div class="cc-brand-icon mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
          <i class="bi bi-envelope"></i>
        </div>
        <h3 class="h6 mb-1">Email Us</h3>
        <p class="text-muted small mb-0">hello@customcaseco.example</p>
      </div>

      <div class="cc-card p-4 mb-3">
        <div class="cc-brand-icon mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
          <i class="bi bi-telephone"></i>
        </div>
        <h3 class="h6 mb-1">Call Us</h3>
        <p class="text-muted small mb-0">+63 900 000 0000</p>
      </div>

      <div class="cc-card p-4">
        <div class="cc-brand-icon mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
          <i class="bi bi-clock"></i>
        </div>
        <h3 class="h6 mb-1">Response Time</h3>
        <p class="text-muted small mb-0">We usually reply within 1-2 business days.</p>
      </div>
    </div>

    <!-- Contact form -->
    <div class="col-lg-8">
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

        <form method="post" action="<?= BASE_URL ?>/contact.php" data-once novalidate>
          <?= csrf_field() ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="name">Your Name</label>
              <input type="text" class="form-control" id="name" name="name"
                     value="<?= e($old['name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="email">Your Email</label>
              <input type="email" class="form-control" id="email" name="email"
                     value="<?= e($old['email']) ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="subject">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject"
                   value="<?= e($old['subject']) ?>" placeholder="e.g. Question about my order" required>
          </div>

          <div class="mb-4">
            <label class="form-label" for="message">Message</label>
            <textarea class="form-control" id="message" name="message" rows="5" maxlength="2000" required><?= e($old['message']) ?></textarea>
            <div class="form-text">Up to 2000 characters.</div>
          </div>

          <button type="submit" class="btn btn-cc-primary btn-lg">
            <i class="bi bi-send"></i> Send Message
          </button>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>