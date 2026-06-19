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
        SELECT id, title, subject 
        FROM notes 
        WHERE (title LIKE ? OR tags LIKE ? OR description LIKE ?) 
        LIMIT 8
    ");
    $stmt->execute([$query, $query, $query]);
    $results = $stmt->fetchAll();
    
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
