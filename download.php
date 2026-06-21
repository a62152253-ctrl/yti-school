<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$note_id = SecurityEnterprise::getInt('id', 0);

if ($note_id <= 0) {
    http_response_code(400);
    die("Brak poprawnego ID materiału.");
}

try {
    // Fetch note details
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();

    if (!$note) {
        http_response_code(404);
        die("Materiał nie istnieje.");
    }

    // Access control check: if premium, user must be owner or must have purchased it
    if (($note['access_type'] ?? 'free') === 'premium') {
        if ($note['user_id'] != $user_id) {
            // Check purchase
            $stmtPur = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ? AND payment_status = 'completed'");
            $stmtPur->execute([$user_id, $note_id]);
            $hasPurchased = (bool)$stmtPur->fetch();
            if (!$hasPurchased) {
                http_response_code(403);
                die("Brak uprawnień. Materiał jest płatny (Premium).");
            }
        }
    }

    // Resolve specific slide path if it's a presentation
    $filepath = $note['filepath'];
    if ($note['file_type'] === 'presentation') {
        $decoded = json_decode($filepath, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $slideIndex = SecurityEnterprise::getInt('slide', 0);
            if (isset($decoded[$slideIndex])) {
                $filepath = $decoded[$slideIndex];
            } else {
                $filepath = $decoded[0] ?? '';
            }
        }
    }

    if (empty($filepath)) {
        http_response_code(404);
        die("Brak pliku w bazie danych.");
    }

    // Secure path check relative to APP_ROOT
    $fullPath = SecurityEnterprise::safePathJoin(APP_ROOT, $filepath);

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        die("Plik nie został znaleziony na serwerze.");
    }

    // Send headers and stream file contents
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fullPath) ?: 'application/octet-stream';

    // Prevent MIME-sniffing
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    header('Cache-Control: private, max-age=86400');

    // Clean output buffer to prevent issues with binary downloads
    if (ob_get_level()) {
        ob_end_clean();
    }

    readfile($fullPath);
    exit;

} catch (\PDOException $e) {
    http_response_code(500);
    die("Błąd bazy danych.");
}
