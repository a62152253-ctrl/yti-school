<?php
require_once 'db.php';
requireLogin();

if (!isStudentCreator()) {
    redirect('student_dashboard.php');
}

$id = SecurityEnterprise::getInt('id', 0);
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT filepath, file_type FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $note = $stmt->fetch();

        if ($note) {
            if ($note['file_type'] === 'presentation') {
                $decoded = json_decode($note['filepath'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $path) {
                        $fullPath = SecurityEnterprise::safePathJoin(APP_ROOT, $path);
                        if (file_exists($fullPath) && is_file($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }
            } else {
                $fullPath = SecurityEnterprise::safePathJoin(APP_ROOT, $note['filepath']);
                if (file_exists($fullPath) && is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
        }
    } catch (\PDOException $e) {
        // Silent database errors handled gracefully
    }
}

redirect('student_creator_dashboard.php');
?>
