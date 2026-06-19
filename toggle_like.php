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
$type = sanitize($_POST['type'] ?? '');

if (!$note_id || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Check if user already voted
    $stmt = $pdo->prepare("SELECT type FROM likes WHERE user_id = ? AND note_id = ?");
    $stmt->execute([$user_id, $note_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['type'] === $type) {
            // Remove vote if clicking same type again
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND note_id = ?");
            $stmt->execute([$user_id, $note_id]);
            $user_vote = null;
        } else {
            // Update to new type
            $stmt = $pdo->prepare("UPDATE likes SET type = ? WHERE user_id = ? AND note_id = ?");
            $stmt->execute([$type, $user_id, $note_id]);
            $user_vote = $type;
        }
    } else {
        // Insert new vote
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, note_id, type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $note_id, $type]);
        $user_vote = $type;
    }

    // Get counts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE note_id = ? AND type = 'like'");
    $stmt->execute([$note_id]);
    $likes = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE note_id = ? AND type = 'dislike'");
    $stmt->execute([$note_id]);
    $dislikes = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'likes' => $likes,
        'dislikes' => $dislikes,
        'user_vote' => $user_vote
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
