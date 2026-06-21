<?php
$pageTitle = 'Użytkownicy - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $stmtUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmtUsers->fetchAll();
} catch (\PDOException $e) {
    die("Błąd pobierania danych użytkowników: " . $e->getMessage());
}

include 'admin/users.php';
require_once 'admin/footer.php';
?>
