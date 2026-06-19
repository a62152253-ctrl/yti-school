<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $type     = $_POST['type'] ?? 'student';
    $class_level = $_POST['class_level'] ?? null;
    $school_name = trim($_POST['school_name'] ?? '');
    $rspo_number = trim($_POST['rspo_number'] ?? '');
    $teacher_card_number = trim($_POST['teacher_card_number'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!SecurityEnterprise::verifyCsrf($csrfToken)) {
        $error = 'Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.';
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = 'Uzupełnij wszystkie wymagane pola.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres e-mail.';
    } elseif (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $error = 'Hasło musi mieć minimum 8 znaków, zawierać dużą literę oraz cyfrę.';
    } elseif ($password !== $confirm) {
        $error = 'Hasła nie są takie same.';
    } elseif ($type === 'teacher') {
        if (!preg_match('/\.edu\.pl$/i', $email)) {
            $error = 'Nauczyciel musi użyć szkolnego adresu e-mail kończącego się na .edu.pl.';
        } elseif (empty($school_name) || empty($rspo_number) || empty($teacher_card_number)) {
            $error = 'Nauczyciel musi podać nazwę szkoły, numer RSPO oraz numer legitymacji.';
        } elseif (!preg_match('/^\d{5,10}$/', $rspo_number)) {
            $error = 'Numer RSPO musi składać się z 5 do 10 cyfr.';
        } elseif (!preg_match('/^[A-Z0-9\-]{5,20}$/i', $teacher_card_number)) {
            $error = 'Numer legitymacji nauczyciela musi mieć od 5 do 20 znaków (litery, cyfry, myślniki).';
        }
        $class_level = null;
    } elseif ($type === 'student_creator') {
        $class_level = null;
    } elseif ($type === 'student' && empty($class_level)) {
        $error = 'Uczeń musi wybrać poziom edukacji.';
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Nazwa użytkownika lub e-mail są już zajęte.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $is_verified = ($type === 'teacher') ? 0 : 1;
                $save_type = ($type === 'student_creator') ? 'teacher' : $type;
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, type, class_level, is_verified, school_name, rspo_number, teacher_card_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashedPassword, $save_type, $class_level, $is_verified, $school_name, $rspo_number, $teacher_card_number])) {
                    $success = 'Konto utworzone. ' . ($type === 'teacher' ? 'Twoje konto oczekuje na weryfikację. ' : '') . 'Możesz teraz <a href="login.php">się zalogować</a>.';
                } else {
                    $error = 'Coś poszło nie tak. Spróbuj ponownie.';
                }
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
    <title>Rejestracja - Yti School</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
