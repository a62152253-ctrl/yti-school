<?php
require_once 'db.php';
requireLogin();

$q = sanitize($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $query = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.subject, u.username AS author_name
        FROM notes n
        JOIN users u ON n.user_id = u.id
        WHERE (n.title LIKE ? OR n.tags LIKE ? OR n.description LIKE ?) 
        LIMIT 8
    ");
    $stmt->execute([$query, $query, $query]);
    $results = $stmt->fetchAll();
    
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
