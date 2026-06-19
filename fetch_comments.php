<?php
require_once 'db.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');
$note_id = (int)($_GET['note_id'] ?? 0);

if (!$note_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.note_id = ? AND c.parent_id IS NULL 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$note_id]);
    $comments = $stmt->fetchAll();
    
    // Format dates and censor profanity
    require_once 'includes/profanity_filter.php';
    foreach ($comments as &$c) {
        $c['created_at'] = date('d.m.Y H:i', strtotime($c['created_at']));
        $c['content'] = ProfanityFilter::censor($c['content']);
    }
    
    echo json_encode($comments);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
