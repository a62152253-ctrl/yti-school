<?php
require_once 'db.php';
requireLogin();

// Redirect students to their YouTube-style dashboard
if (isStudent()) {
    redirect('student_dashboard.php');
}
$user_id = $_SESSION['user_id'];

// Check teacher verification status
try {
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $dbUser = $stmt->fetch();
    if ($dbUser) {
        $_SESSION['is_verified'] = (int)$dbUser['is_verified'];
    }
} catch (\PDOException $e) {}

if (!($_SESSION['is_verified'] ?? 0)) {
    redirect('teacher_verification.php');
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=raport_statystyk_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Polish characters display in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Column headers
    fputcsv($output, ['Tytuł', 'Typ pliku', 'Przedmiot', 'Klasa', 'Wyświetlenia', 'Dostęp', 'Cena', 'Komentarze', 'Data utworzenia']);
    
    try {
        $stmt = $pdo->prepare("SELECT n.*, 
                               (SELECT COUNT(*) FROM comments WHERE note_id = n.id) as note_comments_count 
                               FROM notes n 
                               WHERE n.user_id = ? 
                               ORDER BY n.created_at DESC");
        $stmt->execute([$user_id]);
        $exportNotes = $stmt->fetchAll();
        
        foreach ($exportNotes as $row) {
            $typeLabel = $row['file_type'] === 'presentation' ? 'Prezentacja' : ($row['file_type'] === 'pdf' ? 'PDF' : 'Zdjęcie');
            fputcsv($output, [
                $row['title'],
                $typeLabel,
                $row['subject'],
                $row['class_level'],
                $row['views'],
                $row['access_type'] === 'premium' ? 'Premium' : 'Darmowy',
                $row['premium_price'] . ' PLN',
                $row['note_comments_count'],
                $row['created_at']
            ]);
        }
    } catch (\PDOException $e) {
        fputcsv($output, ['Błąd pobierania danych: ' . $e->getMessage()]);
    }
    
    fclose($output);
    exit;
}

$errorMsg = '';
$successMsg = '';
$sortOption = $_GET['sort'] ?? 'newest';
$allowedSorts = [
    'newest' => 'n.created_at DESC',
    'popular' => 'n.views DESC',
    'alphabetical' => 'n.title ASC'
];
$sortSql = $allowedSorts[$sortOption] ?? $allowedSorts['newest'];

$currentPage = max(1, (int)($_GET['page'] ?? 1));
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;

// Handle new playlist creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    $playlistTitle = trim($_POST['playlist_title'] ?? '');
    $playlistDesc = trim($_POST['playlist_desc'] ?? '');
    if (!empty($playlistTitle)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO playlists (user_id, title, description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $playlistTitle, $playlistDesc]);
            redirect('dashboard.php?msg=playlist_created');
        } catch (\PDOException $e) {
            $errorMsg = "Błąd: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Tytuł playlisty jest wymagany.";
    }
}

// Handle playlist deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_playlist'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    $playlistToDelete = (int)$_POST['playlist_id'];
    try {
        // Delete playlist notes association (ensuring playlist belongs to the user)
        $stmt = $pdo->prepare("DELETE FROM playlist_notes WHERE playlist_id = ? AND playlist_id IN (SELECT id FROM playlists WHERE user_id = ?)");
        $stmt->execute([$playlistToDelete, $user_id]);
        
        // Delete playlist itself
        $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
        $stmt->execute([$playlistToDelete, $user_id]);
        
        redirect('dashboard.php?msg=playlist_deleted');
    } catch (\PDOException $e) {
        $errorMsg = "Błąd podczas usuwania playlisty: " . $e->getMessage();
    }
}

// Check message query param
if (!empty($_GET['msg'])) {
    if ($_GET['msg'] === 'playlist_created') {
        $successMsg = "Playlista została pomyślnie utworzona!";
    } elseif ($_GET['msg'] === 'playlist_deleted') {
        $successMsg = "Playlista została pomyślnie usunięta.";
    } elseif ($_GET['msg'] === 'note_deleted') {
        $successMsg = "Lekcja została usunięta.";
    }
}

// 1. Fetch Teacher Stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_notes, SUM(views) as total_views FROM notes WHERE user_id = ?");
    $stats = $stmt->execute([$user_id]) ? $stmt->fetch() : [];
    $notesCount = $stats['total_notes'] ?? 0;
    $viewsCount = $stats['total_views'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_comments FROM comments c JOIN notes n ON c.note_id = n.id WHERE n.user_id = ?");
    $stmt->execute([$user_id]);
    $commentsCount = $stmt->fetch()['total_comments'] ?? 0;

    // Fetch top 6 notes by views for dynamic analytics line chart
    $stmt = $pdo->prepare("SELECT title, views FROM notes WHERE user_id = ? ORDER BY views DESC LIMIT 6");
    $stmt->execute([$user_id]);
    $chartNotes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $notesCount = 0;
    $viewsCount = 0;
    $commentsCount = 0;
    $chartNotes = [];
}

// 2. Fetch Teacher's Playlists
try {
    $stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM playlist_notes WHERE playlist_id = p.id) as notes_count 
                           FROM playlists p 
                           WHERE p.user_id = ? 
                           ORDER BY p.created_at DESC");
    $stmt->execute([$user_id]);
    $myPlaylists = $stmt->fetchAll();
} catch (\PDOException $e) {
    $myPlaylists = [];
}

