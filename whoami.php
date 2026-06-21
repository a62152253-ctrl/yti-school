<?php
require_once 'db.php';
header('Content-Type: application/json');
echo json_encode([
    'logged_in' => isLoggedIn(),
    'session' => $_SESSION,
]);
