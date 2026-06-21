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

$playlist_id = (int)($_POST['playlist_id'] ?? 0);
$note_ids_json = $_POST['note_ids'] ?? '[]';
$note_ids = json_decode($note_ids_json, true);
$user_id = $_SESSION['user_id'];

if (!$playlist_id || empty($note_ids)) {
    echo json_encode(['success' => false, 'message' => 'Błędne parametry.']);
    exit;
}

try {
    // Verify playlist belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT 1 FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Brak uprawnień do tej playlisty.']);
        exit;
    }

    $pdo->beginTransaction();
    // Update position for each note in the playlist
    $stmtUpdate = $pdo->prepare("UPDATE playlist_notes SET position = ? WHERE playlist_id = ? AND note_id = ?");
    foreach ($note_ids as $index => $note_id) {
        $stmtUpdate->execute([$index + 1, $playlist_id, (int)$note_id]);
    }
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?>
