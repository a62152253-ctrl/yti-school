<?php
require_once __DIR__ . '/db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isTeacher()) {
    redirect('dashboard.php');
}

redirect('student_dashboard.php');
