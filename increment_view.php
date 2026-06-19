<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    try {
        // Increment views
        $stmt = $pdo->prepare("UPDATE notes SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);

        // If user is a student, also log/update history entry
        if (isStudent()) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO history (user_id, note_id, watched_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$user_id, $id]);
        }

        // Get current view count
        $stmt = $pdo->prepare("SELECT views FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        $note = $stmt->fetch();

        echo json_encode(['success' => true, 'views' => $note['views']]);
        exit;
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid ID']);
?>