</head>
<body class="auth-page">
    <div class="auth-wrapper auth-shell">
        <div class="auth-layout auth-layout-register">
            <aside class="auth-side auth-side-compact">
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
                        <h2>Jedno konto, dwa tryby pracy.</h2>
                        <p>Zarejestruj się bez opłat jako uczeń lub nauczyciel. System sam dostosuje dostępny panel i narzędzia robocze.</p>
                    </div>
                    <div class="onboarding-slide">
                        <h2>Uczeń - pełna baza wiedzy.</h2>
                        <p>Gromadzimy notatki lekcyjne, PDF-y i interaktywne slajdy z podziałem na przedmioty i Twoją docelową klasę.</p>
                    </div>
                    <div class="onboarding-slide">
                        <h2>Nauczyciel - łatwe dzielenie się.</h2>
                        <p>Wrzucaj materiały naukowe bezpośrednio na serwer, twórz playlisty tematyczne i sprawdzaj szczegółowe dane oglądalności.</p>
                    </div>
                    
                    <div class="onboarding-dots">
                        <span class="onboarding-dot active" data-slide="0"></span>
                        <span class="onboarding-dot" data-slide="1"></span>
                        <span class="onboarding-dot" data-slide="2"></span>
                    </div>
                </div>

                <div class="auth-benefits" style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                    <div class="auth-benefit" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(16, 185, 129, 0.15); color: var(--success-color); width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
                        Wymaganie silnego hasła
                    </div>
                    <div class="auth-benefit" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(16, 185, 129, 0.15); color: var(--success-color); width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
                        Poziom klasy dopasowany do Ciebie
                    </div>
                </div>
            </aside>

            <div class="auth-container auth-card">
                <div class="auth-header">
                    <h1>Utwórz konto</h1>
                    <p>Wybierz rolę i przygotuj swój panel.</p>
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
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="register-form">
                    <?= SecurityEnterprise::csrfField() ?>
                    <div class="form-group form-span">
                        <label>Kim jesteś?</label>
                        <div class="role-selector auth-role-selector">
                            <div class="role-option">
                                <input type="radio" name="type" id="role-student" value="student" <?= (!isset($_POST['type']) || $_POST['type'] === 'student') ? 'checked' : '' ?>>
                                <label for="role-student" class="role-label">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 6px; display: block; margin: 0 auto 6px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
                                    <span>Uczeń</span>
                                    <small>Materiały i lekcje</small>
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="type" id="role-student-creator" value="student_creator" <?= (isset($_POST['type']) && $_POST['type'] === 'student_creator') ? 'checked' : '' ?>>
                                <label for="role-student-creator" class="role-label">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 6px; display: block; margin: 0 auto 6px;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    <span>Uczeń-twórca</span>
                                    <small>Notatki i publikacja</small>
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="type" id="role-teacher" value="teacher" <?= (isset($_POST['type']) && $_POST['type'] === 'teacher') ? 'checked' : '' ?>>
                                <label for="role-teacher" class="role-label">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 6px; display: block; margin: 0 auto 6px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    <span>Nauczyciel</span>
                                    <small>Weryfikowany panel</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Nazwa użytkownika</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="np. jan.kowalski" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Adres e-mail</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>

                    <div id="teacher-fields-group" style="display: none;">
                        <div class="form-group">
                            <label for="school_name">Nazwa szkoły</label>
                            <input type="text" name="school_name" id="school_name" class="form-control" placeholder="np. I Liceum Ogólnokształcące w Warszawie" value="<?= isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="rspo_number">Numer RSPO szkoły</label>
                            <input type="text" name="rspo_number" id="rspo_number" class="form-control" placeholder="np. 123456" value="<?= isset($_POST['rspo_number']) ? htmlspecialchars($_POST['rspo_number']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="teacher_card_number">Numer legitymacji nauczyciela</label>
                            <input type="text" name="teacher_card_number" id="teacher_card_number" class="form-control" placeholder="np. PL-78492" value="<?= isset($_POST['teacher_card_number']) ? htmlspecialchars($_POST['teacher_card_number']) : '' ?>">
                        </div>
                    </div>

                    <div class="form-group" id="class-select-group">
                        <label for="class_level">Poziom edukacji / klasa</label>
                        <select name="class_level" id="class_level" class="form-control">
                            <option value="" disabled selected>Wybierz poziom...</option>
                            <optgroup label="Liceum (1-4)">
                                <option value="1_LO" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '1_LO') ? 'selected' : '' ?>>Klasa 1 LO</option>
                                <option value="2_LO" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '2_LO') ? 'selected' : '' ?>>Klasa 2 LO</option>
                                <option value="3_LO" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '3_LO') ? 'selected' : '' ?>>Klasa 3 LO</option>
                                <option value="4_LO" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '4_LO') ? 'selected' : '' ?>>Klasa 4 LO</option>
                            </optgroup>
                            <optgroup label="Studia (1-5)">
                                <option value="1_Studia" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '1_Studia') ? 'selected' : '' ?>>1 rok studiów</option>
                                <option value="2_Studia" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '2_Studia') ? 'selected' : '' ?>>2 rok studiów</option>
                                <option value="3_Studia" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '3_Studia') ? 'selected' : '' ?>>3 rok studiów</option>
                                <option value="4_Studia" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '4_Studia') ? 'selected' : '' ?>>4 rok studiów</option>
                                <option value="5_Studia" <?= (isset($_POST['class_level']) && $_POST['class_level'] === '5_Studia') ? 'selected' : '' ?>>5 rok studiów</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Hasło</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Wpisz bezpieczne hasło" required>
                            <button type="button" class="password-toggle" data-toggle-password="password">Pokaż</button>
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
                        <label for="confirm_password">Potwierdź hasło</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Wpisz hasło ponownie" required>
                            <button type="button" class="password-toggle" data-toggle-password="confirm_password">Pokaż</button>
                        </div>
                    </div>
                    <div class="form-group terms-group">
                        <input type="checkbox" id="agree" required>
                        <label for="agree">Akceptuję <a href="#">regulamin platformy</a></label>
                    </div>
                    <button type="submit" class="btn btn-primary">Stwórz darmowe konto</button>
                </form>

                <div class="auth-footer">
                    Masz już konto? <a href="login.php">Zaloguj się</a>
                </div>
            </div>
        </div>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        function toggleRoleFields(role) {
            const classGroup = document.getElementById('class-select-group');
            const classSelect = document.getElementById('class_level');
            const emailInput = document.getElementById('email');
            const teacherGroup = document.getElementById('teacher-fields-group');
            const schoolName = document.getElementById('school_name');
            const rspoNumber = document.getElementById('rspo_number');
            const teacherCard = document.getElementById('teacher_card_number');

            if (role === 'teacher') {
                classGroup.style.display = 'none';
                classGroup.style.opacity = '0';
                classGroup.style.animation = 'none';
                classSelect.removeAttribute('required');
                emailInput.placeholder = 'username@school.edu.pl';
                
                teacherGroup.style.display = 'block';
                teacherGroup.style.animation = 'fadeInUp 0.3s ease-out';
                schoolName.setAttribute('required', 'required');
                rspoNumber.setAttribute('required', 'required');
                teacherCard.setAttribute('required', 'required');
            } else if (role === 'student_creator') {
                classGroup.style.display = 'none';
                classGroup.style.opacity = '0';
                classGroup.style.animation = 'none';
                classSelect.removeAttribute('required');
                emailInput.placeholder = 'name@example.com';

                teacherGroup.style.display = 'none';
                teacherGroup.style.opacity = '0';
                teacherGroup.style.animation = 'none';
                schoolName.removeAttribute('required');
                rspoNumber.removeAttribute('required');
                teacherCard.removeAttribute('required');
            } else {
                classGroup.style.display = 'block';
                classGroup.style.animation = 'fadeInUp 0.3s ease-out';
                classSelect.setAttribute('required', 'required');
                emailInput.placeholder = 'name@example.com';

                teacherGroup.style.display = 'none';
                teacherGroup.style.opacity = '0';
                teacherGroup.style.animation = 'none';
                schoolName.removeAttribute('required');
                rspoNumber.removeAttribute('required');
                teacherCard.removeAttribute('required');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const roleInputs = document.querySelectorAll('input[name="type"]');
            roleInputs.forEach(input => {
                input.addEventListener('change', () => {
                    toggleRoleFields(input.value);
                    input.parentElement.querySelector('.role-label').style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        input.parentElement.querySelector('.role-label').style.transform = 'scale(1)';
                    }, 150);
                });
            });
            const activeRole = document.querySelector('input[name="type"]:checked');
            toggleRoleFields(activeRole ? activeRole.value : 'student');

            document.querySelectorAll('[data-toggle-password]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById(btn.getAttribute('data-toggle-password'));
                    if (!target) return;
                    const visible = target.type === 'password';
                    target.type = visible ? 'text' : 'password';
                    btn.textContent = visible ? 'Ukryj' : 'Pokaż';
                    btn.style.transition = 'all 0.15s ease';
                    btn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        btn.style.transform = 'scale(1)';
                    }, 100);
                });
            });

            const password = document.getElementById('password');
            const strength = document.getElementById('passwordStrength');
            const reqLength = document.getElementById('req-length');
            const reqUpper = document.getElementById('req-upper');
            const reqNumber = document.getElementById('req-number');

            if (password && strength && reqLength && reqUpper && reqNumber) {
                password.addEventListener('input', () => {
                    const val = password.value;
                    
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
