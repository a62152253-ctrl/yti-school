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
$note_id = (int)($_POST['note_id'] ?? 0);

if (!$note_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid note']);
    exit;
}

try {
    // Check if already in watch later
    $stmt = $pdo->prepare("SELECT 1 FROM watch_later WHERE user_id = ? AND note_id = ?");
    $stmt->execute([$user_id, $note_id]);
    $exists = (bool)$stmt->fetch();

    if ($exists) {
        // Remove from watch later
        $stmt = $pdo->prepare("DELETE FROM watch_later WHERE user_id = ? AND note_id = ?");
        $stmt->execute([$user_id, $note_id]);
        $added = false;
    } else {
        // Add to watch later
        $stmt = $pdo->prepare("INSERT INTO watch_later (user_id, note_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $note_id]);
        $added = true;
    }

    echo json_encode([
        'success' => true,
        'added' => $added
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
