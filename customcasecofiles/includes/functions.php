<?php

// ============================================================
// GENERAL HELPER FUNCTIONS
// ============================================================

/**
 * Escape HTML output
 */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/**
 * Redirect to another page
 */
function redirect($url)
{
    // Reject full external URLs (safety) and force every redirect to be
    // resolved from the site root, regardless of which folder the current
    // page lives in (fixes admin/ pages double-prefixing their own path).
    $url = ltrim($url, '/');
    if (preg_match('#^https?://#i', $url)) {
        $url = 'index.php';
    }
    header('Location: /' . $url);
    exit;
}


/**
 * Format price
 */
function format_price($amount)
{
    return '₱' . number_format((float)$amount, 2);
}


// ============================================================
// AUTHENTICATION
// ============================================================

/**
 * Check if a user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


/**
 * Require user to be logged in
 */
function require_login()
{
    if (!is_logged_in()) {
        set_flash(
            'danger',
            'Please log in to continue.'
        );

        redirect('login.php');
    }
}


/**
 * Check if current user is an administrator
 */
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


/**
 * Require administrator access
 *
 * This performs an additional database check
 * to make sure the account is still active
 * and still has the admin role.
 */
function require_admin()
{
    global $pdo;

    if (!is_logged_in() || !is_admin()) {
        set_flash(
            'danger',
            'Please log in as an administrator to continue.'
        );

        redirect('admin/login.php');
    }

    // Verify the user's current status and role
    $stmt = $pdo->prepare(
        'SELECT 
            u.is_active,
            r.name AS role_name
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id'
    );

    $stmt->execute([
        ':id' => $_SESSION['user_id']
    ]);

    $current = $stmt->fetch();

    // Account does not exist, is inactive,
    // or is no longer an administrator
    if (
        !$current ||
        !$current['is_active'] ||
        $current['role_name'] !== 'admin'
    ) {
        redirect('admin/logout.php');
    }
}


// ============================================================
// FLASH MESSAGES
// ============================================================

/**
 * Set a flash message
 */
function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


/**
 * Display and remove flash message
 */
function show_flash()
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];

    unset($_SESSION['flash']);

    $type = e($flash['type']);
    $message = e($flash['message']);

    echo '
        <div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
            ' . $message . '
            <button 
                type="button" 
                class="btn-close" 
                data-bs-dismiss="alert"
                aria-label="Close">
            </button>
        </div>
    ';
}


// ============================================================
// CSRF PROTECTION
// ============================================================

/**
 * Generate or retrieve CSRF token
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/**
 * Generate hidden CSRF form field
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' .
        e(csrf_token()) .
        '">';
}


/**
 * Verify CSRF token
 */
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        set_flash(
            'danger',
            'Invalid security token. Please try again.'
        );

        return false;
    }

    return true;
}


// ============================================================
// CART FUNCTIONS
// ============================================================

/**
 * Get total number of items in cart
 */
function cart_count()
{
    global $pdo;

    if (!is_logged_in()) {
        return 0;
    }

    $cartId = get_or_create_cart_id();

    if (!$cartId) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(quantity), 0)
         FROM cart_items
         WHERE cart_id = :cart_id'
    );

    $stmt->execute([
        ':cart_id' => $cartId
    ]);

    return (int)$stmt->fetchColumn();
}


/**
 * Get or create the logged-in user's cart
 */
function get_or_create_cart_id()
{
    global $pdo;

    if (!is_logged_in()) {
        return null;
    }

    $userId = $_SESSION['user_id'];

    // Check if the user already has a cart
    $stmt = $pdo->prepare(
        'SELECT id
         FROM carts
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $cartId = $stmt->fetchColumn();

    if ($cartId) {
        return $cartId;
    }

    // Create a new cart
    $stmt = $pdo->prepare(
        'INSERT INTO carts (user_id)
         VALUES (:user_id)'
    );

    $stmt->execute([
        ':user_id' => $userId
    ]);

    return $pdo->lastInsertId();
}


// ============================================================
// LOGIN SECURITY / THROTTLING
// ============================================================

function check_login_throttle()
{
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;

    if ($lockedUntil > time()) {
        $wait = $lockedUntil - time();

        return "Too many failed attempts. Please try again in {$wait} seconds.";
    }

    return null;
}


function register_failed_login()
{
    $_SESSION['login_attempts'] =
        ($_SESSION['login_attempts'] ?? 0) + 1;

    if ($_SESSION['login_attempts'] >= 5) {

        $_SESSION['login_locked_until'] =
            time() + 60;

        $_SESSION['login_attempts'] = 0;
    }
}


function clear_login_throttle()
{
    unset(
        $_SESSION['login_attempts'],
        $_SESSION['login_locked_until']
    );
}
