<?php
// =====================================================
// HOMEPAGE
// =====================================================
$pageTitle  = 'Home';
$activePage = 'home';
require_once 'config/database.php';
require_once 'includes/header.php';

// ---------- Featured products (Shop section) ----------
$featured = $pdo->query(
    "SELECT p.id, p.name, p.price, p.image, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.created_at DESC
     LIMIT 4"
)->fetchAll();

// ---------- Popular phone models ----------
$popularModels = $pdo->query(
    "SELECT b.name AS brand_name, m.name AS model_name, COUNT(p.id) AS product_count
     FROM phone_models m
     JOIN phone_brands b ON b.id = m.brand_id
     LEFT JOIN products p ON p.phone_model_id = m.id AND p.is_active = 1
     WHERE m.is_active = 1
     GROUP BY m.id
     ORDER BY product_count DESC, m.name ASC
     LIMIT 6"
)->fetchAll();
?>

<!-- =====================================================
     1. HERO SECTION (not animated - first thing seen, should be instant)
     ===================================================== -->
<section class="cc-hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 text-center text-lg-start">
        <span class="cc-tag mb-3 d-inline-block">✨ Made for you</span>
        <h1 class="mb-3">Your Phone. <span class="text-pink">Your Style.</span></h1>
        <p class="lead text-muted mb-4">
          Design a one-of-a-kind phone case in minutes. Pick your phone, choose a
          color, add your own photo and text, and we'll bring it to life.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
          <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-cta btn-lg">
            <i class="bi bi-magic"></i> CREATE YOUR CASE
          </a>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline btn-lg">Browse Shop</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="cc-card p-5 text-center mx-auto" style="max-width: 380px;">
          <i class="bi bi-phone" style="font-size: 6rem; color: var(--cc-purple-pastel);"></i>
          <p class="text-muted small mt-3 mb-0">Live preview shown here once you start designing</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
     2. PROMO BANNER
     ===================================================== -->
<section class="py-3 cc-promo-banner">
  <div class="container text-center">
    <p class="mb-0 text-white fw-semibold">
      <i class="bi bi-gift text-pink"></i>
      20% OFF YOUR FIRST CUSTOM PHONE CASE —
      <a href="<?= BASE_URL ?>/customize.php" class="text-pink text-decoration-underline">start designing now</a>
    </p>
  </div>
</section>

<!-- =====================================================
     3. FEATURED PHONE CASES
     ===================================================== -->
<section class="cc-section">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>Featured Phone Cases</h2>
      <p>Our most-loved ready-made designs</p>
    </div>

    <?php if ($featured): ?>
      <div class="row g-4 cc-reveal-group">
        <?php foreach ($featured as $product): ?>
          <div class="col-6 col-md-4 col-lg-3 cc-reveal">
            <div class="cc-card cc-card-hover cc-product-card">
              <div class="cc-product-img">
                <?php if ($product['image']): ?>
                  <img src="<?= BASE_URL ?>/uploads/products/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                  <i class="bi bi-phone"></i>
                <?php endif; ?>
              </div>
              <div class="cc-product-body">
                <span class="cc-tag mb-2 align-self-start"><?= e($product['category_name']) ?></span>
                <h3 class="h6"><?= e($product['name']) ?></h3>
                <div class="mb-3"><span class="cc-price"><?= format_price($product['price']) ?></span></div>
                <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$product['id'] ?>" class="btn btn-cc-primary btn-sm">View Case</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-muted">No featured cases yet. Check back soon!</p>
    <?php endif; ?>

    <div class="text-center mt-4 cc-reveal">
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-cc-outline">View All Cases <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- =====================================================
     4. HOW IT WORKS
     ===================================================== -->
