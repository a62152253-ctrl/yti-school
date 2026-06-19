<?php
require_once 'db.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (empty($title) || empty($subject) || empty($class_level)) {
        echo json_encode(['success' => false, 'message' => 'Proszę wypełnić wymagane pola (Tytuł, Przedmiot, Klasa).']);
        exit;
    }

    if ($access_type === 'premium' && $premium_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Podaj cenę dla materiału premium.']);
        exit;
    }

    if ($access_type === 'premium' && $premium_price > 20.00) {
        echo json_encode(['success' => false, 'message' => 'Maksymalna cena dla materiału premium to 20.00 PLN']);
        exit;
    }

    if (!isset($_FILES['presentationFiles']) || empty($_FILES['presentationFiles']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'Proszę wybrać przynajmniej jeden plik graficzny do slajdów.']);
        exit;
    }

    $files = $_FILES['presentationFiles'];
    $fileCount = count($files['name']);

    if ($fileCount > 15) {
        echo json_encode(['success' => false, 'message' => 'Prezentacja może zawierać maksymalnie 15 slajdów (zdjęć).']);
        exit;
    }

    $allowedTypes = ALLOWED_PRESENTATION_IMAGE_TYPES;
    $uploadedPaths = [];

    for ($i = 0; $i < $fileCount; $i++) {
        $uploadItem = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];

        [$valid, $validation] = SecurityEnterprise::validateUpload($uploadItem, $allowedTypes, MAX_FILE_UPLOAD_SIZE);
        if ($valid !== true) {
            $message = is_string($validation) ? $validation : 'Nieprawidłowe dane pliku.';
            echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        /** @var array{name:string,mime:string,size:int,tmp_name:string} $validated */
        $validated = $validation;
        $extension = SecurityEnterprise::safeFileExtension($validated['name']);
        if ($extension === '') {
            $extension = 'bin';
        }

        $filename = sprintf('slide_%s.%s', bin2hex(random_bytes(8)), $extension);
        $targetPath = SecurityEnterprise::safePathJoin(UPLOAD_DIR, $filename);
        $dbPath = UPLOAD_URL_PATH . '/' . $filename;

        if (!move_uploaded_file($validated['tmp_name'], $targetPath)) {
            foreach ($uploadedPaths as $path) {
                @unlink(SecurityEnterprise::safePathJoin(APP_ROOT, $path));
            }
            echo json_encode(['success' => false, 'message' => 'Nie udało się zapisać slajdów na serwerze.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $uploadedPaths[] = $dbPath;
    }

    // Insert into database
    try {
        $playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;

        SecurityEnterprise::withTransaction($pdo, function (PDO $pdo) use ($uploadedPaths, $title, $description, $subject, $class_level, $tags, $access_type, $premium_price, $playlist_id) {
            $filepathJson = json_encode($uploadedPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $file_type = 'presentation';

            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, description, subject, class_level, tags, filepath, file_type, access_type, premium_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $subject, $class_level, $tags, $filepathJson, $file_type, $access_type, $premium_price]);

            $note_id = $pdo->lastInsertId();
            if ($playlist_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO playlist_notes (playlist_id, note_id) VALUES (?, ?)");
                $stmt->execute([$playlist_id, $note_id]);
            }
        });

        echo json_encode(['success' => true, 'message' => 'Prezentacja została utworzona i opublikowana!'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (\Throwable $e) {
        foreach ($uploadedPaths as $path) {
            @unlink(SecurityEnterprise::safePathJoin(APP_ROOT, $path));
        }
        echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
