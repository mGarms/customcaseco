<?php
// includes/navbar.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$activePage = $activePage ?? '';
$cartTotal = function_exists('cart_count') ? cart_count() : 0;
?>

<nav class="navbar navbar-expand-lg cc-navbar sticky-top">
  <div class="container">

    <a class="navbar-brand cc-brand" href="<?= BASE_URL ?>/index.php">
      <span class="cc-brand-icon"><i class="bi bi-phone-fill"></i></span>
      <span class="cc-brand-text">
        Custom Case Co.
        <small><?= e(SITE_TAGLINE) ?></small>
      </span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNavbar" aria-controls="mainNavbar"
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">

      <ul class="navbar-nav mx-lg-auto mb-3 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= $activePage === 'home' ? ' active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $activePage === 'shop' ? ' active' : '' ?>" href="<?= BASE_URL ?>/shop.php">Shop</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $activePage === 'customize' ? ' active' : '' ?>" href="<?= BASE_URL ?>/customize.php">Customize</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $activePage === 'contact' ? ' active' : '' ?>" href="<?= BASE_URL ?>/contact.php">Contact Us</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2 flex-wrap">

        <!-- Theme toggle -->
        <button type="button" id="themeToggleBtn" class="btn btn-cc-outline"
                aria-label="Toggle theme" title="Toggle theme">
          <i id="themeToggleIcon" class="bi bi-moon-stars-fill"></i>
        </button>

        <!-- Cart -->
        <a href="<?= BASE_URL ?>/cart.php"
           class="btn btn-cc-outline position-relative<?= $activePage === 'cart' ? ' active' : '' ?>"
           aria-label="Shopping cart">
          <i class="bi bi-bag"></i> <span class="d-lg-none ms-1">Cart</span>
          <span id="cartBadge" class="cc-badge<?= $cartTotal > 0 ? '' : ' d-none' ?>"><?= (int)$cartTotal ?></span>
        </a>

        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
          <div class="dropdown">
            <button class="btn btn-cc-outline dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i>
              <span class="ms-1"><?= e(strtok($_SESSION['full_name'] ?? 'Account', ' ')) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end cc-dropdown">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders.php"><i class="bi bi-box-seam"></i> My Orders</a></li>
              <?php if (function_exists('is_admin') && is_admin()): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Admin Dashboard</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-cc-outline<?= $activePage === 'login' ? ' active' : '' ?>">Login</a>
          <a href="<?= BASE_URL ?>/register.php" class="btn btn-cc-primary">Sign Up</a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>