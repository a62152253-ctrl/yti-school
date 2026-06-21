<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$note_id = (int)($_GET['id'] ?? 0);

// Fetch note details
try {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    
    if (!$note) {
        redirect('student_dashboard.php');
    }
} catch (PDOException $e) {
    redirect('student_dashboard.php');
}

// Handle mock purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($amount > 0) {
        try {
            // Insert purchase
            $stmt = $pdo->prepare("
                INSERT INTO purchases (user_id, note_id, amount, payment_status) 
                VALUES (?, ?, ?, 'completed')
            ");
            $stmt->execute([$user_id, $note_id, $amount]);
            
            // Create notification for student
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                'Zakupiono lekcję ✓',
                "Kupiłeś '" . htmlspecialchars($note['title']) . "' za " . number_format($amount, 2) . " PLN",
                'page_favorites.php'
            ]);
            
            // Create notification for teacher
            $teacher_id = $note['user_id'];
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $student = $stmt->fetch();
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $teacher_id,
                'Nowa sprzedaż! 💰',
                $student['username'] . " kupił Twoją lekcję '" . htmlspecialchars($note['title']) . "' za " . number_format($amount, 2) . " PLN",
                'dashboard.php'
            ]);
            
            // Add to my_lessons
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO my_lessons (user_id, note_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $note_id]);
            
            $_SESSION['purchase_success'] = true;
            redirect('watch.php?id=' . $note_id . '&purchased=1');
        } catch (PDOException $e) {
            $error = "Błąd podczas przetwarzania zakupu: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout - Yti School';
$activePage = '';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <main class="main-content" style="max-width: 500px; margin: 80px auto; padding: 40px 20px;">
            <div class="glass-card" style="border-radius: 16px; padding: 32px;">
                <h2 style="font-size: 1.8rem; font-weight: 600; color: #ffffff; margin: 0 0 8px 0;">Potwierdź zakup</h2>
                <p style="color: var(--text-secondary); margin: 0 0 32px 0; font-size: 0.95rem;">Finalizujesz transakcję w naszym demo systemie</p>

                <div class="checkout-item" style="background: #1c1c1e; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="font-size: 2rem;">📚</div>
                        <div style="flex-grow: 1;">
                            <h3 style="margin: 0; color: #ffffff; font-size: 1.1rem; font-weight: 600;">
                                <?= htmlspecialchars($note['title']) ?>
                            </h3>
                            <p style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 0.85rem;">
                                Przedmiot: <?= htmlspecialchars($note['subject']) ?> • Klasa: <?= htmlspecialchars($note['class_level']) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div style="background: #1c1c1e; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="color: var(--text-secondary); font-size: 0.9rem;">Cena:</span>
                        <span style="color: #ffffff; font-size: 1.3rem; font-weight: 600;">
                            <?= number_format((float)$note['premium_price'], 2, ',', ' ') ?> PLN
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="color: var(--text-secondary); font-size: 0.85rem;">Metoda płatności:</span>
                        <span style="color: #ffffff; font-size: 0.9rem; font-weight: 500;">Visa •••• 4242</span>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div style="background: rgba(255, 69, 58, 0.15); border: 1px solid rgba(255, 69, 58, 0.2); color: #ff453a; border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 0.85rem;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="amount" value="<?= (float)$note['premium_price'] ?>">
                    <button type="submit" name="confirm_purchase" class="btn btn-primary" style="margin-bottom: 12px; font-size: 1rem;">
                        Zapłać <?= number_format((float)$note['premium_price'], 2, ',', ' ') ?> PLN
                    </button>
                </form>

                <a href="watch.php?id=<?= $note_id ?>" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none; margin-bottom: 16px;">Anuluj</a>

                <div style="background: rgba(100, 210, 255, 0.12); border: 1px solid rgba(100, 210, 255, 0.2); border-radius: 8px; padding: 12px; font-size: 0.8rem; color: #64d2ff;">
                    <strong>Demo:</strong> To jest symulacja zakupu. Po kliknięciu "Zapłać" otrzymasz powiadomienie o transakcji.
                </div>
            </div>
        </main>
    </div>

    <script src="app.js"></script>
</body>
</html>
