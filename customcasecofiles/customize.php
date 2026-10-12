<?php
// =====================================================
// CASE DESIGNER
// =====================================================
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login(); // saving a design needs a user_id

// Safety net: define the constant here if it wasn't already defined in
// config/config.php, so this page can never fatal-crash over a missing
// constant. The real fix is still adding it to config.php (see note below).
if (!defined('BASE_CUSTOM_PRICE')) {
    define('BASE_CUSTOM_PRICE', 16.99);
}

$pageTitle  = 'Customize Your Case';
$activePage = 'customize';
require_once 'includes/header.php';

// ---------- Data for the form ----------
$brands = $pdo->query('SELECT id, name FROM phone_brands WHERE is_active = 1 ORDER BY name')->fetchAll();
$models = $pdo->query('SELECT id, brand_id, name FROM phone_models WHERE is_active = 1 ORDER BY name')->fetchAll();
$designs = $pdo->query('SELECT id, name, color_hex, extra_price FROM case_designs WHERE is_active = 1 ORDER BY extra_price, name')->fetchAll();

// Pre-select a model if arriving from product.php's "Customize This Case" link
$preselectModel = $_GET['model'] ?? '';
$preselectModel = ctype_digit((string)$preselectModel) ? (int)$preselectModel : null;

$modelsJson  = json_encode($models);
$designsJson = json_encode($designs);
?>

<div class="cc-page-header">
  <div class="container">
    <h1><i class="bi bi-magic"></i> Design Your Case</h1>
    <p>Pick your phone, choose a style, and make it yours</p>
  </div>
</div>

<div class="container cc-section pt-0">
  <form action="<?= BASE_URL ?>/actions/save-design.php" method="post" enctype="multipart/form-data" id="designerForm" data-once>
    <?= csrf_field() ?>
    <input type="hidden" name="case_design_id" id="case_design_id" value="<?= $designs ? (int)$designs[0]['id'] : '' ?>">
    <input type="hidden" name="text_position" id="text_position" value="middle">

    <div class="row g-4">

      <!-- ============ LEFT: LIVE PREVIEW ============ -->
      <div class="col-lg-5">
        <div class="cc-card p-4 text-center" style="position: sticky; top: 90px;">
          <h2 class="h6 mb-3">Live Preview</h2>

          <div id="casePreview" class="mx-auto" style="
              width: 220px; height: 440px; border-radius: 36px;
              background: <?= $designs ? e($designs[0]['color_hex']) : '#c4b5fd' ?>;
              position: relative; overflow: hidden;
              box-shadow: 0 12px 32px rgba(59,31,107,.25); border: 6px solid #fff;">

            <!-- camera cutout, just for realism -->
            <div style="position:absolute; top:14px; left:50%; transform:translateX(-50%);
                        width:60px; height:22px; background:rgba(0,0,0,.15); border-radius:12px;"></div>

            <img id="previewImage" src="" alt=""
                 style="display:none; position:absolute; top:50%; left:50%;
                        transform:translate(-50%,-50%); max-width:80%; max-height:60%;
                        border-radius:8px; object-fit:cover;">

            <div id="previewText"
                 style="display:none; position:absolute; left:50%; top:50%;
                        transform:translate(-50%,-50%); width:85%; text-align:center;
                        font-weight:700; font-size:1.1rem; word-wrap:break-word;
                        color:#ffffff; text-shadow:0 1px 3px rgba(0,0,0,.4);"></div>
          </div>

          <p class="mt-3 mb-1 small text-muted" id="previewModelLabel">Choose a phone model above</p>
         <div class="cc-price fs-4" id="previewPrice">
    ₱499.00
