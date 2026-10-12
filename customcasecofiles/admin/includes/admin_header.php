<?php
// =====================================================
// Shared admin page top
// =====================================================

$pageTitle = $pageTitle ?? 'Admin';
$activeAdminPage = $activeAdminPage ?? '';

function admin_nav_active($name, $active) {
    return $name === $active ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= e($pageTitle) ?> | Admin - Custom Case Co.</title>

  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >

  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
  >

  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  >

  <link
    href="<?= BASE_URL ?>/assets/css/style.css"
    rel="stylesheet"
  >

  <link
    href="<?= BASE_URL ?>/assets/css/admin.css"
    rel="stylesheet"
  >

  <!-- Apply saved theme before page renders -->
  <script>
    (function () {
      try {

        var saved = localStorage.getItem('cc-theme');

        var theme = saved;

        if (!theme) {
          theme =
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches
              ? 'dark'
              : 'light';
        }

        document.documentElement.setAttribute(
          'data-theme',
          theme
        );

        document.documentElement.classList.toggle(
          'theme-dark',
          theme === 'dark'
        );

        document.documentElement.classList.toggle(
          'theme-light',
          theme === 'light'
        );

      } catch (e) {}
    })();
  </script>

</head>

<body class="admin-body">

<div class="admin-layout">

  <!-- =====================================================
       SIDEBAR
       ===================================================== -->

  <aside class="admin-sidebar">

    <a
      href="<?= BASE_URL ?>/admin/dashboard.php"
      class="admin-brand"
    >
      <i class="bi bi-phone-fill"></i>
      <span>Custom Case Co.</span>
    </a>

    <p class="admin-brand-sub">
      Admin Panel
    </p>

    <nav class="admin-nav">

      <a
        href="<?= BASE_URL ?>/admin/dashboard.php"
        class="admin-nav-link<?= admin_nav_active('dashboard', $activeAdminPage) ?>"
      >
        <i class="bi bi-speedometer2"></i>
        Dashboard
      </a>

      <a
        href="<?= BASE_URL ?>/admin/products.php"
        class="admin-nav-link<?= admin_nav_active('products', $activeAdminPage) ?>"
      >
        <i class="bi bi-box-seam"></i>
        Products
      </a>

      <a
        href="<?= BASE_URL ?>/admin/models.php"
        class="admin-nav-link<?= admin_nav_active('models', $activeAdminPage) ?>"
      >
        <i class="bi bi-phone"></i>
        Phone Models
      </a>

      <a
        href="<?= BASE_URL ?>/admin/case-styles.php"
        class="admin-nav-link<?= admin_nav_active('case-styles', $activeAdminPage) ?>"
      >
        <i class="bi bi-palette"></i>
        Case Styles
      </a>

      <a
        href="<?= BASE_URL ?>/admin/custom-designs.php"
        class="admin-nav-link<?= admin_nav_active('custom-designs', $activeAdminPage) ?>"
      >
        <i class="bi bi-magic"></i>
        Custom Designs
      </a>

      <a
        href="<?= BASE_URL ?>/admin/orders.php"
        class="admin-nav-link<?= admin_nav_active('orders', $activeAdminPage) ?>"
      >
        <i class="bi bi-receipt"></i>
        Orders
      </a>

      <a
        href="<?= BASE_URL ?>/admin/users.php"
        class="admin-nav-link<?= admin_nav_active('users', $activeAdminPage) ?>"
      >
        <i class="bi bi-people"></i>
        Users
      </a>

    </nav>

    <div class="admin-sidebar-footer">

      <a
        href="<?= BASE_URL ?>/index.php"
        class="admin-nav-link"
      >
        <i class="bi bi-box-arrow-left"></i>
        View Site
      </a>

      <a
        href="<?= BASE_URL ?>/admin/logout.php"
        class="admin-nav-link"
      >
        <i class="bi bi-box-arrow-right"></i>
        Logout
      </a>

    </div>

  </aside>


  <!-- =====================================================
       MAIN CONTENT
       ===================================================== -->

  <div class="admin-content">

    <header class="admin-topbar">

      <button
        class="admin-menu-toggle d-lg-none"
        id="adminMenuToggle"
        aria-label="Toggle menu"
      >
        <i class="bi bi-list"></i>
      </button>

      <h1 class="admin-page-title">
        <?= e($pageTitle) ?>
      </h1>

      <div class="admin-topbar-actions">

        <!-- THEME TOGGLE -->
        <button
          type="button"
          id="adminThemeToggle"
          class="admin-theme-toggle"
          aria-label="Switch to dark mode"
          title="Switch to dark mode"
        >
          <i
            class="bi bi-moon-stars-fill"
            id="adminThemeIcon"
          ></i>
        </button>

        <!-- USER -->
        <div class="admin-topbar-user">
          <i class="bi bi-person-circle"></i>

          <?= e($_SESSION['full_name'] ?? 'Admin') ?>
        </div>

      </div>

    </header>

    <main class="admin-main">

      <?php show_flash(); ?>