<section class="cc-section bg-lavender">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>How It Works</h2>
      <p>Three simple steps to your perfect case</p>
    </div>

    <div class="row g-4 text-center cc-reveal-group">
      <div class="col-md-4 cc-reveal">
        <div class="cc-card p-4 h-100">
          <div class="cc-brand-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.6rem;">
            <i class="bi bi-cloud-upload"></i>
          </div>
          <h3 class="h5">1. Upload</h3>
          <p class="text-muted mb-0">Pick your phone model and upload a photo, artwork, or logo you love.</p>
        </div>
      </div>
      <div class="col-md-4 cc-reveal">
        <div class="cc-card p-4 h-100">
          <div class="cc-brand-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.6rem;">
            <i class="bi bi-palette"></i>
          </div>
          <h3 class="h5">2. Customize</h3>
          <p class="text-muted mb-0">Choose a case color, add custom text, and arrange everything in the live preview.</p>
        </div>
      </div>
      <div class="col-md-4 cc-reveal">
        <div class="cc-card p-4 h-100">
          <div class="cc-brand-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.6rem;">
            <i class="bi bi-box-seam"></i>
          </div>
          <h3 class="h5">3. Order</h3>
          <p class="text-muted mb-0">Add it to your cart, check out, and track your order until it reaches your door.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================
     5. POPULAR PHONE MODELS
     ===================================================== -->
<section class="cc-section">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>Popular Phone Models</h2>
      <p>We design cases for all the phones you already own</p>
    </div>

    <?php if ($popularModels): ?>
      <div class="row g-3 cc-reveal-group">
        <?php foreach ($popularModels as $model): ?>
          <div class="col-6 col-md-4 col-lg-2 cc-reveal">
            <a href="<?= BASE_URL ?>/shop.php?model=<?= urlencode($model['model_name']) ?>"
               class="cc-card cc-card-hover d-block text-center p-3 text-decoration-none h-100">
              <i class="bi bi-phone" style="font-size: 1.8rem; color: var(--cc-purple);"></i>
              <p class="small fw-semibold mt-2 mb-0 text-dark"><?= e($model['model_name']) ?></p>
              <p class="small text-muted mb-0"><?= e($model['brand_name']) ?></p>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-muted">Phone models coming soon.</p>
    <?php endif; ?>
  </div>
</section>

<!-- =====================================================
     6. CUSTOMER REVIEWS
     Placeholder content: no "reviews" table exists yet.
     ===================================================== -->
<section class="cc-section bg-pink-soft">
  <div class="container">
    <div class="cc-section-title cc-reveal">
      <h2>What Our Customers Say</h2>
      <p>Real feedback from real students</p>
    </div>

    <div class="row g-4 cc-reveal-group">
      <?php
      $reviews = [
        ['name' => 'Maya S.',  'text' => 'The case fits perfectly and the print quality is amazing. Got so many compliments on campus!', 'rating' => 5],
        ['name' => 'Jordan T.', 'text' => 'Super easy to design my own case. The preview looked exactly like what arrived.', 'rating' => 5],
        ['name' => 'Ava R.',   'text' => 'Fast shipping and the pastel colors are even prettier in person.', 'rating' => 4],
      ];
      foreach ($reviews as $review):
      ?>
        <div class="col-md-4 cc-reveal">
          <div class="cc-card p-4 h-100">
            <div class="mb-2 text-pink">
              <?php for ($i = 0; $i < $review['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
              <?php for ($i = $review['rating']; $i < 5; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
            </div>
            <p class="mb-3">"<?= e($review['text']) ?>"</p>
            <p class="fw-semibold mb-0">— <?= e($review['name']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =====================================================
     7. FINAL CTA
     ===================================================== -->
<section class="cc-section text-center final-cta">
  <div class="container cc-reveal">
    <h2 class="text-white mb-3">Ready to Design Your Case?</h2>
    <p class="text-white-50 mb-4">Join hundreds of students who've made their phone truly theirs.</p>
    <a href="<?= BASE_URL ?>/customize.php" class="btn btn-cc-cta btn-lg">
      <i class="bi bi-magic"></i> CREATE YOUR CASE
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
