<?php
require_once 'db.php';
requireTeacher();

SecurityEnterprise::requirePost();
SecurityEnterprise::requireCsrf();

$note_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($note_id > 0) {
    try {
        // Fetch file path to delete from physical disk
        $stmt = $pdo->prepare("SELECT filepath, file_type FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
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

            // Delete database entry (defensively ensuring user ownership)
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$note_id, $user_id]);
        }
    } catch (\PDOException $e) {
        // Log or handle silently
    }
}

redirect('dashboard.php?msg=note_deleted');
?>
