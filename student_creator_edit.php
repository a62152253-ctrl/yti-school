<?php
require_once 'db.php';
requireLogin();

if (!isStudentCreator()) {
    redirect('student_dashboard.php');
}

$id = SecurityEnterprise::getInt('id', 0);
if ($id <= 0) {
    redirect('student_creator_dashboard.php');
}

// Fetch note and verify ownership
try {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $note = $stmt->fetch();
    if (!$note) {
        redirect('student_creator_dashboard.php');
    }
} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
    header('Content-Type: application/json; charset=utf-8');
    SecurityEnterprise::requirePost();
    SecurityEnterprise::assertSameOrigin();
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

    $title       = SecurityEnterprise::normalizeText($_POST['title'] ?? '');
    $description = SecurityEnterprise::normalizeText($_POST['description'] ?? '');
    $subject     = SecurityEnterprise::normalizeText($_POST['subject'] ?? '');
    $class_level = SecurityEnterprise::normalizeText($_POST['class_level'] ?? '');
    $tags        = SecurityEnterprise::normalizeText($_POST['tags'] ?? '');
    $access_type = 'free';
    $premium_price = 0.0;

    if (empty($title) || empty($subject) || empty($class_level)) {
        echo json_encode(['success' => false, 'message' => 'Proszę wypełnić wymagane pola (Tytuł, Przedmiot, Klasa).']);
        exit;
    }

    $dbPath = $note['filepath'];
    $file_type = $note['file_type'];
    $newFileUploaded = false;
    $targetPath = '';

    if (isset($_FILES['noteFile']) && $_FILES['noteFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['noteFile'];
        [$validUpload, $uploadMeta] = SecurityEnterprise::validateUpload($file, ALLOWED_UPLOAD_MIME_TYPES, MAX_FILE_UPLOAD_SIZE);
        if ($validUpload !== true) {
            $message = is_string($uploadMeta) ? $uploadMeta : 'Nieprawidłowe przesłanie pliku.';
            echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        /** @var array{name:string,mime:string,size:int,tmp_name:string} $validated */
        $validated = $uploadMeta;
        $extension = SecurityEnterprise::safeFileExtension($validated['name']);
        if ($extension === '') {
            echo json_encode(['success' => false, 'message' => 'Nieobsługiwany format pliku.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $filename = sprintf('note_%s.%s', bin2hex(random_bytes(8)), $extension);
        $targetPath = SecurityEnterprise::safePathJoin(UPLOAD_DIR, $filename);
        $dbPath = 'storage/private/files/' . $filename;
        $file_type = (str_starts_with($validated['mime'], 'image/')) ? 'image' : 'pdf';
        
        if (!move_uploaded_file($validated['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Błąd zapisu pliku na serwerze.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $newFileUploaded = true;
    }

    try {
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, description = ?, subject = ?, class_level = ?, tags = ?, filepath = ?, file_type = ?, access_type = ?, premium_price = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $subject, $class_level, $tags, $dbPath, $file_type, $access_type, $premium_price, $id, $_SESSION['user_id']]);
        
        if ($newFileUploaded) {
            // Delete old file
            $oldFile = SecurityEnterprise::safePathJoin(APP_ROOT, $note['filepath']);
            if (file_exists($oldFile) && is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Materiał został zaktualizowany pomyślnie!'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (\PDOException $e) {
        if ($newFileUploaded && file_exists($targetPath)) {
            @unlink($targetPath);
        }
        echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
<?php
$pageTitle = 'Edytuj Publikację - Yti School';
require_once 'partials/head.php';
?>
    <style>
        .creator-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #0a84ff;
            background: rgba(10, 132, 255, 0.05);
        }
        .upload-icon {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .progress-bar-wrapper {
            background: rgba(255,255,255,0.08);
            border-radius: 6px;
            overflow: hidden;
            height: 10px;
        }
        .progress-bar {
            background: #0a84ff;
            width: 0%;
            height: 100%;
            transition: width 0.1s linear;
        }
        .progress-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 6px;
            text-align: right;
        }
    </style>
<?php require_once 'partials/topbar.php'; ?>
    <div class="app-container">
        <?php 
        $activePage = 'student_creator_dashboard.php';
        require_once 'partials/sidebar.php'; 
        ?>
        <main class="main-content">
            <div class="creator-container" style="margin-top: 0; padding-top: 0;">

        <h1 style="font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 10px;">Edytuj lekcję</h1>
        <p style="color: var(--text-secondary); margin-bottom: 30px;">Zmień szczegóły swojej publikacji bądź zaktualizuj plik z treścią.</p>

        <div class="glass-card" style="padding: 30px;">
            <div id="status-msg" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;"></div>

            <form id="editForm" action="student_creator_edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Plik materiału (pozostaw pusty, aby zachować obecny)</label>
                    <div class="upload-area" id="drop-area">
                        <span class="upload-icon">🔄</span>
                        <p id="upload-label" style="font-size: 0.95rem; font-weight: 500;">Przeciągnij tutaj plik, aby go zastąpić, lub kliknij</p>
                        <span style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px; display: block;">Obecny plik: <?= htmlspecialchars(basename($note['filepath'])) ?> (<?= strtoupper($note['file_type']) ?>)</span>
                        <input type="file" name="noteFile" id="noteFile" accept="image/*,application/pdf" style="display: none;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="subject">Przedmiot</label>
                        <select name="subject" id="subject" class="form-control" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <option value="matematyka" <?= $note['subject'] === 'matematyka' ? 'selected' : '' ?>>Matematyka</option>
                            <option value="fizyka" <?= $note['subject'] === 'fizyka' ? 'selected' : '' ?>>Fizyka</option>
                            <option value="biologia" <?= $note['subject'] === 'biologia' ? 'selected' : '' ?>>Biologia</option>
                            <option value="chemia" <?= $note['subject'] === 'chemia' ? 'selected' : '' ?>>Chemia</option>
                            <option value="geografia" <?= $note['subject'] === 'geografia' ? 'selected' : '' ?>>Geografia</option>
                            <option value="historia" <?= $note['subject'] === 'historia' ? 'selected' : '' ?>>Historia</option>
                            <option value="informatyka" <?= $note['subject'] === 'informatyka' ? 'selected' : '' ?>>Informatyka</option>
                            <option value="polski" <?= $note['subject'] === 'polski' ? 'selected' : '' ?>>Język Polski</option>
                            <option value="angielski" <?= $note['subject'] === 'angielski' ? 'selected' : '' ?>>Język Angielski</option>
                            <option value="inne" <?= $note['subject'] === 'inne' ? 'selected' : '' ?>>Inne</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_level">Docelowa Klasa</label>
                        <select name="class_level" id="class_level" class="form-control" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <optgroup label="Szkoła Podstawowa (1-8)" style="background: #111;">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>_SP" <?= $note['class_level'] === "{$i}_SP" ? 'selected' : '' ?>>Klasa <?= $i ?> SP</option>
                                <?php endfor; ?>
                            </optgroup>
                            <optgroup label="Liceum (1-4)" style="background: #111;">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?= $i ?>_LO" <?= $note['class_level'] === "{$i}_LO" ? 'selected' : '' ?>>Klasa <?= $i ?> LO</option>
                                <?php endfor; ?>
                            </optgroup>
                            <optgroup label="Technikum (1-5)" style="background: #111;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>_Tech" <?= $note['class_level'] === "{$i}_Tech" ? 'selected' : '' ?>>Klasa <?= $i ?> Technikum</option>
                                <?php endfor; ?>
                            </optgroup>
                            <optgroup label="Studia (1-5)" style="background: #111;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>_Studia" <?= $note['class_level'] === "{$i}_Studia" ? 'selected' : '' ?>><?= $i ?> Rok Studiów</option>
                                <?php endfor; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Tytuł materiału</label>
                    <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($note['title']) ?>" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>

                <div class="form-group">
                    <label for="description">Opis i szczegóły</label>
                    <textarea name="description" id="description" class="form-control" rows="4" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($note['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tags">Tagi (oddzielone przecinkami)</label>
                    <input type="text" name="tags" id="tags" class="form-control" value="<?= htmlspecialchars($note['tags'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>



                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Zapisz zmiany</button>
            </form>

            <div class="progress-container">
                <div class="progress-bar-wrapper">
                    <div class="progress-bar"></div>
                </div>
                <div class="progress-text">Wysyłanie: 0%</div>
            </div>
        </div>
        </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('noteFile');
            const editForm = document.getElementById('editForm');
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            const statusMsg = document.getElementById('status-msg');
            const label = document.getElementById('upload-label');

            // Drag and drop setup
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropArea.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('dragover');
                }, false);
            });

            dropArea.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt.files.length) {
                    fileInput.files = dt.files;
                    label.textContent = `Zastąp plik: ${dt.files[0].name}`;
                    label.style.color = '#0a84ff';
                }
            });

            dropArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    label.textContent = `Zastąp plik: ${fileInput.files[0].name}`;
                    label.style.color = '#0a84ff';
                }
            });

            editForm.addEventListener('submit', (e) => {
                e.preventDefault();

                const formData = new FormData(editForm);
                const xhr = new XMLHttpRequest();

                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = 'Zapisywanie: 0%';
                statusMsg.style.display = 'none';

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = pct + '%';
                        progressText.textContent = `Wysyłanie: ${pct}%`;
                    }
                });

                xhr.addEventListener('load', () => {
                    let res = {};
                    try {
                        res = JSON.parse(xhr.responseText);
                    } catch(err) {
                        res = { success: false, message: 'Błąd serwera lub nieprawidłowa odpowiedź.' };
                    }

                    if (xhr.status === 200 && res.success) {
                        showStatus(res.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'student_creator_dashboard.php';
                        }, 1500);
                    } else {
                        showStatus(res.message || 'Wystąpił błąd.', 'danger');
                        progressContainer.style.display = 'none';
                    }
                });

                xhr.addEventListener('error', () => {
                    showStatus('Błąd połączenia.', 'danger');
                    progressContainer.style.display = 'none';
                });

                xhr.open('POST', 'student_creator_edit.php?id=<?= $id ?>', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });

            function showStatus(msg, type) {
                statusMsg.className = `alert alert-${type}`;
                statusMsg.textContent = msg;
                statusMsg.style.display = 'block';
                if (type === 'success') {
                    statusMsg.style.backgroundColor = 'rgba(48, 209, 88, 0.15)';
                    statusMsg.style.color = '#30d158';
                    statusMsg.style.border = '1px solid rgba(48, 209, 88, 0.2)';
                } else {
                    statusMsg.style.backgroundColor = 'rgba(255, 69, 58, 0.15)';
                    statusMsg.style.color = '#ff453a';
                    statusMsg.style.border = '1px solid rgba(255, 69, 58, 0.2)';
                }
            }
        });
    </script>
    <script src="app.js"></script>
</body>
</html>
