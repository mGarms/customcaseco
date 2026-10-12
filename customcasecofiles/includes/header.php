<?php
// Shared page top: <head>, promo bar, navbar, and the opening <main> tag.
// Set variables BEFORE including this file (see notes above).

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle       = $pageTitle       ?? '';
$pageDescription = $pageDescription ?? SITE_NAME . ' - ' . SITE_TAGLINE . ' Design personalized phone cases in minutes.';
$activePage      = $activePage      ?? '';
$showPromo       = $showPromo       ?? true;
$extraCss        = $extraCss        ?? [];

$fullTitle = $pageTitle !== ''
    ? $pageTitle . ' | ' . SITE_NAME
    : SITE_NAME . ' - ' . SITE_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="theme-color" content="#3b1f6b">
  <title><?= e($fullTitle) ?></title>

  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📱</text></svg>">

  <!-- Bootstrap 5, icons, font -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Site design system -->
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <?php foreach ($extraCss as $css): ?>
    <link href="<?= BASE_URL . '/' . e(ltrim($css, '/')) ?>" rel="stylesheet">
  <?php endforeach; ?>
</head>
<body>

<?php if ($showPromo): ?>
  <div class="cc-promo-bar">
    <i class="bi bi-stars"></i>
    <a href="<?= BASE_URL ?>/customize.php">20% OFF YOUR FIRST CUSTOM PHONE CASE</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/navbar.php'; ?>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="container mt-3">
    <?php show_flash(); ?>
  </div>
<?php endif; ?>
  <script>
    (function () {
      try {
        var saved = localStorage.getItem('cc-theme');
        var theme = saved ? saved : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
      } catch (e) {}
    })();
  </script>
<main class="cc-main">
    
