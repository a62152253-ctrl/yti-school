<?php
require_once 'db.php';
requireLogin();

if (!isStudentCreator()) {
    redirect('student_dashboard.php');
}

redirect('upload_student.php');
exit;
    SecurityEnterprise::requirePost();
    SecurityEnterprise::assertSameOrigin();
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');

    $title       = SecurityEnterprise::normalizeText($_POST['title'] ?? '');
    $description = SecurityEnterprise::normalizeText($_POST['description'] ?? '');
    $subject     = SecurityEnterprise::normalizeText($_POST['subject'] ?? '');
    $class_level = SecurityEnterprise::normalizeText($_POST['class_level'] ?? '');
    $tags        = SecurityEnterprise::normalizeText($_POST['tags'] ?? '');
    $access_type = (SecurityEnterprise::getString('access_type', 'free') === 'premium') ? 'premium' : 'free';
    $premium_price = $access_type === 'premium' ? SecurityEnterprise::getFloat('premium_price', 0.0) : 0.0;

    if ($access_type === 'premium' && $premium_price > 20.00) {
        echo json_encode(['success' => false, 'message' => 'Maksymalna cena dla materiału premium to 20.00 PLN']);
        exit;
    }

    if (empty($title) || empty($subject) || empty($class_level)) {
        echo json_encode(['success' => false, 'message' => 'Proszę wypełnić wymagane pola (Tytuł, Przedmiot, Klasa).']);
        exit;
    }

    if ($access_type === 'premium' && $premium_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Podaj cenę dla materiału premium.']);
        exit;
    }

    if (!isset($_FILES['noteFile'])) {
        echo json_encode(['success' => false, 'message' => 'Brak pliku do przesłania.']);
        exit;
    }

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
    $dbPath = UPLOAD_URL_PATH . '/' . $filename;

    if (move_uploaded_file($validated['tmp_name'], $targetPath)) {
        try {
            $file_type = (str_starts_with($validated['mime'], 'image/')) ? 'image' : 'pdf';
            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, description, subject, class_level, tags, filepath, file_type, access_type, premium_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $subject, $class_level, $tags, $dbPath, $file_type, $access_type, $premium_price]);
            
            echo json_encode(['success' => true, 'message' => 'Materiał został dodany pomyślnie!'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (\PDOException $e) {
            @unlink($targetPath);
            echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Błąd zapisu pliku na serwerze.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<?php
$pageTitle = 'Dodaj Materiały (Uczeń-Twórca) - Yti School';
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
            padding: 40px 20px;
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
            font-size: 2.5rem;
            margin-bottom: 12px;
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

        <h1 style="font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 10px;">Dodaj lekcję lub notatki</h1>
        <p style="color: var(--text-secondary); margin-bottom: 30px;">Opublikuj materiały dla innych uczniów (w formacie PDF lub jako pliki graficzne).</p>

        <div class="glass-card" style="padding: 30px;">
            <div id="status-msg" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;"></div>

            <form id="uploadForm" action="student_creator_upload.php" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Wybierz plik z komputera</label>
                    <div class="upload-area" id="drop-area">
                        <span class="upload-icon">📁</span>
                        <p id="upload-label" style="font-size: 0.95rem; font-weight: 500;">Przeciągnij i upuść plik tutaj, lub kliknij, aby go wybrać</p>
                        <span style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px; display: block;">Obsługiwane formaty: PDF, JPG, JPEG, PNG, WEBP (maksymalnie 50MB)</span>
                        <input type="file" name="noteFile" id="noteFile" accept="image/*,application/pdf" style="display: none;" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="subject">Przedmiot</label>
                        <select name="subject" id="subject" class="form-control" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <option value="" disabled selected>Wybierz przedmiot...</option>
                            <option value="matematyka">Matematyka</option>
                            <option value="fizyka">Fizyka</option>
                            <option value="biologia">Biologia</option>
                            <option value="chemia">Chemia</option>
                            <option value="geografia">Geografia</option>
                            <option value="historia">Historia</option>
                            <option value="informatyka">Informatyka</option>
                            <option value="polski">Język Polski</option>
                            <option value="angielski">Język Angielski</option>
                            <option value="inne">Inne</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_level">Docelowa Klasa</label>
                        <select name="class_level" id="class_level" class="form-control" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <option value="" disabled selected>Wybierz klasę...</option>
                            <optgroup label="Szkoła Podstawowa (1-8)" style="background: #111;">
                                <option value="1_SP">Klasa 1 SP</option>
                                <option value="2_SP">Klasa 2 SP</option>
                                <option value="3_SP">Klasa 3 SP</option>
                                <option value="4_SP">Klasa 4 SP</option>
                                <option value="5_SP">Klasa 5 SP</option>
                                <option value="6_SP">Klasa 6 SP</option>
                                <option value="7_SP">Klasa 7 SP</option>
                                <option value="8_SP">Klasa 8 SP</option>
                            </optgroup>
                            <optgroup label="Liceum (1-4)" style="background: #111;">
                                <option value="1_LO">Klasa 1 LO</option>
                                <option value="2_LO">Klasa 2 LO</option>
                                <option value="3_LO">Klasa 3 LO</option>
                                <option value="4_LO">Klasa 4 LO</option>
                            </optgroup>
                            <optgroup label="Technikum (1-5)" style="background: #111;">
                                <option value="1_Tech">Klasa 1 Technikum</option>
                                <option value="2_Tech">Klasa 2 Technikum</option>
                                <option value="3_Tech">Klasa 3 Technikum</option>
                                <option value="4_Tech">Klasa 4 Technikum</option>
                                <option value="5_Tech">Klasa 5 Technikum</option>
                            </optgroup>
                            <optgroup label="Studia (1-5)" style="background: #111;">
                                <option value="1_Studia">1 Rok Studiów</option>
                                <option value="2_Studia">2 Rok Studiów</option>
                                <option value="3_Studia">3 Rok Studiów</option>
                                <option value="4_Studia">4 Rok Studiów</option>
                                <option value="5_Studia">5 Rok Studiów</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Tytuł materiału</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Np. Twierdzenie Pitagorasa - wprowadzenie i zadania" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>

                <div class="form-group">
                    <label for="description">Opis i szczegóły</label>
                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Dodaj krótki opis, co znajduje się w tych notatkach..." style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);"></textarea>
                </div>

                <div class="form-group">
                    <label for="tags">Tagi (oddzielone przecinkami)</label>
                    <input type="text" name="tags" id="tags" class="form-control" placeholder="geometria, trojkaty, pitagoras" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="access_type">Dostęp</label>
                        <select name="access_type" id="access_type" class="form-control" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                            <option value="free" selected>Darmowy (Free)</option>
                            <option value="premium">Płatny (Premium)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="premium_price">Cena (PLN)</label>
                        <input type="number" name="premium_price" id="premium_price" class="form-control" min="1" max="20" step="0.50" placeholder="Maksymalnie 20.00" disabled style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Opublikuj materiał</button>
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
            const uploadForm = document.getElementById('uploadForm');
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            const statusMsg = document.getElementById('status-msg');
            const accessType = document.getElementById('access_type');
            const premiumPrice = document.getElementById('premium_price');
            const label = document.getElementById('upload-label');

            if (accessType && premiumPrice) {
                accessType.addEventListener('change', () => {
                    const isPremium = accessType.value === 'premium';
                    premiumPrice.disabled = !isPremium;
                    premiumPrice.required = isPremium;
                    if (!isPremium) {
                        premiumPrice.value = '';
                    }
                });
            }

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
                    label.textContent = `Wybrany plik: ${dt.files[0].name}`;
                    label.style.color = '#0a84ff';
                }
            });

            dropArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    label.textContent = `Wybrany plik: ${fileInput.files[0].name}`;
                    label.style.color = '#0a84ff';
                }
            });

            uploadForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!fileInput.files.length) {
                    showStatus('Wybierz plik z materiałem.', 'danger');
                    return;
                }

                const formData = new FormData(uploadForm);
                const xhr = new XMLHttpRequest();

                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = 'Wysyłanie: 0%';
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

                xhr.open('POST', 'student_creator_upload.php', true);
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
