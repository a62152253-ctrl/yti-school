<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$successMsg = '';

try {
    $stmt = $pdo->prepare('SELECT username, email, type, class_level FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect('login.php');
    }
} catch (\PDOException $e) {
    die('Błąd połączenia: ' . $e->getMessage());
}

// Calculate study streak from history
$streak = 0;
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT date(watched_at) as watch_date 
        FROM history 
        WHERE user_id = ? 
        ORDER BY watch_date DESC
    ");
    $stmt->execute([$user_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($dates)) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($dates[0] === $today || $dates[0] === $yesterday) {
            $streak = 1;
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $curr = strtotime($dates[$i]);
                $next = strtotime($dates[$i + 1]);
                $diff = ($curr - $next) / (86400);
                if ($diff == 1) {
                    $streak++;
                } else {
                    break;
                }
            }
        }
    }
} catch (\PDOException $e) {
    $streak = 0;
}

// Calculate achievement badges
$badgeExplorer = false;
$badgeCollector = false;
$badgeStudent = false;
$badgeSponsor = false;

try {
    // 1. Młody Odkrywca: subbed to 1+ teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $badgeExplorer = $stmt->fetchColumn() >= 1;

    // 2. Kolekcjoner: 3+ my_lessons bookmarks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM my_lessons WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $badgeCollector = $stmt->fetchColumn() >= 3;

    // 3. Pilny Uczeń: 5+ history records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $badgeStudent = $stmt->fetchColumn() >= 5;

    // 4. Wspierający: 1+ premium purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $badgeSponsor = $stmt->fetchColumn() >= 1;
} catch (\PDOException $e) {}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityEnterprise::requireCsrf();
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newUsername) || empty($newEmail)) {
        $error = 'Nazwa użytkownika i email są wymagane.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres email.';
    } elseif (!empty($newPassword) && strlen($newPassword) < 6) {
        $error = 'Hasło musi mieć przynajmniej 6 znaków.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Hasła nie są takie same.';
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
            $stmt->execute([$newUsername, $newEmail, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Nazwa użytkownika lub email są już zajęte.';
            } else {
                $updates = ['username' => $newUsername, 'email' => $newEmail];
                if (!empty($newPassword)) {
                    $updates['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                $fields = [];
                $values = [];
                foreach ($updates as $field => $value) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
                $values[] = $user_id;
                $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
                $stmt->execute($values);

                $_SESSION['username'] = $newUsername;
                $_SESSION['email'] = $newEmail;
                $successMsg = 'Dane profilu zostały zaktualizowane.';
                $user['username'] = $newUsername;
                $user['email'] = $newEmail;
            }
        } catch (\PDOException $e) {
            $error = 'Błąd zapisu: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Mój Profil - Yti School';
$activePage = 'profile.php';
$hideSearch = true;
require APP_ROOT . '/partials/head.php';
require APP_ROOT . '/partials/topbar.php';
require APP_ROOT . '/partials/sidebar.php';
?>
<main class="main-content">
    <div class="glass-card profile-card" style="max-width: 760px; margin: 24px auto; padding: 28px;">
        <h2>Ustawienia konta</h2>
        <p style="color: var(--text-secondary); margin-top: 4px;">Zaktualizuj dane użytkownika oraz hasło.</p>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-top: 18px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success" style="margin-top: 18px;"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <!-- Gamification Widgets (Only for Students) -->
        <?php if ($user['type'] === 'student'): ?>
            <div class="streak-badge-row" style="margin-top: 24px;">
                <!-- Streak Card -->
                <div class="streak-card">
                    <div class="streak-card-icon">🔥</div>
                    <div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: #fff; line-height: 1.2;"><?= $streak ?> dni z rzędu</div>
                        <div style="font-size: 0.8rem; color: #f59e0b; font-weight: 500;">Seria Dni Nauki (Study Streak)</div>
                    </div>
                </div>
                
                <!-- Achievements Panel -->
                <div class="glass-card" style="padding: 16px;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 8px;">Odznaki zaangażowania</div>
                    <div class="badges-container">
                        <div class="badge-item <?= $badgeExplorer ? 'active' : '' ?>" title="Subskrybujesz nauczyciela">
                            <span class="badge-item-icon">🧭</span>
                            <span>Odkrywca</span>
                        </div>
                        <div class="badge-item <?= $badgeCollector ? 'active' : '' ?>" title="Zapisano przynajmniej 3 lekcje">
                            <span class="badge-item-icon">📂</span>
                            <span>Kolekcjoner</span>
                        </div>
                        <div class="badge-item <?= $badgeStudent ? 'active' : '' ?>" title="Ukończono przynajmniej 5 lekcji">
                            <span class="badge-item-icon">✍️</span>
                            <span>Pilny Uczeń</span>
                        </div>
                        <div class="badge-item <?= $badgeSponsor ? 'active' : '' ?>" title="Kupiono materiał premium">
                            <span class="badge-item-icon">💎</span>
                            <span>Wspierający</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST" style="margin-top: 20px;">
            <?= SecurityEnterprise::csrfField() ?>
            <div class="form-group">
                <label for="username">Nazwa użytkownika</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Rola</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['type'] === 'teacher' ? 'Nauczyciel' : 'Student') ?>" disabled>
            </div>
            <?php if ($user['type'] === 'student'): ?>
                <div class="form-group">
                    <label>Klasa</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['class_level'] ?? '') ?>" disabled>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="password">Nowe hasło</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Pozostaw puste aby nie zmieniać">
            </div>
            <div class="form-group">
                <label for="confirm_password">Potwierdź hasło</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Powtórz nowe hasło">
            </div>
            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
        </form>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
