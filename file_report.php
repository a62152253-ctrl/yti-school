<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';
$note = null;

// Determine if JSON response is expected/requested (e.g. by API client or AJAX)
$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_ajax) {
        header('Content-Type: application/json');
    }
    
    try {
        SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    } catch (\Exception $e) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Błąd CSRF: nieprawidłowe żądanie.']);
            exit;
        }
        $error_msg = 'Błąd CSRF: nieprawidłowe żądanie.';
    }

    if (empty($error_msg)) {
        $note_id = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
        $reason  = trim($_POST['reason'] ?? '');

        if ($note_id <= 0 || empty($reason)) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Proszę podać powód zgłoszenia.']);
                exit;
            }
            $error_msg = 'Proszę podać powód zgłoszenia.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO reports (note_id, user_id, reason) VALUES (?, ?, ?)");
                $stmt->execute([$note_id, $user_id, $reason]);
                
                if ($is_ajax) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                $success_msg = 'Zgłoszenie zostało pomyślnie wysłane. Dziękujemy za pomoc!';
            } catch (\PDOException $e) {
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
                    exit;
                }
                $error_msg = 'Wystąpił błąd bazy danych przy wysyłaniu zgłoszenia.';
            }
        }
    }
}

// Fetch note info for displaying
$note_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : (isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0);
if ($note_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, title FROM notes WHERE id = ?");
        $stmt->execute([$note_id]);
        $note = $stmt->fetch();
    } catch (\PDOException $e) {}
}

if (!$note) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Materiał nie istnieje.']);
        exit;
    }
    die('Nieprawidłowy identyfikator materiału.');
}
?>
<?php
$pageTitle = 'Zgłoś materiał - Yti School';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>

    <div class="auth-wrapper">
        <div class="auth-container" style="max-width: 500px; background: #1f1f1f; border: 1px solid var(--card-border); border-radius: 16px;">
            <div class="auth-header">
                <h1 style="font-size: 1.6rem; font-weight: 700; color: #fff; margin-bottom: 8px;">Zgłoś materiał</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Pomóż nam dbać o wysoką jakość materiałów w yti School</p>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
                <div style="margin-top: 25px;">
                    <a href="watch.php?id=<?= $note['id'] ?>" class="btn btn-primary" style="text-decoration: none;">Powrót do lekcji</a>
                </div>
            <?php else: ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <div style="background: rgba(255, 255, 255, 0.04); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--card-border);">
                    <span style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Zgłaszany materiał</span>
                    <strong style="font-size: 1rem; color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($note['title']) ?></strong>
                </div>

                <form action="file_report.php" method="POST">
                    <?= SecurityEnterprise::csrfField() ?>
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="reason" style="display: block; margin-bottom: 8px; font-size: 0.88rem; font-weight: 500; color: var(--text-primary);">Powód zgłoszenia</label>
                        <textarea name="reason" id="reason" class="form-control" rows="5" style="width: 100%; min-height: 120px; resize: vertical;" placeholder="Np. treść zawiera błędy merytoryczne, narusza prawa autorskie, zawiera nieodpowiednie słownictwo..." required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 25px;">
                        <a href="watch.php?id=<?= $note['id'] ?>" class="btn btn-secondary" style="flex: 1; text-decoration: none;">Anuluj</a>
                        <button type="submit" class="btn btn-danger" style="flex: 1; background: var(--danger-color); color: #fff;">Wyślij zgłoszenie</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
