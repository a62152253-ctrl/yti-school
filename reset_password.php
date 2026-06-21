<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$tokenValid = false;
$user = null;

if (empty($token)) {
    $error = 'Brak tokenu resetowania. Zażądaj nowego linka.';
} else {
    try {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("
            SELECT pr.user_id, u.email, pr.expires_at
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.expires_at > datetime('now')
        ");
        $stmt->execute([$tokenHash]);
        $resetRecord = $stmt->fetch();
        
        if ($resetRecord) {
            $tokenValid = true;
            $user = $resetRecord;
        } else {
            $error = 'Link resetowania wygasł lub jest nieprawidłowy. Zażądaj nowego linka.';
        }
    } catch (\PDOException $e) {
        $error = 'Błąd systemu.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    SecurityEnterprise::requireCsrf();
    
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password) || empty($passwordConfirm)) {
        $error = 'Wszystkie pola są wymagane.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Hasło musi mieć minimum 8 znaków, zawierać dużą literę oraz cyfrę.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Hasła nie pasują do siebie.';
    } else {
        try {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['user_id']]);
            
            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$tokenHash]);
            
            $success = 'Hasło zostało zmienione. Możesz się teraz zalogować.';
            $tokenValid = false;
        } catch (\PDOException $e) {
            $error = 'Błąd podczas zmiany hasła.';
        }
    }
}
?>
<?php
$pageTitle = 'Ustaw Nowe Hasło - Yti School';
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
                    <h2>Zabezpiecz swoje konto na nowo.</h2>
                    <p>Wprowadź nowe, silne hasło. Po zmianie będziesz mógł od razu zalogować się do platformy.</p>
                </div>
                <div class="auth-benefits">
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Hasło szyfrowane metodą bcrypt
                    </div>
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Sesja wygasa po zmianie hasła
                    </div>
                    <div class="auth-benefit">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </span>
                        Ochrona przed brute-force
                    </div>
                </div>
            </aside>

            <div class="auth-container auth-card">
                <div class="auth-header">
                    <h1>🔑 Nowe Hasło</h1>
                    <p>Wpisz nowe hasło dla swojego konta.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="login.php" class="btn btn-primary">Przejdź do Logowania</a>
                    </div>
                <?php elseif ($tokenValid): ?>
                    <form method="POST">
                        <?= SecurityEnterprise::csrfField() ?>
                        
                        <div class="form-group">
                            <label for="password">Nowe Hasło</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Wpisz bezpieczne hasło" required>
                                <button type="button" class="password-toggle" data-target="password">Pokaż</button>
                            </div>
                            <div class="password-strength" id="passwordStrength" data-score="0"><span></span><span></span><span></span></div>
                            <div class="password-hint-wrapper" id="passwordRequirements">
                                <div class="password-hint-title">Wymagania hasła:</div>
                                <div class="password-hint-list">
                                    <div class="password-hint-item" id="req-length">Minimum 8 znaków</div>
                                    <div class="password-hint-item" id="req-upper">Przynajmniej jedna duża litera</div>
                                    <div class="password-hint-item" id="req-number">Przynajmniej jedna cyfra</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Powtórz Hasło</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Powtórz hasło" required>
                                <button type="button" class="password-toggle" data-target="password_confirm">Pokaż</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Zmień Hasło</button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <a href="login.php">← Wróć do Logowania</a>
                </div>
            </div>
        </div>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target') || this.getAttribute('data-toggle-password');
                const input = document.getElementById(targetId);
                if (!input) return;
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                this.textContent = visible ? 'Ukryj' : 'Pokaż';
            });
        });

        document.getElementById('password')?.addEventListener('input', function() {
            const val = this.value;
            const reqLength = document.getElementById('req-length');
            const reqUpper = document.getElementById('req-upper');
            const reqNumber = document.getElementById('req-number');
            const strength = document.getElementById('passwordStrength');

            if (reqLength && reqUpper && reqNumber && strength) {
                const isLengthValid = val.length >= 8;
                reqLength.classList.toggle('valid', isLengthValid);
                
                const isUpperValid = /[A-Z]/.test(val);
                reqUpper.classList.toggle('valid', isUpperValid);
                
                const isNumberValid = /[0-9]/.test(val);
                reqNumber.classList.toggle('valid', isNumberValid);
                
                let score = 0;
                if (isLengthValid) score++;
                if (isUpperValid) score++;
                if (isNumberValid) score++;
                
                strength.dataset.score = String(score);
            }
        });
    </script>
</body>
</html>
