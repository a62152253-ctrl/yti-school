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

    // Migration: Create school_codes table and seed it
    $pdo->exec("CREATE TABLE IF NOT EXISTS `school_codes` (
        `code` TEXT PRIMARY KEY,
        `school_name` TEXT NOT NULL
    );");
    $codeCheck = $pdo->query("SELECT COUNT(*) FROM school_codes")->fetchColumn();
    if ($codeCheck == 0) {
        $stmtSeed = $pdo->prepare("INSERT INTO school_codes (code, school_name) VALUES (?, ?)");
        $stmtSeed->execute(['SCHOOL123', 'I Liceum Ogólnokształcące w Warszawie']);
        $stmtSeed->execute(['TEACH2026', 'Szkoła Podstawowa nr 5 w Krakowie']);
        $stmtSeed->execute(['YTI999', 'Technikum Informatyczne w Poznaniu']);
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
    if (!in_array('is_student_creator', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_student_creator INTEGER DEFAULT 0");
    }
    if (!in_array('verification_document', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_document TEXT DEFAULT NULL");
    }

    // Migration: Check and update purchases table schema to support premium features (id, stripe_id, payment_status)
    $purchaseCols = $pdo->query("PRAGMA table_info(purchases)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('payment_status', $purchaseCols, true)) {
        $pdo->exec("ALTER TABLE purchases RENAME TO purchases_old");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `purchases` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `user_id` INTEGER NOT NULL,
          `note_id` INTEGER NOT NULL,
          `amount` REAL NOT NULL,
          `stripe_id` TEXT UNIQUE DEFAULT NULL,
          `payment_status` TEXT DEFAULT 'pending' CHECK(payment_status IN ('pending', 'completed', 'failed')),
          `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE(user_id, note_id),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
        );");
        
        // Copy old data if any existed
        $pdo->exec("INSERT INTO purchases (user_id, note_id, amount, paid_at, payment_status) 
                    SELECT user_id, note_id, amount, paid_at, 'completed' FROM purchases_old");
        
        $pdo->exec("DROP TABLE purchases_old");
    }
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed.');
}
