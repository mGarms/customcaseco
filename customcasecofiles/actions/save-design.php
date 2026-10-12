<?php
// =====================================================
// Saves a custom design and adds it to the user's cart
// =====================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload-helper.php';

require_login();

// Safety net: define the constant here if it wasn't already defined in
// config/config.php, so this script can never fatal-crash over it.
// The real fix is adding it to config.php (see Step 1 above).
if (!defined('BASE_CUSTOM_PRICE')) {
    define('BASE_CUSTOM_PRICE', 16.99);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request. Please try again.');
    redirect('customize.php');
}

$userId       = $_SESSION['user_id'];
$phoneModelId = $_POST['phone_model_id'] ?? '';
$caseDesignId = $_POST['case_design_id'] ?? '';
$customText   = trim($_POST['custom_text'] ?? '');
$textColor    = $_POST['text_color'] ?? '#ffffff';
$textPosition = $_POST['text_position'] ?? 'middle';
$quantity     = $_POST['quantity'] ?? 1;

$errors = [];

// ---------- Validate ----------
if (!ctype_digit((string)$phoneModelId)) $errors[] = 'Please choose a phone model.';
if (!ctype_digit((string)$caseDesignId)) $errors[] = 'Please choose a case style.';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $textColor)) $textColor = '#ffffff';
if (!in_array($textPosition, ['top', 'middle', 'bottom'], true)) $textPosition = 'middle';

$quantity = ctype_digit((string)$quantity) ? (int)$quantity : 1;
$quantity = max(1, min(10, $quantity));

if ($customText === '' && empty($_FILES['uploaded_image']['name'])) {
    $errors[] = 'Please add an image or custom text to personalize your case.';
}
if (mb_strlen($customText) > 60) {
    $errors[] = 'Custom text must be 60 characters or fewer.';
}

// Confirm the model and design actually exist and are active (never trust the client)
if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id FROM phone_models WHERE id = :id AND is_active = 1');
    $stmt->execute([':id' => $phoneModelId]);
    if (!$stmt->fetch()) $errors[] = 'That phone model is not available.';
}
if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id, extra_price FROM case_designs WHERE id = :id AND is_active = 1');
    $stmt->execute([':id' => $caseDesignId]);
    $design = $stmt->fetch();
    if (!$design) $errors[] = 'That case style is not available.';
}

if ($errors) {
    set_flash('danger', implode(' ', $errors));
    redirect('customize.php');
}

// ---------- Handle the image upload ----------
$uploadDir = __DIR__ . '/../uploads/custom_designs/';
$upload = handle_image_upload($_FILES['uploaded_image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $uploadDir, 'design_');

if (!$upload['ok']) {
    set_flash('danger', $upload['error']);
    redirect('customize.php');
}

// ---------- Map the position preset to stored coordinates ----------
$positions = [
    'top'    => 20.00,
    'middle' => 50.00,
    'bottom' => 80.00,
];
$textY = $positions[$textPosition];

$unitPrice = BASE_CUSTOM_PRICE + (float)$design['extra_price'];

// ---------- Save the design, then add it to the cart ----------
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO custom_designs
            (user_id, phone_model_id, case_design_id, uploaded_image,
             custom_text, text_color, text_x, text_y, price)
         VALUES
            (:user_id, :model_id, :design_id, :image,
             :text, :text_color, 50.00, :text_y, :price)'
    );
    $stmt->execute([
        ':user_id'    => $userId,
        ':model_id'   => $phoneModelId,
        ':design_id'  => $caseDesignId,
        ':image'      => $upload['filename'],
        ':text'       => $customText !== '' ? $customText : null,
        ':text_color' => $textColor,
        ':text_y'     => $textY,
        ':price'      => $unitPrice,
    ]);
    $designId = $pdo->lastInsertId();

    $cartId = get_or_create_cart_id($pdo, $userId);

    $stmt = $pdo->prepare(
        'INSERT INTO cart_items (cart_id, custom_design_id, quantity) VALUES (:cart_id, :design_id, :qty)'
    );
    $stmt->execute([':cart_id' => $cartId, ':design_id' => $designId, ':qty' => $quantity]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    if ($upload['filename'] && file_exists($uploadDir . $upload['filename'])) {
        unlink($uploadDir . $upload['filename']); // clean up the orphaned file
    }
    error_log('save-design failed: ' . $e->getMessage());
    set_flash('danger', 'Something went wrong saving your design. Please try again.');
    redirect('customize.php');
}

set_flash('success', 'Your custom case has been added to your cart!');
redirect('cart.php');