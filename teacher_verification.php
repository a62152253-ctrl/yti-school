<?php
require_once 'db.php';
requireLogin();

if (isStudent()) {
    redirect('student_dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Check database to see if verified
try {
    $stmt = $pdo->prepare("SELECT is_verified, school_name, rspo_number, teacher_card_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('logout.php');
    }
    
    if ($user['is_verified'] == 1) {
        $_SESSION['is_verified'] = 1;
        redirect('dashboard.php');
    }
} catch (\PDOException $e) {
    die("Błąd połączenia: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_code') {
        $code = trim($_POST['verification_code'] ?? '');
        try {
            $stmtCode = $pdo->prepare("SELECT school_name FROM school_codes WHERE code = ?");
            $stmtCode->execute([$code]);
            $school = $stmtCode->fetch();
            
            if ($school) {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, school_name = ? WHERE id = ?");
                $stmt->execute([$school['school_name'], $user_id]);
                $_SESSION['is_verified'] = 1;
                redirect('dashboard.php?msg=verified');
            } else {
                $error = 'Nieprawidłowy kod weryfikacyjny dla Twojej placówki oświatowej.';
            }
        } catch (\PDOException $e) {
            $error = 'Wystąpił błąd podczas zapisywania weryfikacji.';
        }
    } elseif ($action === 'upload_id') {
        if (!isset($_FILES['id_document']) || $_FILES['id_document']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Wybierz plik dokumentu do przesłania.';
        } else {
            $file = $_FILES['id_document'];
            [$validUpload, $uploadMeta] = SecurityEnterprise::validateUpload($file, ALLOWED_UPLOAD_MIME_TYPES, MAX_FILE_UPLOAD_SIZE);
            if ($validUpload !== true) {
                $error = is_string($uploadMeta) ? $uploadMeta : 'Nieprawidłowy plik dokumentu.';
            } else {
                /** @var array{name:string,mime:string,size:int,tmp_name:string} $validated */
                $validated = $uploadMeta;
                $extension = SecurityEnterprise::safeFileExtension($validated['name']);
                if ($extension === '') {
                    $error = 'Nieobsługiwany format pliku.';
                } else {
                    $filename = sprintf('id_%s.%s', bin2hex(random_bytes(8)), $extension);
                    $targetPath = SecurityEnterprise::safePathJoin(UPLOAD_DIR, $filename);
                    $dbPath = 'storage/private/files/' . $filename;
                    
                    if (move_uploaded_file($validated['tmp_name'], $targetPath)) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET is_verified = 2, verification_document = ? WHERE id = ?");
                            $stmt->execute([$dbPath, $user_id]);
                            $_SESSION['is_verified'] = 2;
                            // Refresh page to show pending status
                            redirect('teacher_verification.php?msg=pending');
                        } catch (\PDOException $e) {
                            @unlink($targetPath);
                            $error = 'Błąd bazy danych podczas zapisywania dokumentu.';
                        }
                    } else {
                        $error = 'Błąd zapisu pliku dokumentu na serwerze.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weryfikacja Nauczyciela - Yti School</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
    <style>
        .verification-container {
            max-width: 650px;
            margin: 60px auto;
            padding: 0 20px;
        }
        .verification-card {
            background: rgba(30, 30, 40, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .verification-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .verification-header h1 {
            font-size: 2rem;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .verification-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .info-badge {
            background: rgba(10, 132, 255, 0.1);
            border: 1px solid rgba(10, 132, 255, 0.2);
            color: #0a84ff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .school-details {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .school-details h3 {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.92rem;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-secondary);
        }
        .detail-value {
            font-weight: 600;
            color: #fff;
        }
        .section-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 30px 0;
            position: relative;
        }
        .section-divider-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgb(25, 25, 35);
            padding: 0 15px;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 0.1em;
        }
        .upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.01);
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #30d158;
            background: rgba(48, 209, 88, 0.05);
        }
        .upload-icon {
            font-size: 2.2rem;
            margin-bottom: 12px;
            display: block;
        }
        .loader-spinner {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="auth-page">
    <div class="verification-container">
        <div class="verification-card">
            <div class="verification-header">
                <h1>Weryfikacja profilu</h1>
                <p>Aby publikować lekcje i playlisty w serwisie Yti School, musimy potwierdzić, że jesteś czynnym nauczycielem w swojej szkole.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="info-badge">
                <span style="font-size: 1.2rem;">ℹ</span>
                <span>Weryfikacja zapewnia bezpieczeństwo oraz zapobiega podszywaniu się pod grono pedagogiczne.</span>
            </div>

            <div class="school-details">
                <h3>Szczegóły Twojej szkoły</h3>
                <div class="detail-row">
                    <span class="detail-label">Szkoła</span>
                    <span class="detail-value"><?= htmlspecialchars($user['school_name'] ?? 'Brak danych') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Numer RSPO</span>
                    <span class="detail-value"><?= htmlspecialchars($user['rspo_number'] ?? 'Brak danych') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Numer legitymacji</span>
                    <span class="detail-value"><?= htmlspecialchars($user['teacher_card_number'] ?? 'Brak danych') ?></span>
                </div>
            </div>

            <?php if ($user['is_verified'] == 2): ?>
                <div class="alert alert-warning" style="margin-bottom: 20px; background: rgba(255, 159, 10, 0.16); color: #ff9f0a; border: 1px solid rgba(255, 159, 10, 0.2); padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.2rem;">⏳</span>
                    <span>Twój dokument został przesłany i oczekuje na weryfikację przez administratora.</span>
                </div>
            <?php else: ?>
                <form action="teacher_verification.php" method="POST" style="margin-bottom: 0;">
                    <?= SecurityEnterprise::csrfField() ?>
                    <input type="hidden" name="action" value="verify_code">
                    
                    <div class="form-group">
                        <label for="verification_code">Kod aktywacyjny placówki</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="verification_code" id="verification_code" class="form-control" placeholder="Wpisz kod aktywacyjny szkoły..." required style="margin-bottom: 0;">
                            <button type="submit" class="btn btn-primary" style="margin-bottom: 0; white-space: nowrap;">Weryfikuj kod</button>
                        </div>
                        <small style="color: var(--text-secondary); margin-top: 6px; display: block;">Dla celów testowych/prezentacyjnych wpisz kod: <strong style="color: #30d158; cursor: pointer;" onclick="document.getElementById('verification_code').value='SCHOOL123'">SCHOOL123</strong></small>
                    </div>
                </form>

                <div class="section-divider">
                    <span class="section-divider-text">lub</span>
                </div>

                <form action="teacher_verification.php" method="POST" id="uploadForm" enctype="multipart/form-data">
                    <?= SecurityEnterprise::csrfField() ?>
                    <input type="hidden" name="action" value="upload_id">
                    <input type="file" id="id_document" name="id_document" accept="image/*,application/pdf" style="display: none;" onchange="handleFileSelected()">

                    <div class="upload-area" id="dropArea" onclick="document.getElementById('id_document').click()">
                        <div class="loader-spinner" id="uploadSpinner"></div>
                        <span class="upload-icon" id="uploadIcon">📄</span>
                        <p id="uploadText" style="margin-bottom: 4px; font-weight: 600; color: #fff;">Prześlij zdjęcie legitymacji nauczycielskiej</p>
                        <small style="color: var(--text-secondary);">Akceptowane pliki: PNG, JPG, PDF (maks. 5MB)</small>
                    </div>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="logout.php" style="color: var(--text-secondary); font-size: 0.9rem; text-decoration: none; hover:underline;">Wyloguj się</a>
            </div>
        </div>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        const dropArea = document.getElementById('dropArea');
        
        if (dropArea) {
            dropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropArea.classList.add('dragover');
            });
            dropArea.addEventListener('dragleave', () => {
                dropArea.classList.remove('dragover');
            });
            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                dropArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length) {
                    document.getElementById('id_document').files = files;
                    handleFileSelected();
                }
            });
        }

        function handleFileSelected() {
            const input = document.getElementById('id_document');
            if (input.files.length === 0) return;
            
            // Show fake upload loading animation
            document.getElementById('uploadIcon').style.display = 'none';
            document.getElementById('uploadSpinner').style.display = 'block';
            document.getElementById('uploadText').textContent = 'Weryfikowanie dokumentu...';
            
            setTimeout(() => {
                document.getElementById('uploadForm').submit();
            }, 1800);
        }
    </script>
</body>
</html>
