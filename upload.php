<?php
require_once 'db.php';
requireTeacher();

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
    $dbPath = 'storage/private/files/' . $filename;

    if (move_uploaded_file($validated['tmp_name'], $targetPath)) {
        try {
            $file_type = (str_starts_with($validated['mime'], 'image/')) ? 'image' : 'pdf';
            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, description, subject, class_level, tags, filepath, file_type, access_type, premium_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $subject, $class_level, $tags, $dbPath, $file_type, $access_type, $premium_price]);
            
            $note_id = $pdo->lastInsertId();
            $playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
            if ($playlist_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO playlist_notes (playlist_id, note_id) VALUES (?, ?)");
                $stmt->execute([$playlist_id, $note_id]);
            }

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
$pageTitle = 'Dodaj Materiały - Yti School';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php 
        $activePage = 'upload.php';
        require_once 'partials/sidebar.php'; 
        ?>

        <!-- Main Upload View -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2>Dodaj Materiał Naukowy</h2>
                    <p style="color: var(--text-secondary); margin-top: 5px;">Opublikuj notatki, schematy lub zadania w formatach graficznych bądź PDF</p>
                </div>
            </header>

            <div class="glass-card" style="max-width: 800px; padding: 40px;">
                <div id="status-msg" style="display: none;"></div>

                <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Plik Notatki / Zdjęcia</label>
                        <div class="upload-area" id="drop-area">
                            <div class="upload-icon">
                                <svg width="50" height="50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p>Przeciągnij i upuść plik tutaj, lub kliknij, aby przeglądać</p>
                            <span style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px; display: block;">Obsługuje PDF, JPG, JPEG, PNG, WEBP (Maksymalnie 50MB)</span>
                            <input type="file" name="noteFile" id="noteFile" accept="image/*,application/pdf" style="display: none;" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="subject">Przedmiot</label>
                            <select name="subject" id="subject" class="form-control" required>
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
                            <select name="class_level" id="class_level" class="form-control" required>
                                <option value="" disabled selected>Wybierz klasę...</option>
                                <optgroup label="Szkoła Podstawowa (1-8)">
                                    <option value="1_SP">Klasa 1 SP</option>
                                    <option value="2_SP">Klasa 2 SP</option>
                                    <option value="3_SP">Klasa 3 SP</option>
                                    <option value="4_SP">Klasa 4 SP</option>
                                    <option value="5_SP">Klasa 5 SP</option>
                                    <option value="6_SP">Klasa 6 SP</option>
                                    <option value="7_SP">Klasa 7 SP</option>
                                    <option value="8_SP">Klasa 8 SP</option>
                                </optgroup>
                                <optgroup label="Liceum (1-4)">
                                    <option value="1_LO">Klasa 1 LO</option>
                                    <option value="2_LO">Klasa 2 LO</option>
                                    <option value="3_LO">Klasa 3 LO</option>
                                    <option value="4_LO">Klasa 4 LO</option>
                                </optgroup>
                                <optgroup label="Technikum (1-5)">
                                    <option value="1_Tech">Klasa 1 Technikum</option>
                                    <option value="2_Tech">Klasa 2 Technikum</option>
                                    <option value="3_Tech">Klasa 3 Technikum</option>
                                    <option value="4_Tech">Klasa 4 Technikum</option>
                                    <option value="5_Tech">Klasa 5 Technikum</option>
                                </optgroup>
                                <optgroup label="Studia (1-5)">
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
                        <input type="text" name="title" id="title" class="form-control" placeholder="Np. Działania na ułamkach zwykłych" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Opis i wskazówki</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Opisz zagadnienia lub dodaj dodatkowe informacje dla uczniów..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tagi (oddzielone przecinkami)</label>
                        <input type="text" name="tags" id="tags" class="form-control" placeholder="ułamki, matematyka, lekcja1">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="access_type">Dostęp</label>
                            <select name="access_type" id="access_type" class="form-control">
                                <option value="free" selected>Free</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="premium_price">Cena premium (PLN)</label>
                            <input type="number" name="premium_price" id="premium_price" class="form-control" min="1" max="20" step="0.50" placeholder="Np. 19.99" disabled>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Dodaj materiał</button>
                </form>

                <!-- Progress container -->
                <div class="progress-container">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar"></div>
                    </div>
                    <div class="progress-text">Wysyłanie: 0%</div>
                </div>
            </div>
        </main>
    </div>

    <script src="/upload.js"></script>
</body>
</html>
