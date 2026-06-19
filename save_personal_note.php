<?php
require_once 'db.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityEnterprise::requirePost();
    SecurityEnterprise::assertSameOrigin();
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

    $note_id = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($note_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID lekcji.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO personal_notes (user_id, note_id, content, updated_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(user_id, note_id) DO UPDATE SET 
                content = excluded.content,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $note_id, $content]);
        echo json_encode(['success' => true, 'message' => 'Notatka zapisana.']);
        exit;
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Błąd zapisu w bazie danych: ' . $e->getMessage()]);
        exit;
    }
} else {
    // GET request to load
    $note_id = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;
    if ($note_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID lekcji.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT content FROM personal_notes WHERE user_id = ? AND note_id = ?");
        $stmt->execute([$user_id, $note_id]);
        $row = $stmt->fetch();
        $content = $row ? $row['content'] : '';
        echo json_encode(['success' => true, 'content' => $content]);
        exit;
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Błąd odczytu bazy danych.']);
        exit;
    }
}
