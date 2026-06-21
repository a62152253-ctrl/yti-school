<?php
$pageTitle = 'Kody Szkół - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $schoolCodes = $pdo->query("SELECT * FROM school_codes ORDER BY school_name ASC")->fetchAll();
} catch (\PDOException $e) {
    die("Błąd pobierania kodów szkół: " . $e->getMessage());
}

include 'admin/school_codes.php';
require_once 'admin/footer.php';
?>
