<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_id = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
    $reason  = trim($_POST['reason'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($note_id <= 0 || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data. Please specify a reason.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO reports (note_id, user_id, reason) VALUES (?, ?, ?)");
        $stmt->execute([$note_id, $user_id, $reason]);
        echo json_encode(['success' => true]);
        exit;
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
