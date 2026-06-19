<?php
require_once '../db.php';
requireLogin();

if (!isTeacher()) {
    redirect('../student_dashboard.php');
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
    
    if (empty($username) || empty($email)) {
        $errorMsg = "Nazwa oraz Email są wymagane.";
    } else {
        try {
            // Check if username/email already exists on another user
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmtCheck->execute([$username, $email, $teacher_id]);
            if ($stmtCheck->fetch()) {
                $errorMsg = "Nazwa użytkownika lub Email są już zajęte.";
            } else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmtUpd->execute([$username, $email, $hashed, $teacher_id]);
                } else {
                    $stmtUpd = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmtUpd->execute([$username, $email, $teacher_id]);
                }
                
                // Update session
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                $successMsg = "Profil został pomyślnie zaktualizowany!";
                
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$teacher_id]);
                $teacher = $stmt->fetch();
            }
        } catch (\PDOException $e) {
            $errorMsg = "Błąd zapisu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dostosuj Kanał - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
</head>
<body>
    <!-- Header Topbar like YouTube -->
    <header class="yt-header">
        <div class="yt-header-left">
            <a href="../dashboard.php" class="logo-section">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                </svg>
                <span class="yt-logo-text">yti School</span>
            </a>
        </div>
        <div class="yt-header-right">
            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['username']) ?>">
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav style="width: 100%;">
                <ul class="nav-links">
                    <li>
                        <a href="../dashboard.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Panel Dydaktyczny
                        </a>
                    </li>
                    <li class="active">
                        <a href="channel_manage.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Mój Kanał
                        </a>
                    </li>
                    <li>
                        <a href="../report.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                            Zgłoszenia uczniów
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="user-avatar">
                            <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            <span class="user-role-badge">Nauczyciel</span>
                        </div>
                    </div>
                    <a href="../logout.php" class="logout-btn">Wyloguj się</a>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="content-header">
                <h2>Dostosuj swój Kanał Nauczyciela</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Aktualizuj swoje dane profilowe i informacje kontaktowe</p>
            </header>

            <div class="glass-card" style="padding: 30px; max-width: 600px;">
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
                <?php endif; ?>

                <form action="channel_edit.php" method="POST">
                    <div class="form-group">
                        <label for="username">Nazwa Kanału (Nazwa Użytkownika)</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($teacher['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Adres E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Nowe Hasło (Zostaw puste, aby nie zmieniać)</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Wpisz nowe hasło...">
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 25px;">
                        <button type="submit" class="btn btn-primary" style="width: auto; padding: 12px 30px; border-radius: 20px;">Zapisz zmiany</button>
                        <a href="channel_manage.php" class="btn btn-secondary" style="width: auto; padding: 12px 30px; border-radius: 20px; text-decoration: none;">Powrót</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
