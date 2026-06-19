<?php
require_once 'db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

$user_id = $_SESSION['user_id'];
$note_id = (int)($_POST['id'] ?? 0);

if (!$note_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid note']);
    exit;
}

try {
    // Check if already bookmarked
    $stmt = $pdo->prepare("SELECT 1 FROM my_lessons WHERE user_id = ? AND note_id = ?");
    $stmt->execute([$user_id, $note_id]);
    $exists = (bool)$stmt->fetch();

    if ($exists) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM my_lessons WHERE user_id = ? AND note_id = ?");
        $stmt->execute([$user_id, $note_id]);
        $bookmarked = false;
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO my_lessons (user_id, note_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $note_id]);
        $bookmarked = true;
    }

    echo json_encode([
        'success' => true,
        'is_bookmarked' => $bookmarked
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
