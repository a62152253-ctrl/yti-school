<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect(isTeacher() ? 'dashboard.php' : 'student_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['login_identity'] ?? '');
    $password        = $_POST['password'] ?? '';
    $csrfToken       = $_POST['csrf_token'] ?? '';

    if (!SecurityEnterprise::verifyCsrf($csrfToken)) {
        $error = 'Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.';
    } elseif (empty($usernameOrEmail) || empty($password)) {
        $error = 'Podaj login/e-mail oraz hasło.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['user_type']   = $user['type'];
                $_SESSION['class_level'] = $user['class_level'];

                redirect(isTeacher() ? 'dashboard.php' : 'student_dashboard.php');
            } else {
                $error = 'Nieprawidłowy login/e-mail lub hasło.';
            }
        } catch (\PDOException $e) {
            $error = 'Błąd bazy danych. Spróbuj ponownie później.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Yti School</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
</head>
<body class="auth-page">
    <div class="auth-wrapper auth-shell">
        <div class="auth-layout">
            <aside class="auth-side">
                <div class="auth-brand" style="margin-bottom: 20px;">
                    <span class="auth-brand-mark" style="background: var(--accent-gradient); color: #fff; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; margin-right: 8px; vertical-align: middle;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                        </svg>
                    </span>
                    <span style="font-weight: 800; font-size: 1.3rem; letter-spacing: -0.03em; vertical-align: middle;">yti School</span>
                </div>
                
                <div class="onboarding-carousel">
                    <div class="onboarding-slide active">
                        <h2>Wszystkie lekcje w jednym miejscu.</h2>
                        <p>Zaloguj się, aby uzyskać natychmiastowy dostęp do swoich ulubionych notatek, prezentacji oraz historii nauki bez szukania po folderach.</p>
                    </div>
                    <div class="onboarding-slide">
                        <h2>Ucz się interaktywnie.</h2>
                        <p>Przeglądaj slajdy lekcji w wygodnym odtwarzaczu, kontroluj tempo automatycznego odtwarzania i dostosowuj motyw kolorystyczny.</p>
                    </div>
                    <div class="onboarding-slide">
                        <h2>Twój osobisty notatnik.</h2>
                        <p>Rób notatki w czasie rzeczywistym podczas przeglądania materiałów. Zapiszą się one automatycznie i możesz je pobrać na dysk w każdej chwili.</p>
                    </div>
                    <div class="onboarding-slide">
                        <h2>Grywalizacja i statystyki.</h2>
                        <p>Śledź swoją codzienną serię dni nauki (Study Streak), zdobywaj prestiżowe odznaki za zaangażowanie i monitoruj swoje postępy.</p>
                    </div>
                    
                    <div class="onboarding-dots">
                        <span class="onboarding-dot active" data-slide="0"></span>
                        <span class="onboarding-dot" data-slide="1"></span>
                        <span class="onboarding-dot" data-slide="2"></span>
                        <span class="onboarding-dot" data-slide="3"></span>
                    </div>
                </div>

                <div class="auth-benefits" style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                    <div class="auth-benefit" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(16, 185, 129, 0.15); color: var(--success-color); width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
                        Bezpieczeństwo z tokenami CSRF i CSP
                    </div>
                    <div class="auth-benefit" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(16, 185, 129, 0.15); color: var(--success-color); width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
                        Weryfikowane konta nauczycielskie (.edu.pl)
                    </div>
                </div>
            </aside>

            <div class="auth-container auth-card">
                <div class="auth-header">
                    <h1>Witaj ponownie</h1>
                    <p>Zaloguj się do swojego centrum nauki.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <?= SecurityEnterprise::csrfField() ?>
                    <div class="form-group">
                        <label for="login_identity">Login lub e-mail</label>
                        <input type="text" name="login_identity" id="login_identity" class="form-control" placeholder="np. jan.kowalski" value="<?= isset($_POST['login_identity']) ? htmlspecialchars($_POST['login_identity']) : '' ?>" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Hasło</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Wpisz hasło" required>
                            <button type="button" id="togglePwd" class="password-toggle">Pokaż</button>
                        </div>
                    </div>
                    <div class="auth-meta-row">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; display: inline-block; vertical-align: middle;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Chroniona sesja
                        </span>
                        <a href="forgot_password.php" class="auth-link">Nie pamiętasz hasła?</a>
                    </div>
                    <button type="submit" class="btn btn-primary">Zaloguj bezpiecznie</button>
                </form>

                <div class="auth-footer">
                    Nie masz konta? <a href="register.php">Zarejestruj się</a>
                </div>
            </div>
        </div>
    </div>
    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle
            var toggleBtn = document.getElementById('togglePwd');
            var pwdField = document.getElementById('password');
            if (toggleBtn && pwdField) {
                toggleBtn.addEventListener('click', function() {
                    var isPwd = pwdField.type === 'password';
                    pwdField.type = isPwd ? 'text' : 'password';
                    this.textContent = isPwd ? 'Ukryj' : 'Pokaż';
                });
            }

            // Onboarding Carousel
            var slides = document.querySelectorAll('.onboarding-slide');
            var dots = document.querySelectorAll('.onboarding-dot');
            var activeIdx = 0;
            var interval;

            function showSlide(idx) {
                slides.forEach(function(s) { s.classList.remove('active'); });
                dots.forEach(function(d) { d.classList.remove('active'); });
                slides[idx].classList.add('active');
                dots[idx].classList.add('active');
                activeIdx = idx;
            }

            function startTimer() {
                interval = setInterval(function() {
                    var next = (activeIdx + 1) % slides.length;
                    showSlide(next);
                }, 4000);
            }

            dots.forEach(function(dot) {
                dot.addEventListener('click', function() {
                    clearInterval(interval);
                    var idx = parseInt(this.getAttribute('data-slide'));
                    showSlide(idx);
                    startTimer();
                });
            });

            if (slides.length > 0) {
                startTimer();
            }
        });
    </script>
</body>
</html>
