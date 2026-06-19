<?php
require_once 'db.php';

class NotificationService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Dodaj powiadomienie
     */
    public function create($user_id, $title, $message, $link = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$user_id, $title, $message, $link]);
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Powiadomienie o zakupie lekcji
     */
    public function notifyPurchase($student_id, $note_id, $amount, $teacher_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT title FROM notes WHERE id = ?");
            $stmt->execute([$note_id]);
            $note = $stmt->fetch();
            
            if (!$note) return false;

            // Powiadomienie dla studenta
            $this->create(
                $student_id,
                "Zakupiono lekcję ✓",
                "Kupiłeś '{$note['title']}' za " . number_format($amount, 2) . " PLN",
                "my_lessons.php"
            );

            // Powiadomienie dla nauczyciela
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();

            $this->create(
                $teacher_id,
                "Nowa sprzedaż! 💰",
                "{$student['username']} kupił Twoją lekcję '{$note['title']}' za " . number_format($amount, 2) . " PLN",
                "dashboard.php"
            );

            return true;
        } catch (PDOException $e) {
            error_log("Purchase notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Powiadomienie o subskrypcji
     */
    public function notifySubscription($student_id, $teacher_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();

            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch();

            // Powiadomienie dla nauczyciela
            $this->create(
                $teacher_id,
                "Nowy obserwujący! ⭐",
                "{$student['username']} subskrybuje Twoje materiały",
                "dashboard.php"
            );

            // Powiadomienie dla studenta
            $this->create(
                $student_id,
                "Subskrypcja potwierdzana ✓",
                "Obserwujesz teraz nauczyciela {$teacher['username']}",
                "profile.php?user_id={$teacher_id}"
            );

            return true;
        } catch (PDOException $e) {
            error_log("Subscription notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Powiadomienie o komentarzu
     */
    public function notifyComment($note_id, $commenter_id, $teacher_id, $content_preview) {
        try {
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$commenter_id]);
            $commenter = $stmt->fetch();

            $this->create(
                $teacher_id,
                "Nowy komentarz 💬",
                "{$commenter['username']}: \"" . substr($content_preview, 0, 40) . "...\"",
                "watch.php?id={$note_id}#comments"
            );

            return true;
        } catch (PDOException $e) {
            error_log("Comment notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobierz powiadomienia użytkownika
     */
    public function getNotifications($user_id, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Liczba nieprzeczytanych powiadomień
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Oznacz jako przeczytane
     */
    public function markAsRead($notification_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1 WHERE id = ?
            ");
            return $stmt->execute([$notification_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Oznacz wszystkie jako przeczytane
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Usuń powiadomienie
     */
    public function delete($notification_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE id = ?");
            return $stmt->execute([$notification_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Usuń wszystkie powiadomienia
     */
    public function deleteAll($user_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Singleton instance
$notificationService = new NotificationService($pdo);
?>