</div>
          <p class="small text-muted mb-0" id="previewTotalLabel"></p>
        </div>
      </div>

      <!-- ============ RIGHT: OPTIONS ============ -->
      <div class="col-lg-7">
        <div class="cc-card p-4 p-md-5">

          <!-- Step 1: Brand + Model -->
          <h2 class="h6 mb-3">1. Choose Your Phone</h2>
          <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
              <label class="form-label" for="brand">Brand</label>
              <select class="form-select" id="brand" required>
                <option value="">Select brand</option>
                <?php foreach ($brands as $b): ?>
                  <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="model">Model</label>
              <select class="form-select" id="model" name="phone_model_id" required>
                <option value="">Select brand first</option>
              </select>
            </div>
          </div>

          <!-- Step 2: Case color/design -->
          <h2 class="h6 mb-3">2. Choose a Case Style</h2>
          <div class="case-style-grid mb-4" id="designSwatches">
            <?php foreach ($designs as $i => $d): ?>
              <div class="text-center design-swatch" data-id="<?= (int)$d['id'] ?>"
                   data-price="<?= (float)$d['extra_price'] ?>" style="cursor:pointer;">
                <div class="swatch-circle <?= $i === 0 ? 'selected' : '' ?>" style="
                    width:48px; height:48px; border-radius:50%;
                    background:<?= e($d['color_hex']) ?>;
                    border:3px solid <?= $i === 0 ? 'var(--cc-purple)' : 'transparent' ?>;
                    box-shadow: 0 2px 8px rgba(0,0,0,.15);"></div>
                <p class="small mb-0 mt-1"><?= e($d['name']) ?></p>
                <?php if ($d['extra_price'] > 0): ?>
                  <p class="small text-muted mb-0">+<?= format_price($d['extra_price']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Step 3: Upload image -->
          <h2 class="h6 mb-3">3. Upload an Image (optional)</h2>
          <div class="mb-4">
            <input type="file" class="form-control" id="uploaded_image" name="uploaded_image" accept="image/jpeg,image/png">
            <div class="form-text">JPG or PNG, max 5MB.</div>
            <div id="imageError" class="text-danger small mt-1"></div>
          </div>

          <!-- Step 4: Custom text -->
          <h2 class="h6 mb-3">4. Add Custom Text (optional)</h2>
          <div class="row mb-2">
            <div class="col-md-8 mb-3">
              <label class="form-label" for="custom_text">Text</label>
              <input type="text" class="form-control" id="custom_text" name="custom_text" maxlength="60" placeholder="e.g. Maya">
              <div class="form-text">Up to 60 characters.</div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label" for="text_color">Text Color</label>
              <input type="color" class="form-control form-control-color w-100" id="text_color" name="text_color" value="#ffffff">
            </div>
          </div>

          <!-- Step 5: Text position -->
          <h2 class="h6 mb-3">5. Text Position</h2>
          <div class="btn-group mb-4" role="group" id="positionButtons">
            <button type="button" class="btn btn-cc-outline btn-sm" data-pos="top">Top</button>
            <button type="button" class="btn btn-cc-outline btn-sm active" data-pos="middle">Middle</button>
            <button type="button" class="btn btn-cc-outline btn-sm" data-pos="bottom">Bottom</button>
          </div>

          <!-- Step 6: Quantity + Add to cart -->
          <h2 class="h6 mb-3">6. Quantity</h2>
          <div class="row align-items-end">
            <div class="col-6 col-md-4 mb-3">
              <select class="form-select" name="quantity" id="quantity">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-12 col-md-8 mb-3">
              <button type="submit" class="btn btn-cc-primary btn-lg w-100">
                <i class="bi bi-bag-plus"></i> Add to Cart
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  var allModels  = <?= $modelsJson ?>;
  var allDesigns = <?= $designsJson ?>;
  var preselectModel = <?= $preselectModel ? (int)$preselectModel : 'null' ?>;
  var basePrice = <?= (float)BASE_CUSTOM_PRICE ?>;

  var brandSelect = document.getElementById('brand');
  var modelSelect = document.getElementById('model');
  var caseDesignIdField = document.getElementById('case_design_id');
  var textPositionField = document.getElementById('text_position');

  var previewImage = document.getElementById('previewImage');
  var previewText  = document.getElementById('previewText');
  var casePreview  = document.getElementById('casePreview');
  var previewPrice = document.getElementById('previewPrice');
  var previewTotalLabel = document.getElementById('previewTotalLabel');
  var previewModelLabel = document.getElementById('previewModelLabel');

var selectedExtra = 0;

function money(n) {
    n = Number(n) || 0;
    return '₱' + n.toFixed(2);
}

function updatePrice() {
    var unitPrice = 499 + selectedExtra;
    var qty = parseInt(document.getElementById('quantity').value, 10) || 1;

    previewPrice.textContent = money(unitPrice);

    if (qty > 1) {
        previewTotalLabel.textContent =
            'Total for ' + qty + ': ' + money(unitPrice * qty);
    } else {
        previewTotalLabel.textContent = '';
    }
}
  // ---------- Brand -> Model cascading ----------
  function refreshModels(selectId) {
    var brandId = brandSelect.value;
    modelSelect.innerHTML = '';
    if (!brandId) {
      modelSelect.innerHTML = '<option value="">Select brand first</option>';
      previewModelLabel.textContent = 'Choose a phone model above';
      return;
    }
    var matches = allModels.filter(function (m) { return String(m.brand_id) === brandId; });
    modelSelect.innerHTML = '<option value="">Select model</option>';
    matches.forEach(function (m) {
      var opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.name;
      if (selectId && String(m.id) === String(selectId)) opt.selected = true;
      modelSelect.appendChild(opt);
    });
    updateModelLabel();
  }

  function updateModelLabel() {
    var opt = modelSelect.options[modelSelect.selectedIndex];
    previewModelLabel.textContent = (opt && opt.value) ? opt.textContent : 'Choose a phone model above';
  }

  brandSelect.addEventListener('change', function () { refreshModels(null); });
  modelSelect.addEventListener('change', updateModelLabel);

  // Pre-select brand/model if arriving from a product page
  if (preselectModel) {
    var found = allModels.find(function (m) { return m.id === preselectModel; });
    if (found) {
      brandSelect.value = found.brand_id;
      refreshModels(found.id);
    }
  }

  // ---------- Case design swatches ----------
  document.querySelectorAll('.design-swatch').forEach(function (el) {
    el.addEventListener('click', function () {
      document.querySelectorAll('.swatch-circle').forEach(function (c) {
        c.classList.remove('selected');
        c.style.borderColor = 'transparent';
      });
      var circle = el.querySelector('.swatch-circle');
      circle.classList.add('selected');
      circle.style.borderColor = 'var(--cc-purple)';

      caseDesignIdField.value = el.dataset.id;
      selectedExtra = parseFloat(el.dataset.price) || 0;
      casePreview.style.background = circle.style.background;
      updatePrice();
    });
  });

  // ---------- Image upload preview ----------
  var imageInput = document.getElementById('uploaded_image');
  var imageError = document.getElementById('imageError');
  imageInput.addEventListener('change', function (e) {
    imageError.textContent = '';
    var file = e.target.files[0];
    if (!file) { previewImage.style.display = 'none'; return; }

    var allowed = ['image/jpeg', 'image/png'];
    if (!allowed.includes(file.type)) {
      imageError.textContent = 'Please choose a JPG or PNG image.';
      e.target.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      imageError.textContent = 'Image must be smaller than 5MB.';
      e.target.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function (ev) {
      previewImage.src = ev.target.result;
      previewImage.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  // ---------- Custom text live update ----------
  var textInput = document.getElementById('custom_text');
  var colorInput = document.getElementById('text_color');

  function updateTextPreview() {
    var text = textInput.value.trim();
    if (text === '') {
      previewText.style.display = 'none';
      return;
    }
    previewText.textContent = text;
    previewText.style.color = colorInput.value;
    previewText.style.display = 'block';
  }
  textInput.addEventListener('input', updateTextPreview);
  colorInput.addEventListener('input', updateTextPreview);

  // ---------- Text position buttons ----------
  document.querySelectorAll('#positionButtons button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('#positionButtons button').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var pos = btn.dataset.pos;
      textPositionField.value = pos;

      if (pos === 'top') previewText.style.top = '18%';
      else if (pos === 'bottom') previewText.style.top = '82%';
      else previewText.style.top = '50%';
    });
  });

  // ---------- Quantity ----------
  document.getElementById('quantity').addEventListener('change', updatePrice);

  // ---------- Form validation before submit ----------
  document.getElementById('designerForm').addEventListener('submit', function (e) {
    if (!modelSelect.value) {
      e.preventDefault();
      alert('Please choose a phone brand and model.');
      resetSubmitButton();
      return;
    }
    if (!textInput.value.trim() && !imageInput.files.length) {
      e.preventDefault();
      alert('Please add an image or custom text to personalize your case.');
      resetSubmitButton();
    }
  });
  function resetSubmitButton() {
    var btn = document.querySelector('#designerForm [type="submit"]');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-bag-plus"></i> Add to Cart'; }
  }

  updatePrice();
})();
</script>

<?php require_once 'includes/footer.php'; ?>