<?php
require_once 'db.php';
require_once 'includes/profanity_filter.php';
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
$content = trim($_POST['content'] ?? '');

if (!$note_id || empty($content) || strlen($content) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

if (ProfanityFilter::hasProfanity($content)) {
    echo json_encode(['success' => false, 'message' => 'Komentarz zawiera niedozwolone słownictwo (wulgaryzmy).']);
    exit;
}

try {
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (note_id, user_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$note_id, $user_id, $content]);
    
    // Notify note owner
    $stmt = $pdo->prepare("SELECT user_id FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    
    if ($note && $note['user_id'] != $user_id) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $commenter = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, link) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $note['user_id'],
            'Nowy komentarz 💬',
            $commenter['username'] . ': "' . substr($content, 0, 40) . '..."',
            'watch.php?id=' . $note_id . '#comments'
        ]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
