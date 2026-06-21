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
        SELECT c.id, c.content, c.parent_id, c.created_at, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.note_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$note_id]);
    $all_comments = $stmt->fetchAll();
    
    $total_count = count($all_comments);
    
    // Format dates and censor profanity
    require_once 'includes/profanity_filter.php';
    $comments_by_id = [];
    foreach ($all_comments as &$c) {
        $c['created_at'] = date('d.m.Y H:i', strtotime($c['created_at']));
        $c['content'] = ProfanityFilter::censor($c['content']);
        $c['replies'] = [];
        $comments_by_id[$c['id']] = &$c;
    }
    unset($c); // break the reference

    $root_comments = [];
    foreach ($all_comments as &$c) {
        if ($c['parent_id'] === null || $c['parent_id'] === '' || $c['parent_id'] == 0) {
            $root_comments[] = &$c;
        } else {
            $pid = $c['parent_id'];
            if (isset($comments_by_id[$pid])) {
                $comments_by_id[$pid]['replies'][] = &$c;
            } else {
                $root_comments[] = &$c;
            }
        }
    }
    unset($c); // break the reference

    // Main comments should show newest first
    $root_comments = array_reverse($root_comments);
    
    echo json_encode([
        'total_count' => $total_count,
        'comments' => $root_comments
    ]);
} catch (PDOException $e) {
    echo json_encode(['total_count' => 0, 'comments' => []]);
}
?>
