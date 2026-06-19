<?php
// Application configuration
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Debug mode: set to true only in development
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

define('APP_NAME', 'Yti School Hub');
define('DB_FILE', APP_ROOT . '/yti.sqlite');
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('UPLOAD_URL_PATH', 'uploads');
define('MAX_FILE_UPLOAD_SIZE', 50 * 1024 * 1024); // 50 MB

define('ALLOWED_UPLOAD_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
]);

define('ALLOWED_PRESENTATION_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
]);

define('ALLOWED_MIME_EXTENSIONS', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
]);

// Stripe Configuration
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_51234567890abcdefghij');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_51234567890abcdefghij');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_test_1234567890abcdef');

// Email Configuration
define('ADMIN_EMAIL', 'jankom@eskp.pl');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
define('MAIL_FROM_NAME', 'YTI School Hub');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@yti-school.pl');