// Fetch playlist-note associations to know which note is in which playlist
try {
    $stmt = $pdo->prepare("SELECT pn.playlist_id, pn.note_id 
                           FROM playlist_notes pn 
                           JOIN playlists p ON pn.playlist_id = p.id 
                           WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $playlistNotesMap = $stmt->fetchAll();
} catch (\PDOException $e) {
    $playlistNotesMap = [];
}

// Collect search filters and sanitize them
$selectedSubjectFilter = SecurityEnterprise::getSanitizedString('filter_subject');
$selectedTypeFilter = SecurityEnterprise::getSanitizedString('filter_type');

// 3. Fetch Teacher's Uploaded Notes with Comments, Likes and Reports
$queryCountStr = "SELECT COUNT(*) as total_uploaded FROM notes WHERE user_id = ?";
$querySelectStr = "SELECT n.*, 
                   (SELECT COUNT(*) FROM comments WHERE note_id = n.id) as note_comments_count,
                   (SELECT COUNT(*) FROM likes WHERE note_id = n.id AND type = 'like') as likes_count,
                   (SELECT COUNT(*) FROM reports WHERE note_id = n.id) as reports_count
                   FROM notes n 
                   WHERE n.user_id = ?";
$whereParams = [$user_id];

if ($selectedSubjectFilter !== '') {
    $queryCountStr .= " AND subject = ?";
    $querySelectStr .= " AND n.subject = ?";
    $whereParams[] = $selectedSubjectFilter;
}

if ($selectedTypeFilter !== '') {
    if ($selectedTypeFilter === 'presentation') {
        $queryCountStr .= " AND file_type = 'presentation'";
        $querySelectStr .= " AND n.file_type = 'presentation'";
    } else {
        $queryCountStr .= " AND file_type != 'presentation'";
        $querySelectStr .= " AND n.file_type != 'presentation'";
    }
}

try {
    $stmt = $pdo->prepare($queryCountStr);
    $stmt->execute($whereParams);
    $totalUploaded = (int)$stmt->fetch()['total_uploaded'];
} catch (\PDOException $e) {
    $totalUploaded = 0;
}

try {
    $limit = (int)$itemsPerPage;
    $off = (int)$offset;
    $querySelectStr .= " ORDER BY $sortSql LIMIT $limit OFFSET $off";
    
    $stmt = $pdo->prepare($querySelectStr);
    $stmt->execute($whereParams);
    $myNotes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $myNotes = [];
}
?>
<?php
$pageTitle = 'Panel Nauczyciela - Yti School';
$activePage = 'dashboard.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <style>
        :root {
            --bg-color: #080808;
            --card-bg: #121212;
            --card-border: rgba(255, 255, 255, 0.08);
            --primary-color: #ffffff;
            --accent-color: #ffffff;
            --text-primary: #ffffff;
            --text-secondary: #8e8e93;
            --yt-input-bg: #1c1c1e;
        }

        body {
            background-color: #080808 !important;
            color: #ffffff !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            -webkit-font-smoothing: antialiased;
        }

        .main-content {
            padding: 32px !important;
            max-width: none;
            margin-left: var(--sidebar-width) !important;
            margin-top: 56px !important;
            width: calc(100% - var(--sidebar-width)) !important;
        }

        .dashboard-hero {
            background: #121212 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: none !important;
            border-radius: 8px !important;
            padding: 32px !important;
            margin-bottom: 30px !important;
            display: flex;
            flex-direction: column;
            gap: 28px;
            position: relative;
            overflow: hidden;
        }

        .teacher-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 18px;
            max-width: 620px;
        }

        .teacher-status-pill {
            background: #1c1c1e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 12px;
        }

        .teacher-status-pill strong {
            display: block;
            color: #ffffff;
            font-size: 1.18rem;
            font-weight: 700;
            line-height: 1;
        }

        .teacher-status-pill span {
            display: block;
            color: #8e8e93;
            font-size: 0.76rem;
            margin-top: 6px;
        }

        .dashboard-hero h1 {
            font-size: 2rem !important;
            font-weight: 600 !important;
            letter-spacing: -0.025em !important;
            color: #ffffff !important;
            margin-bottom: 8px;
        }

        .dashboard-hero p {
            font-size: 0.95rem !important;
            color: #8e8e93 !important;
            max-width: 600px;
            line-height: 1.5;
        }

        .dashboard-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
            gap: 16px !important;
        }

        .action-card {
            background: #1c1c1e !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            padding: 16px 20px !important;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s ease-in-out !important;
        }

        .action-card:hover {
            transform: translateY(-2px) !important;
            background: #252528 !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5) !important;
        }

        .action-card-icon {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
            box-shadow: none !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            width: 42px !important;
            height: 42px !important;
            border-radius: 10px !important;
            font-size: 1.1rem !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-card-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .action-card-title {
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            color: #ffffff !important;
        }

        .action-card-text {
            font-size: 0.8rem !important;
            color: #8e8e93 !important;
        }

        .dashboard-panel-stats {
            margin-bottom: 30px !important;
        }

        .stats-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .stats-heading h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #ffffff;
        }

        .stats-heading p {
            color: #8e8e93;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
            gap: 16px !important;
        }

        .stats-card {
            background: #121212 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px !important;
            padding: 24px !important;
            box-shadow: none !important;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: border-color 0.2s !important;
        }

        .stats-card:hover {
            transform: none !important;
            box-shadow: none !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        .stats-label {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #8e8e93 !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase;
        }

        .stats-value {
            font-size: 2.2rem !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            background: none !important;
            -webkit-text-fill-color: initial !important;
            margin-top: 8px;
            display: block;
        }

        .saas-dashboard-grid {
            display: grid;
            grid-template-columns: 1.15fr 1.85fr !important;
            gap: 24px !important;
            align-items: start;
        }

        .glass-card, .saas-table-wrapper {
            background: #121212 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            padding: 24px !important;
        }

        .glass-card:hover, .saas-table-wrapper:hover {
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: none !important;
        }

        .card-header-minimal {
            margin-bottom: 20px;
        }

        .card-header-minimal h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .card-header-minimal p {
            color: #8e8e93;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Apple Segmented Controls Tabs */
        .tab-headers {
            display: flex !important;
            background: #1c1c1e !important;
            padding: 3px !important;
            border-radius: 8px !important;
            border: 1px solid rgba(255, 255, 255, 0.04) !important;
            margin-bottom: 24px !important;
            gap: 2px !important;
        }

        .tab-header-btn {
            flex: 1 !important;
            background: transparent !important;
            border: none !important;
            color: #8e8e93 !important;
            padding: 6px 12px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
            cursor: pointer;
            transition: all 0.15s ease !important;
        }

        .tab-header-btn:hover {
            color: #ffffff !important;
        }

        .tab-header-btn.active {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
        }

        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            color: #8e8e93 !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            display: block;
            margin-bottom: 6px;
        }

        .form-control {
            background: #1c1c1e !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 0.9rem !important;
            width: 100%;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: #ffffff !important;
            background: #1c1c1e !important;
        }

        .form-row-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Apple style buttons */
        .btn-primary {
            background: #ffffff !important;
            color: #000000 !important;
            font-weight: 500 !important;
            border-radius: 8px !important;
            padding: 10px 18px !important;
            font-size: 0.9rem !important;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.2s !important;
            text-align: center;
        }

        .btn-primary:hover {
            background: #e5e5ea !important;
        }

        .btn-secondary {
            background: #1c1c1e !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            padding: 10px 18px !important;
            font-size: 0.9rem !important;
            width: auto;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s !important;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #252528 !important;
        }

        .btn-danger {
            background: rgba(255, 69, 58, 0.15) !important;
            color: #ff453a !important;
            border: 1px solid rgba(255, 69, 58, 0.2) !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            cursor: pointer;
            transition: all 0.2s !important;
        }

        .btn-danger:hover {
            background: rgba(255, 69, 58, 0.25) !important;
        }

        /* Upload area drag & drop */
        .upload-area {
            background: #1c1c1e !important;
            border: 1px dashed rgba(255, 255, 255, 0.15) !important;
            border-radius: 8px !important;
            padding: 24px !important;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s !important;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: #ffffff !important;
            background: #252528 !important;
        }

        .upload-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .upload-icon svg {
            stroke: #8e8e93 !important;
        }

        .upload-area p {
            color: #8e8e93 !important;
            font-size: 0.85rem !important;
            margin: 0;
        }

        /* Playlists Section styling */
        .section-subtitle {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            margin-top: 16px;
            margin-bottom: 12px;
        }

        .section-subtitle-border {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 16px;
            margin-top: 20px;
        }

        .playlist-list {
            margin-top: 10px;
        }

        .playlist-item {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            background: #1c1c1e !important;
            padding: 12px 14px !important;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .playlist-item strong {
            color: #ffffff !important;
            font-size: 0.9rem;
        }

        /* Table & Filters controls styling */
        .table-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .table-controls label {
            font-size: 0.8rem;
            color: #8e8e93;
        }

        .table-controls select {
            background: #1c1c1e !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
            padding: 6px 28px 6px 12px !important;
            outline: none;
            cursor: pointer;
        }

        .saas-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .saas-table-wrapper {
            overflow-x: auto;
        }

        .saas-table th {
            text-align: left;
            color: #8e8e93;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .saas-table td {
            padding: 14px 8px;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #ffffff;
            vertical-align: middle;
        }

        .dead-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-width: 260px;
        }

        .dead-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: #1c1c1e;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.88rem;
            line-height: 1;
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            text-decoration: none;
        }

        .dead-action-btn:hover,
        .dead-action-btn.is-touched {
            background: #252528;
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .dead-action-btn.report:hover,
        .dead-action-btn.report.is-touched {
            color: #ff453a;
            border-color: rgba(255, 69, 58, 0.28);
            background: rgba(255, 69, 58, 0.12);
        }

        .dead-action-chip {
            min-width: 46px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: #8e8e93;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 0 8px;
            font-size: 0.76rem;
            font-weight: 500;
        }

        .alert-info {
            background: rgba(100, 210, 255, 0.12) !important;
            color: #64d2ff !important;
            border: 1px solid rgba(100, 210, 255, 0.2) !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            margin-bottom: 16px !important;
            font-size: 0.85rem !important;
        }

        .saas-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        .saas-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges & Pills */
        .file-pill {
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 6px !important;
            padding: 3px 8px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .subject-badge {
            border-radius: 6px !important;
            padding: 3px 8px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            text-transform: capitalize;
            display: inline-block;
        }

        /* Clean HSL colors for subjects */
        .subject-badge.matematyka { background: rgba(10, 132, 255, 0.15) !important; color: #0a84ff !important; border: 1px solid rgba(10, 132, 255, 0.2) !important; }
        .subject-badge.fizyka { background: rgba(48, 209, 88, 0.15) !important; color: #30d158 !important; border: 1px solid rgba(48, 209, 88, 0.2) !important; }
        .subject-badge.biologia { background: rgba(94, 92, 230, 0.15) !important; color: #5e5ce6 !important; border: 1px solid rgba(94, 92, 230, 0.2) !important; }
        .subject-badge.chemia { background: rgba(255, 159, 10, 0.15) !important; color: #ff9f0a !important; border: 1px solid rgba(255, 159, 10, 0.2) !important; }
        .subject-badge.geografia { background: rgba(255, 69, 58, 0.15) !important; color: #ff453a !important; border: 1px solid rgba(255, 69, 58, 0.2) !important; }
        .subject-badge.historia { background: rgba(255, 214, 10, 0.15) !important; color: #ffd60a !important; border: 1px solid rgba(255, 214, 10, 0.2) !important; }
        .subject-badge.polski { background: rgba(191, 90, 242, 0.15) !important; color: #bf5af2 !important; border: 1px solid rgba(191, 90, 242, 0.2) !important; }
        .subject-badge.angielski { background: rgba(100, 210, 255, 0.15) !important; color: #64d2ff !important; border: 1px solid rgba(100, 210, 255, 0.2) !important; }
        .subject-badge.default { background: rgba(255, 255, 255, 0.1) !important; color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.15) !important; }

        .access-badge {
            border-radius: 6px !important;
            padding: 3px 8px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            display: inline-block;
        }

        .access-badge.premium {
            background: rgba(255, 214, 10, 0.15) !important;
            color: #ffd60a !important;
            border: 1px solid rgba(255, 214, 10, 0.2) !important;
        }

        .access-badge.free {
            background: rgba(255, 255, 255, 0.06) !important;
            color: #8e8e93 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .pagination {
            display: flex;
            gap: 6px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .pagination-link {
            background: #1c1c1e !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
            font-size: 0.85rem !important;
            text-decoration: none;
            transition: all 0.2s;
        }

        .pagination-link:hover {
            background: #252528 !important;
        }

        .pagination-link.active {
            background: #ffffff !important;
            color: #000000 !important;
            border-color: #ffffff !important;
        }

        .progress-container {
            margin-top: 16px;
            display: none;
        }

        .progress-bar-wrapper {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            overflow: hidden;
            height: 6px;
        }

        .progress-bar {
            background: #ffffff;
            height: 100%;
            width: 0%;
            transition: width 0.1s ease;
        }

        .progress-text {
            font-size: 0.75rem;
            color: #8e8e93;
            margin-top: 4px;
            text-align: right;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 20px !important;
            }

            .saas-dashboard-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* --- HIGH PREMIUM AESTHETIC UPGRADES --- */

        /* Smooth animated tabs styling */
        .tab-content {
            display: none;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .tab-content.active {
            display: block;
        }

        /* Action cards click micro-animation */
        .action-card:active {
            transform: scale(0.97) !important;
        }

        /* Premium iOS-like floating Toast */
        .toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(28, 28, 30, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 500;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
            z-index: 9999;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
            opacity: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toast.is-visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Premium styled custom playlist popover */
        .playlist-popover {
            position: absolute;
            background: rgba(28, 28, 30, 0.85) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 14px !important;
            padding: 12px !important;
            z-index: 1000;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.7) !important;
            min-width: 220px;
            max-height: 240px;
            overflow-y: auto;
            animation: popoverFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
        }
        .playlist-popover::-webkit-scrollbar {
            width: 4px;
        }
        .playlist-popover::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 2px;
        }
        @keyframes popoverFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .playlist-popover-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #ffffff;
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 8px;
            transition: background 0.15s ease;
            user-select: none;
            margin-bottom: 4px;
        }
        .playlist-popover-item:last-child {
            margin-bottom: 0;
        }
        .playlist-popover-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .playlist-popover-item input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: transparent;
            display: inline-grid;
            place-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            margin: 0;
        }
        .playlist-popover-item input[type="checkbox"]:checked {
            background: #ffffff;
            border-color: #ffffff;
        }
        .playlist-popover-item input[type="checkbox"]:checked::before {
            content: "✓";
            color: #000000;
            font-size: 10px;
            font-weight: 900;
        }
        .playlist-popover-item input[type="checkbox"]:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Hover glows for standard cards */
        .stats-card {
            transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease !important;
        }
        .stats-card:hover {
            transform: translateY(-2px) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
        }

        /* Table design updates */
        .saas-table tr {
            transition: background-color 0.15s ease;
        }
        .saas-table tr:hover td {
            background: rgba(255, 255, 255, 0.02) !important;
        }
    </style>
    <div class="app-container">
<?php require_once 'partials/sidebar.php'; ?>
        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Apple Style Hero Section -->
            <section class="dashboard-hero">
                <div>
                    <h1>Witaj w Panelu, Nauczycielu</h1>
                    <div class="teacher-status-grid" aria-label="Szybkie statystyki">
                        <div class="teacher-status-pill">
                            <strong><?= (int)$notesCount ?></strong>
                            <span>Materiały</span>
                        </div>
                        <div class="teacher-status-pill">
                            <strong><?= (int)$viewsCount ?></strong>
                            <span>Wyświetlenia</span>
                        </div>
                        <div class="teacher-status-pill">
                            <strong><?= count($myPlaylists) ?></strong>
                            <span>Playlisty</span>
                        </div>
                    </div>
                    <p>Zarządzaj swoimi materiałami, analizuj postępy uczniów i publikuj nowoczesne lekcje w jednym, przepięknym miejscu.</p>
                </div>
                
                <div class="dashboard-actions">
                    <button type="button" class="action-card" data-tab="note-tab">
                        <span class="action-card-icon">➕</span>
                        <span class="action-card-content">
                            <span class="action-card-title">Dodaj lekcję</span>
                            <span class="action-card-text">Wgraj PDF lub zdjęcie</span>
                        </span>
                    </button>
                    <button type="button" class="action-card" data-tab="presentation-tab">
                        <span class="action-card-icon">🎞</span>
                        <span class="action-card-content">
                            <span class="action-card-title">Stwórz prezentację</span>
                            <span class="action-card-text">Interaktywne kursy</span>
                        </span>
                    </button>
                    <button type="button" class="action-card" data-tab="playlist-tab">
                        <span class="action-card-icon">📚</span>
                        <span class="action-card-content">
                            <span class="action-card-title">Utwórz playlistę</span>
                            <span class="action-card-text">Zgrupuj materiały</span>
                        </span>
                    </button>
                </div>
            </section>

            <!-- Apple Style Stats Section -->
            <section class="dashboard-panel-stats">
                <div class="stats-heading">
                    <div>
                        <h3>Twoje Wyniki</h3>
                        <p>Podsumowanie zaangażowania uczniów w Twoje materiały</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="window.print()" style="width: auto; padding: 8px 14px;">
                            <span style="margin-right: 8px;">🖨️</span> Drukuj Raport
                        </button>
                        <a href="?export=csv" class="btn btn-secondary" style="width: auto; padding: 8px 14px; text-decoration: none;">
                            <span style="margin-right: 8px;">📥</span> Eksportuj do CSV
                        </a>
                    </div>
                </div>
                <div class="stats-row">
                    <div class="stats-card">
                        <span class="stats-label">Opublikowane materiały</span>
                        <span class="stats-value"><?= (int)$notesCount ?></span>
                    </div>
                    <div class="stats-card">
                        <span class="stats-label">Łączne wyświetlenia</span>
                        <span class="stats-value"><?= (int)$viewsCount ?></span>
                    </div>
                    <div class="stats-card">
                        <span class="stats-label">Otrzymane komentarze</span>
                        <span class="stats-value"><?= (int)$commentsCount ?></span>
                    </div>
                </div>

                <!-- Dynamic Analytics line chart -->
                <div class="glass-card" style="margin-top: 20px; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 1.1rem; font-weight: 600; color: #ffffff; margin: 0;">Wykres Oglądalności Materiałów</h4>
                        <span style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase;">Wyświetlenia top materiałów</span>
                    </div>
                    
                    <?php if (empty($chartNotes)): ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <p style="margin: 0; font-size: 0.88rem;">Brak danych do wykresu. Dodaj swoje pierwsze lekcje.</p>
                        </div>
                    <?php else: 
                        $labels = [];
                        $values = [];
                        foreach ($chartNotes as $cn) {
                            $labels[] = strlen($cn['title']) > 15 ? substr($cn['title'], 0, 12) . '...' : $cn['title'];
                            $values[] = (int)$cn['views'];
                        }
                        $maxViews = max(1, max($values));
                        $points = [];
                        $graphHeight = 110; 
                        $startX = 50;
                        $stepX = 75;
                        foreach ($values as $idx => $val) {
                            $x = $startX + $idx * $stepX;
                            $y = 135 - ($val / $maxViews) * $graphHeight;
                            $points[] = "$x,$y";
                        }
                        $pointsStr = implode(' ', $points);
                        $lastX = $startX + (count($values)-1) * $stepX;
                        $areaPointsStr = "30,135 " . $pointsStr . " " . $lastX . ",135";
                    ?>
                        <div style="width: 100%; display: flex; justify-content: center; align-items: flex-end;">
                            <svg viewBox="0 0 500 160" class="chart-container-svg" style="width: 100%; height: 140px;">
                                <defs>
                                    <linearGradient id="lineGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="rgba(168, 85, 247, 0.45)" />
                                        <stop offset="100%" stop-color="rgba(99, 102, 241, 0)" />
                                    </linearGradient>
                                    <linearGradient id="lineStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#6366f1" />
                                        <stop offset="100%" stop-color="#a855f7" />
                                    </linearGradient>
                                </defs>
                                <line x1="30" y1="25" x2="480" y2="25" class="chart-grid-line" />
                                <line x1="30" y1="65" x2="480" y2="65" class="chart-grid-line" />
                                <line x1="30" y1="105" x2="480" y2="105" class="chart-grid-line" />
                                <line x1="30" y1="135" x2="480" y2="135" class="chart-axis-line" />
                                
                                <polygon points="<?= $areaPointsStr ?>" fill="url(#lineGrad)" />
                                <polyline points="<?= $pointsStr ?>" fill="none" stroke="url(#lineStroke)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                
                                <?php foreach ($points as $idx => $pt): 
                                    $coords = explode(',', $pt);
                                    $x = $coords[0];
                                    $y = $coords[1];
                                    $val = $values[$idx];
                                ?>
                                    <circle cx="<?= $x ?>" cy="<?= $y ?>" r="5" fill="#ffffff" stroke="#a855f7" stroke-width="2" />
                                    <text x="<?= $x ?>" y="<?= $y - 10 ?>" fill="#fff" font-size="9" text-anchor="middle" font-weight="600"><?= $val ?></text>
                                    <text x="<?= $x ?>" y="152" class="chart-label-text" style="font-size: 8.5px; fill: var(--text-secondary); text-anchor: middle;"><?= htmlspecialchars($labels[$idx]) ?></text>
                                <?php endforeach; ?>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Two Column SaaS Layout -->
            <section class="dashboard-section saas-dashboard-grid">
                <!-- Column 1: Upload tabbed forms -->
                <div class="glass-card">
                    <div class="card-header-minimal">
                        <h3>Dodaj nowy materiał</h3>
                        <p>Utwórz lekcję, prezentację lub playlistę w eleganckim, prostym formularzu.</p>
                    </div>
                    
                    <div class="tab-headers" role="tablist" aria-label="Typ materiału">
                        <button class="tab-header-btn active" id="btn-note-tab" role="tab" aria-controls="note-tab" aria-selected="true">Dodaj Lekcję</button>
                        <button class="tab-header-btn" id="btn-presentation-tab" role="tab" aria-controls="presentation-tab" aria-selected="false">Stwórz Prezentację</button>
                        <button class="tab-header-btn" id="btn-playlist-tab" role="tab" aria-controls="playlist-tab" aria-selected="false">Playlisty</button>
                    </div>

                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($successMsg)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
                    <?php endif; ?>
                    <div id="status-msg" class="status-msg"></div>

                    <!-- TAB 1: Single file upload -->
                    <div id="note-tab" class="tab-content active" role="tabpanel" aria-labelledby="btn-note-tab">
                        <form id="uploadForm">
                            <?= csrfField() ?>
                            <div class="form-group">
                                <label>Plik Lekcji (PDF lub Obraz JPG/PNG/WEBP)</label>
                                <div class="upload-area" id="drop-area">
                                    <div class="upload-icon">
                                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                    </div>
                                    <p>Przeciągnij plik tutaj, lub kliknij</p>
                                    <input type="file" name="noteFile" id="noteFile" accept="image/*,application/pdf" hidden>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="title">Tytuł lekcji</label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="np. Funkcje liniowe – wprowadzenie" required>
                            </div>

                            <div class="form-group">
                                <label for="playlist_id">Dodaj do playlisty (opcjonalnie)</label>
                                <select name="playlist_id" id="playlist_id" class="form-control">
                                    <option value="" selected>Brak (Lekcja pojedyncza)</option>
                                    <?php foreach ($myPlaylists as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject">Przedmiot</label>
                                <select name="subject" id="subject" class="form-control" required>
                                    <option value="" disabled selected>Wybierz...</option>
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
                                <label for="class_level">Poziom (klasa / rok)</label>
                                <select name="class_level" id="class_level" class="form-control" required>
                                    <option value="" disabled selected>Wybierz...</option>
                                    <optgroup label="Liceum (1-4)">
                                        <option value="1_LO">Klasa 1 LO</option>
                                        <option value="2_LO">Klasa 2 LO</option>
                                        <option value="3_LO">Klasa 3 LO</option>
                                        <option value="4_LO">Klasa 4 LO</option>
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

                            <div class="form-group">
                                <label for="description">Opis lekcji</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="np. Omówienie funkcji liniowych, przykładów i zadań."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="tags">Tagi</label>
                                <input type="text" name="tags" id="tags" class="form-control" placeholder="np. funkcje liniowe, matura, zadania">
                            </div>

                            <div class="form-row-two">
                                <div class="form-group">
                                    <label for="note_access_type">Dostęp</label>
                                    <select name="access_type" id="note_access_type" class="form-control">
                                        <option value="free" selected>Free</option>
                                        <option value="premium">Premium</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="note_premium_price">Cena premium (PLN)</label>
                                    <input type="number" name="premium_price" id="note_premium_price" class="form-control" min="1" max="20" step="0.50" placeholder="0.00" disabled>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Publikuj Lekcję</button>
                        </form>
                    </div>

                    <!-- TAB 2: Multiple images presentation -->
                    <div id="presentation-tab" class="tab-content" role="tabpanel" aria-labelledby="btn-presentation-tab">
                        <form id="presentationForm">
                            <?= csrfField() ?>
                            <div class="form-group">
                                <label>Wgraj slajdy (Zdjęcia, do 15 plików)</label>
                                <div class="upload-area" id="pres-drop-area">
                                    <div class="upload-icon">
                                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p>Przeciągnij zdjęcia tutaj, lub kliknij</p>
                                    <input type="file" name="presentationFiles[]" id="presentationFiles" accept="image/*" hidden multiple>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="pres-title">Tytuł prezentacji</label>
                                <input type="text" name="title" id="pres-title" class="form-control" placeholder="Wpisz nazwę prezentacji..." required>
                            </div>

                            <div class="form-group">
                                <label for="pres-playlist_id">Dodaj do playlisty (opcjonalnie)</label>
                                <select name="playlist_id" id="pres-playlist_id" class="form-control">
                                    <option value="" selected>Brak (Prezentacja pojedyncza)</option>
                                    <?php foreach ($myPlaylists as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pres-subject">Przedmiot</label>
                                <select name="subject" id="pres-subject" class="form-control" required>
                                    <option value="" disabled selected>Wybierz...</option>
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
                                <label for="pres-class_level">Poziom (klasa / rok)</label>
                                <select name="class_level" id="pres-class_level" class="form-control" required>
                                    <option value="" disabled selected>Wybierz...</option>
                                    <optgroup label="Liceum (1-4)">
                                        <option value="1_LO">Klasa 1 LO</option>
                                        <option value="2_LO">Klasa 2 LO</option>
                                        <option value="3_LO">Klasa 3 LO</option>
                                        <option value="4_LO">Klasa 4 LO</option>
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

                            <div class="form-group">
                                <label for="pres-description">Opis i wprowadzenie</label>
                                <textarea name="description" id="pres-description" class="form-control" rows="3" placeholder="np. Zestaw slajdów do powtórki przed sprawdzianem."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="pres-tags">Tagi</label>
                                <input type="text" name="tags" id="pres-tags" class="form-control" placeholder="prezentacja, algebra, analiza">
                            </div>

                            <div class="form-row-two">
                                <div class="form-group">
                                    <label for="pres_access_type">Dostęp</label>
                                    <select name="access_type" id="pres_access_type" class="form-control">
                                        <option value="free" selected>Free</option>
                                        <option value="premium">Premium</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="pres_premium_price">Cena premium (PLN)</label>
                                    <input type="number" name="premium_price" id="pres_premium_price" class="form-control" min="1" max="20" step="0.50" placeholder="0.00" disabled>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Utwórz Prezentację</button>
                        </form>
                    </div>

                    <!-- TAB 3: Playlists Management -->
                    <div id="playlist-tab" class="tab-content" role="tabpanel" aria-labelledby="btn-playlist-tab">
                        <h4 class="section-subtitle">Nowa playlista</h4>
                        <form action="dashboard.php" method="POST" class="playlist-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="create_playlist" value="1">
                            <div class="form-group">
                                <label for="playlist_title">Tytuł playlisty</label>
                                <input type="text" name="playlist_title" id="playlist_title" class="form-control" placeholder="np. Algebra 2026" required>
                            </div>
                            <div class="form-group">
                                <label for="playlist_desc">Opis playlisty</label>
                                <textarea name="playlist_desc" id="playlist_desc" class="form-control" rows="2" placeholder="np. Zestaw lekcji do powtórki przed maturą."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Dodaj playlistę</button>
                        </form>

                        <h4 class="section-subtitle section-subtitle-border">Twoje playlisty</h4>
                        <?php if (empty($myPlaylists)): ?>
                            <p class="empty-state">Nie utworzyłeś jeszcze żadnych playlist.</p>
                        <?php else: ?>
                            <ul class="playlist-list" style="list-style: none; padding: 0;">
                                <?php foreach ($myPlaylists as $p): ?>
                                    <li class="playlist-item">
                                        <div>
                                            <strong><?= htmlspecialchars($p['title']) ?></strong>
                                            <div style="font-size: 0.8rem; color: #8e8e93; margin-top: 2px;"><?= (int)$p['notes_count'] ?> lekcji</div>
                                        </div>
                                        <form action="dashboard.php" method="POST" class="delete-playlist-form" style="margin: 0; display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="delete_playlist" value="1">
                                            <input type="hidden" name="playlist_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="width: auto; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;">Usuń</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="progress-container">
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar"></div>
                        </div>
                        <div class="progress-text">Wysyłanie: 0%</div>
                    </div>
                </div>

                <!-- Column 2: Published Materials List -->
                <div class="saas-table-wrapper">
                    <div class="stats-heading" style="margin-bottom: 12px;">
                        <div>
                            <h3>Opublikowane materiały</h3>
                            <p>Sortuj, przeglądaj i zarządzaj treściami które już opublikowałeś.</p>
                        </div>
                        <form method="GET" class="table-controls">
                            <label for="filter_subject">Przedmiot:</label>
                            <select name="filter_subject" id="filter_subject">
                                <option value="">Wszystkie</option>
                                <option value="matematyka" <?= $selectedSubjectFilter === 'matematyka' ? 'selected' : '' ?>>Matematyka</option>
                                <option value="fizyka" <?= $selectedSubjectFilter === 'fizyka' ? 'selected' : '' ?>>Fizyka</option>
                                <option value="biologia" <?= $selectedSubjectFilter === 'biologia' ? 'selected' : '' ?>>Biologia</option>
                                <option value="chemia" <?= $selectedSubjectFilter === 'chemia' ? 'selected' : '' ?>>Chemia</option>
                                <option value="geografia" <?= $selectedSubjectFilter === 'geografia' ? 'selected' : '' ?>>Geografia</option>
                                <option value="historia" <?= $selectedSubjectFilter === 'historia' ? 'selected' : '' ?>>Historia</option>
                                <option value="polski" <?= $selectedSubjectFilter === 'polski' ? 'selected' : '' ?>>Polski</option>
                                <option value="angielski" <?= $selectedSubjectFilter === 'angielski' ? 'selected' : '' ?>>Angielski</option>
                                <option value="inne" <?= $selectedSubjectFilter === 'inne' ? 'selected' : '' ?>>Inne</option>
                            </select>

                            <label for="filter_type">Typ:</label>
                            <select name="filter_type" id="filter_type">
                                <option value="">Wszystkie</option>
                                <option value="lesson" <?= $selectedTypeFilter === 'lesson' ? 'selected' : '' ?>>Lekcja</option>
                                <option value="presentation" <?= $selectedTypeFilter === 'presentation' ? 'selected' : '' ?>>Prezentacja</option>
                            </select>

                            <label for="sort">Sortuj:</label>
                            <select name="sort" id="sort">
                                <option value="newest" <?= $sortOption === 'newest' ? 'selected' : '' ?>>Najnowsze</option>
                                <option value="popular" <?= $sortOption === 'popular' ? 'selected' : '' ?>>Najpopularniejsze</option>
                                <option value="alphabetical" <?= $sortOption === 'alphabetical' ? 'selected' : '' ?>>Alfabetycznie</option>
                            </select>
                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>
                    <div style="font-size: 0.8rem; color: #8e8e93; margin-bottom: 16px; display: flex; justify-content: space-between;">
                        <span>Wyniki: <?= $totalUploaded ?> materiałów</span>
                    </div>

                    <?php if (empty($myNotes)): ?>
                        <p class="empty-state" style="color: #8e8e93; text-align: center; padding: 40px 0;">Brak opublikowanych materiałów.</p>
                    <?php else: ?>
                        <table class="saas-table">
                            <thead>
                                <tr>
                                    <th>Tytuł</th>
                                    <th>Typ</th>
                                    <th>Przedmiot</th>
                                    <th>Klasa</th>
                                    <th>Wyświetlenia</th>
                                    <th>Dostęp</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myNotes as $note): 
                                    $subjClass = strtolower($note['subject']);
                                    if (!in_array($subjClass, ['matematyka', 'fizyka', 'biologia', 'chemia', 'geografia', 'historia', 'polski', 'angielski'])) {
                                        $subjClass = 'default';
                                    }
                                    $typeLabel = $note['file_type'] === 'presentation' ? 'Prezentacja' : ($note['file_type'] === 'pdf' ? 'PDF' : 'Zdjęcie');
                                    $typeIcon = $note['file_type'] === 'presentation' ? '🎞' : ($note['file_type'] === 'pdf' ? '📄' : '🖼️');
                                    $accessType = isset($note['access_type']) && $note['access_type'] === 'premium' ? 'premium' : 'free';
                                ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 500; color: #ffffff;">
                                                <?= htmlspecialchars($note['title']) ?>
                                            </div>
                                        </td>
                                        <td><span class="file-pill"><?= $typeIcon ?> <?= $typeLabel ?></span></td>
                                        <td><span class="subject-badge <?= $subjClass ?>"><?= htmlspecialchars($note['subject']) ?></span></td>
                                        <td><?= htmlspecialchars($note['class_level']) ?></td>
                                        <td><?= (int)$note['views'] ?></td>
                                        <td><span class="access-badge <?= $accessType ?>"><?= $accessType === 'premium' ? 'Premium' : 'Free' ?></span></td>
                                        <td>
                                            <div class="dead-actions" aria-label="Akcje materialu">
                                                <a href="watch.php?id=<?= $note['id'] ?>" target="_blank" class="dead-action-btn" title="Podgląd">▶</a>
                                                
                                                <button type="button" class="dead-action-btn like-toggle-btn" data-note-id="<?= $note['id'] ?>" title="Polub">♥</button>
                                                <span class="dead-action-chip likes-count-chip" id="likes-count-<?= $note['id'] ?>" title="Polubienia">♥ <?= (int)$note['likes_count'] ?></span>
                                                
                                                <a href="watch.php?id=<?= $note['id'] ?>#comments" target="_blank" class="dead-action-btn" title="Komentarze">💬</a>
                                                <span class="dead-action-chip" title="Komentarze">💬 <?= (int)$note['note_comments_count'] ?></span>
                                                
                                                <button type="button" class="dead-action-btn share-btn" data-share-url="<?= SecurityEnterprise::isHttps() ? 'https://' : 'http://' ?><?= htmlspecialchars($_SERVER['HTTP_HOST']) ?>/watch.php?id=<?= $note['id'] ?>" title="Udostępnij">↗</button>
                                                
                                                <button type="button" class="dead-action-btn playlist-toggle-btn" data-note-id="<?= $note['id'] ?>" title="Zapisz do playlisty">＋</button>
                                                
                                                <?php if ($note['reports_count'] > 0): ?>
                                                    <a href="report.php" class="dead-action-btn report" style="color:#ff453a; border-color:rgba(255, 69, 58, 0.4); background:rgba(255, 69, 58, 0.1);" title="Zgłoszenia uczniów">⚠️ <?= (int)$note['reports_count'] ?></a>
                                                <?php endif; ?>
                                                
                                                <form action="delete_note.php" method="POST" style="display:inline;">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" style="width: auto; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;">Usuń</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($totalUploaded > $itemsPerPage): ?>
                            <div class="pagination">
                                <?php 
                                    $subjQs = !empty($selectedSubjectFilter) ? '&filter_subject=' . urlencode($selectedSubjectFilter) : '';
                                    $typeQs = !empty($selectedTypeFilter) ? '&filter_type=' . urlencode($selectedTypeFilter) : '';
                                    $baseQs = "?sort=" . htmlspecialchars($sortOption) . $subjQs . $typeQs;
                                ?>
                                <?php for ($page = 1; $page <= ceil($totalUploaded / $itemsPerPage); $page++): ?>
                                    <a href="<?= $baseQs ?>&page=<?= $page ?>" class="pagination-link<?= $page === $currentPage ? ' active' : '' ?>"><?= $page ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        const playlistNotesMap = <?= json_encode($playlistNotesMap) ?>;
        const myPlaylists = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'title' => $p['title']], $myPlaylists)) ?>;
        const csrfToken = <?= json_encode(SecurityEnterprise::csrfToken()) ?>;
    </script>
    <script src="upload.js"></script>
    <script src="dashboard.js"></script>
    <script src="app.js"></script>
</body>
</html>
