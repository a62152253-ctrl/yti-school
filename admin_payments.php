<?php
$pageTitle = 'Transakcje i zakupy - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $stmtPurchases = $pdo->query("
        SELECT p.*, u.username, n.title 
        FROM purchases p 
        JOIN users u ON p.user_id = u.id 
        JOIN notes n ON p.note_id = n.id 
        ORDER BY p.created_at DESC
    ");
    $purchases = $stmtPurchases->fetchAll();
} catch (\PDOException $e) {
    die("Błąd pobierania transakcji: " . $e->getMessage());
}

include 'admin/payments.php';
require_once 'admin/footer.php';
?>
