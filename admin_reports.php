<?php
$pageTitle = 'Zgłoszenia - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $stmtReports = $pdo->query("
        SELECT r.id as report_id, r.reason, r.created_at as reported_at, n.id as note_id, n.title, u.username as reporter 
        FROM reports r 
        JOIN notes n ON r.note_id = n.id 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $reports = $stmtReports->fetchAll();
} catch (\PDOException $e) {
    die("Błąd pobierania zgłoszeń: " . $e->getMessage());
}

include 'admin/reports.php';
require_once 'admin/footer.php';
?>
