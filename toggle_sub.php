<?php
require_once 'db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$teacher_id = (int)($_POST['teacher_id'] ?? 0);
SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

if (!$teacher_id || $teacher_id === $user_id) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy identyfikator']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT 1 FROM subscriptions WHERE student_id = ? AND teacher_id = ?");
    $stmt->execute([$user_id, $teacher_id]);
    $exists = (bool)$stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE student_id = ? AND teacher_id = ?");
        $stmt->execute([$user_id, $teacher_id]);
        $subscribed = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO subscriptions (student_id, teacher_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $teacher_id]);
        $subscribed = true;
    }

    echo json_encode([
        'success' => true,
        'subscribed' => $subscribed
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']);
}
?>