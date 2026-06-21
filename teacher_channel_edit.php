<?php
require_once 'db.php';
requireLogin();

if (!isTeacher()) {
    redirect('student_dashboard.php');
}

$teacher_id = $_SESSION['user_id'];
$errorMsg = '';
$successMsg = '';

// Fetch teacher details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!SecurityEnterprise::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = "Błąd CSRF: nieprawidłowe żądanie.";
    } elseif (empty($username) || empty($email)) {
        $errorMsg = "Nazwa oraz Email są wymagane.";
    } else {
        $username = SecurityEnterprise::normalizeText($username);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $errorMsg = "Nieprawidłowy adres email.";
        } else {
            try {
                // Check if username/email already exists on another user
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmtCheck->execute([$username, $email, $teacher_id]);
                if ($stmtCheck->fetch()) {
                    $errorMsg = "Nazwa użytkownika lub Email są już zajęte.";
                } else {
                    if (!empty($password)) {
                        if (strlen($password) < 8) {
                            $errorMsg = "Nowe hasło musi mieć co najmniej 8 znaków.";
                        } else {
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $stmtUpd = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                            $stmtUpd->execute([$username, $email, $hashed, $teacher_id]);
                        }
                    } else {
                        $stmtUpd = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                        $stmtUpd->execute([$username, $email, $teacher_id]);
                    }
                    
                    if (empty($errorMsg)) {
                        // Update session
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        
                        $successMsg = "Profil został pomyślnie zaktualizowany!";
                        
                        // Refresh data
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$teacher_id]);
                        $teacher = $stmt->fetch();
                    }
                }
            } catch (\PDOException $e) {
                $errorMsg = "Błąd zapisu: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Dostosuj Kanał - Yti School';
$activePage = 'teacher_channel_manager.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
<div class="app-container">
    <?php require_once 'partials/sidebar.php'; ?>

    <!-- Main Workspace -->
    <main class="main-content">
        <header class="content-header" style="margin-bottom: 24px;">
            <h2 style="font-size: 1.6rem; font-weight: 700; color: #fff; margin: 0;">Dostosuj swój Kanał Nauczyciela</h2>
            <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Aktualizuj swoje dane profilowe i informacje kontaktowe</p>
        </header>

        <div class="glass-card" style="padding: 30px; max-width: 600px; background: #121212; border: 1px solid var(--card-border); border-radius: 8px;">
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>

            <form action="teacher_channel_edit.php" method="POST">
                <?= SecurityEnterprise::csrfField() ?>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="username" style="display: block; margin-bottom: 8px; font-size: 0.88rem; font-weight: 500; color: var(--text-primary);">Nazwa Kanału (Nazwa Użytkownika)</label>
                    <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($teacher['username'] ?? '') ?>" required style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--card-border); background: var(--yt-input-bg); color: #fff;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="email" style="display: block; margin-bottom: 8px; font-size: 0.88rem; font-weight: 500; color: var(--text-primary);">Adres E-mail</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" required style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--card-border); background: var(--yt-input-bg); color: #fff;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" style="display: block; margin-bottom: 8px; font-size: 0.88rem; font-weight: 500; color: var(--text-primary);">Nowe Hasło (Zostaw puste, aby nie zmieniać)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Wpisz nowe hasło..." style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--card-border); background: var(--yt-input-bg); color: #fff;">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <button type="submit" class="btn btn-primary" style="width: auto; padding: 12px 30px; border-radius: 20px; font-weight: 700;">Zapisz zmiany</button>
                    <a href="teacher_channel_manager.php" class="btn btn-secondary" style="width: auto; padding: 12px 30px; border-radius: 20px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;">Powrót</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
