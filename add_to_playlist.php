<?php
require_once 'db.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda niedozwolona.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Require CSRF token
SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

$note_id = (int)($_POST['note_id'] ?? 0);
$playlist_id = (int)($_POST['playlist_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$note_id || !$playlist_id) {
    echo json_encode(['success' => false, 'message' => 'Błędne parametry wejściowe.']);
    exit;
}

try {
    // Verify playlist belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT 1 FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Brak uprawnień do wybranej playlisty.']);
        exit;
    }

    // Verify note belongs to the logged-in user (only teacher can edit/manage their own notes)
    $stmt = $pdo->prepare("SELECT 1 FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Brak uprawnień do tego materiału.']);
        exit;
    }

    // Check if the note is already in this playlist
    $stmt = $pdo->prepare("SELECT 1 FROM playlist_notes WHERE playlist_id = ? AND note_id = ?");
    $stmt->execute([$playlist_id, $note_id]);
    $exists = (bool)$stmt->fetch();

    if ($exists) {
        // Remove from playlist
        $stmt = $pdo->prepare("DELETE FROM playlist_notes WHERE playlist_id = ? AND note_id = ?");
        $stmt->execute([$playlist_id, $note_id]);
        $added = false;
        $message = 'Usunięto materiał z playlisty.';
    } else {
        // Add to playlist
        // Get the current maximum position to append it at the end
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) FROM playlist_notes WHERE playlist_id = ?");
        $stmt->execute([$playlist_id]);
        $maxPos = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO playlist_notes (playlist_id, note_id, position) VALUES (?, ?, ?)");
        $stmt->execute([$playlist_id, $note_id, $maxPos + 1]);
        $added = true;
        $message = 'Dodano materiał do playlisty.';
    }

    echo json_encode([
        'success' => true,
        'added' => $added,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
    exit;
}
?>
