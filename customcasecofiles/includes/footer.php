</main><!-- /.cc-main (opened in header.php) -->

<footer class="cc-footer">
  <div class="container py-5">
    <div class="row g-4">

      <div class="col-lg-5">
        <a class="cc-footer-brand" href="<?= BASE_URL ?>/index.php">
          <i class="bi bi-phone-fill"></i> Custom Case Co.
        </a>
        <p class="mt-2 mb-1 fw-semibold"><?= e(SITE_TAGLINE) ?></p>
        <p class="small mb-3">Personalized phone cases made for college students and young adults who love to stand out.</p>
      </div>

      <div class="col-6 col-lg-2">
        <h6>Shop</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= BASE_URL ?>/shop.php">All Cases</a></li>
          <li><a href="<?= BASE_URL ?>/customize.php">Create Your Case</a></li>
          <li><a href="<?= BASE_URL ?>/cart.php">Cart</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <h6>Account</h6>
        <ul class="list-unstyled small">
          <?php if (is_logged_in()): ?>
            <li><a href="<?= BASE_URL ?>/profile.php">My Profile</a></li>
            <li><a href="<?= BASE_URL ?>/orders.php">Track Orders</a></li>
            <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
            <li><a href="<?= BASE_URL ?>/register.php">Sign Up</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-lg-3">
        <h6>Company</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
          <li><a href="<?= BASE_URL ?>/contact.php"><i class="bi bi-envelope"></i> Contact Us</a></li>
        </ul>
      </div>

    </div>
    <hr>
    <p class="text-center small mb-0">&copy; <?= date('Y') ?> Custom Case Co. All rights reserved.</p>
  </div>
</footer>

<button type="button" id="backToTop" class="cc-back-to-top" aria-label="Back to top">
  <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/theme.js"></script>
<?php foreach (($extraJs ?? []) as $js): ?>
  <script src="<?= BASE_URL . '/' . e(ltrim($js, '/')) ?>"></script>
<?php endforeach; ?>
</body>
</html>