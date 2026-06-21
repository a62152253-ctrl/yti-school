<?php
$pageTitle = 'Materiały dydaktyczne - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $stmtNotes = $pdo->query("SELECT n.*, u.username FROM notes n JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC");
    $notes = $stmtNotes->fetchAll();
} catch (\PDOException $e) {
    die("Błąd pobierania materiałów: " . $e->getMessage());
}

include 'admin/materials.php';
require_once 'admin/footer.php';
?>
