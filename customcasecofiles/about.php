<?php
// =====================================================
// ABOUT PAGE
// =====================================================
$pageTitle  = 'About Us';
$activePage = 'about';
require_once 'config/database.php';
require_once 'includes/header.php';
?>

<div class="cc-page-header">
  <div class="container">
    <h1>About Custom Case Co.</h1>
    <p>Your Phone. Your Style.</p>
  </div>
</div>

<!-- =====================================================
     OUR STORY
     ===================================================== -->
<section class="cc-section pt-0">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 cc-reveal">
        <span class="cc-tag mb-3 d-inline-block">Our Story</span>
        <h2 class="mb-3">Made for Students Who Love to Stand Out</h2>
        <p class="text-muted">
          Custom Case Co. started with a simple idea: your phone case shouldn't look
          like everyone else's. We're built for college students and young adults who
          want an accessory that's actually theirs — their photo, their colors, their text.
        </p>
        <p class="text-muted mb-0">
          Whether you want a ready-made design off the shelf or something you built
          yourself in our case designer, we make it easy, affordable, and quick to order.
        </p>
      </div>
      <div class="col-lg-6 cc-reveal">
        <div class="cc-card p-5 text-center mx-auto" style="max-width: 380px;">
          <i class="bi bi-phone" style="font-size: 5rem; color: var(--cc-purple-pastel);"></i>
          <p class="text-muted small mt-3 mb-0">Every case starts with your idea</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
     WHAT WE OFFER
     ===================================================== -->
<section class="cc-section bg-lavender">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>What We Offer</h2>
      <p>Two ways to get the case you want</p>
    </div>

    <div class="row g-4 cc-reveal-group">
      <div class="col-md-6">
        <div class="cc-card p-4 h-100 cc-reveal">
          <div class="cc-brand-icon mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
            <i class="bi bi-bag"></i>
          </div>
          <h3 class="h5">Ready-Made Cases</h3>
          <p class="text-muted mb-3">
            Browse our collection of pre-designed cases in solid colors, patterns,
            and protective styles — all in stock and ready to ship.
          </p>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline btn-sm">Browse Shop</a>
        </div>
      </div>
      <div class="col-md-6">
        <div class="cc-card p-4 h-100 cc-reveal">
          <div class="cc-brand-icon mb-3" style="width: 56px; height: 56px; font-size: 1.5rem;">
            <i class="bi bi-magic"></i>
          </div>
          <h3 class="h5">Design Your Own</h3>
          <p class="text-muted mb-3">
            Pick your phone, choose a case color, upload a photo, and add custom text.
            Preview it live before you order.
          </p>
          <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-outline btn-sm">Start Designing</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
     WHY CHOOSE US
     ===================================================== -->
<section class="cc-section">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>Why Choose Us</h2>
      <p>Built with students in mind</p>
    </div>

    <div class="row g-4 text-center cc-reveal-group">
      <div class="col-6 col-md-3 cc-reveal">
        <i class="bi bi-cash-coin" style="font-size: 2rem; color: var(--cc-purple);"></i>
        <h3 class="h6 mt-3 mb-1">Affordable</h3>
        <p class="small text-muted mb-0">Quality cases that fit a student budget.</p>
      </div>
      <div class="col-6 col-md-3 cc-reveal">
        <i class="bi bi-palette" style="font-size: 2rem; color: var(--cc-purple);"></i>
        <h3 class="h6 mt-3 mb-1">Fully Personal</h3>
        <p class="small text-muted mb-0">Your photo, your text, your colors.</p>
      </div>
      <div class="col-6 col-md-3 cc-reveal">
        <i class="bi bi-phone" style="font-size: 2rem; color: var(--cc-purple);"></i>
        <h3 class="h6 mt-3 mb-1">Every Phone</h3>
        <p class="small text-muted mb-0">Support for all major brands and models.</p>
      </div>
      <div class="col-6 col-md-3 cc-reveal">
        <i class="bi bi-box-seam" style="font-size: 2rem; color: var(--cc-purple);"></i>
        <h3 class="h6 mt-3 mb-1">Easy Ordering</h3>
        <p class="small text-muted mb-0">Design, order, and track it all in one place.</p>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
     FINAL CTA
     ===================================================== -->
<section class="cc-section text-center" style="background: linear-gradient(135deg, var(--cc-purple-dark), var(--cc-purple));">
  <div class="container cc-reveal">
    <h2 class="text-white mb-3">Ready to Make It Yours?</h2>
    <p class="text-white-50 mb-4">Design a case that's one of a kind — just like you.</p>
    <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-cta btn-lg">
      <i class="bi bi-magic"></i> CREATE YOUR CASE
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
