<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityEnterprise::requireCsrf();
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->execute([$user['id']]);

                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], hash('sha256', $token), $expires]);

                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
                $resetPath = ($basePath === '' || $basePath === '.') ? '/reset_password.php' : $basePath . '/reset_password.php';
                $resetLink = $protocol . '://' . $host . $resetPath . '?token=' . urlencode($token);

                require_once 'includes/smtp_mailer.php';
                $mailer = SmtpMailer::getInstance();
                $mailer->sendPasswordResetEmail($email, $resetLink, 3600);

                $message = 'Wysłaliśmy instrukcje resetowania hasła na adres ' . htmlspecialchars($email) . '. Link jest ważny przez 1 godzinę.';
                $messageType = 'success';
            } else {
                $message = 'Jeśli konto istnieje, wysłaliśmy wiadomość z instrukcjami na podany adres e-mail.';
                $messageType = 'info';
            }
        } catch (\PDOException $e) {
            $message = 'Błąd systemu. Spróbuj ponownie później.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Podaj poprawny adres e-mail.';
        $messageType = 'danger';
    }
}
?>
<?php
$pageTitle = 'Reset hasła - Yti School';
require_once 'partials/head.php';
?>
    <div class="auth-wrapper auth-shell">
        <div class="auth-layout">
            <aside class="auth-side">
                <div class="auth-brand">
                    <span class="auth-brand-mark">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                        </svg>
                    </span>
                    yti School
                </div>
                <div>
                    <h2>Spokojnie, odzyskamy dostęp.</h2>
                    <p>Podaj e-mail z konta. Jeśli go znamy, wyślemy link resetowania hasła ważny przez godzinę.</p>
                </div>
                <div class="auth-benefits">
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Token resetu jest haszowany w bazie
                    </div>
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Stare linki są automatycznie czyszczone
                    </div>
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Link działa także z podfolderu aplikacji
                    </div>
                </div>
            </aside>

            <div class="auth-container auth-card">
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($messageType === 'success' || $messageType === 'info')): ?>
                    <div class="success-illustration-wrapper" style="text-align: center; padding: 30px 10px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div class="success-ring-outer" style="margin-bottom: 24px; display: inline-flex; align-items: center; justify-content: center; width: 90px; height: 90px; border-radius: 50%; background: rgba(48, 209, 88, 0.1); border: 2px dashed rgba(48, 209, 88, 0.3); animation: rotateDashed 20s linear infinite;">
                            <div class="success-ring-inner" style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; border-radius: 50%; background: rgba(48, 209, 88, 0.15); border: 2px solid rgba(48, 209, 88, 0.4); box-shadow: 0 0 20px rgba(48, 209, 88, 0.3);">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#30d158" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="checkmark-svg" style="animation: checkPop 0.5s ease-out forwards;">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <h2 style="font-weight: 800; font-size: 1.6rem; margin-bottom: 12px; color: var(--text-primary); letter-spacing: -0.02em;">Sprawdź skrzynkę!</h2>
                        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 28px; font-size: 0.95rem; text-align: center; max-width: 320px;">
                            <?= $message ?>
                        </p>
                        <a href="login.php" class="btn btn-primary" style="display: inline-block; width: 100%; text-align: center;">Wróć do logowania</a>
                    </div>
                <?php else: ?>
                    <div class="auth-header">
                        <h1>Reset hasła</h1>
                        <p>Wpisz adres e-mail, a wyślemy instrukcję resetowania.</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?= SecurityEnterprise::csrfField() ?>
                        <div class="form-group">
                            <label for="email">Adres e-mail</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary">Wyślij link resetowania</button>
                    </form>

                    <div class="auth-footer">
                        Pamiętasz hasło? <a href="login.php">Zaloguj się</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
