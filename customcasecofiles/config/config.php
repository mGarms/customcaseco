<?php
// Global settings + session start. Include this FIRST on every page.

define('SITE_NAME', 'Custom Case Co.');
define('SITE_TAGLINE', 'Your Phone. Your Style.');
define('BASE_URL', '');
define('CURRENCY_SYMBOL', '₱');
define('FIRST_ORDER_DISCOUNT', 0.20); // 20% off, NOT a flat amount
define('BASE_CUSTOM_PRICE', 499.00);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');