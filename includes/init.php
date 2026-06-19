<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_BASE')) {
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $appRoot = str_replace('\\', '/', realpath(APP_ROOT) ?: APP_ROOT);
    $base = ($docRoot !== '' && str_starts_with($appRoot, $docRoot))
        ? substr($appRoot, strlen($docRoot))
        : '';
    define('APP_BASE', rtrim($base, '/'));
}

if (!function_exists('assetUrl')) {
    function assetUrl(string $path): string
    {
        $path = ltrim($path, '/');
        return (APP_BASE === '' ? '' : APP_BASE) . '/' . $path;
    }
}

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

SecurityEnterprise::boot();

$dsn = 'sqlite:' . DB_FILE;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if (!$tableCheck) {
        $schemaFile = APP_ROOT . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `personal_notes` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `user_id` INTEGER NOT NULL,
      `note_id` INTEGER NOT NULL,
      `content` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE,
      UNIQUE(user_id, note_id)
    );");

    // Ensure uploads directory exists and is not directly executable
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Migration: Check and add teacher verification columns to users table
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('is_verified', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified INTEGER DEFAULT 0");
        // Update existing users to be verified
        $pdo->exec("UPDATE users SET is_verified = 1");
    }
    if (!in_array('school_name', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN school_name TEXT DEFAULT NULL");
    }
    if (!in_array('rspo_number', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN rspo_number TEXT DEFAULT NULL");
    }
    if (!in_array('teacher_card_number', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN teacher_card_number TEXT DEFAULT NULL");
    }
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed.');
}
