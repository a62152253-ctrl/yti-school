<?php
require_once 'db.php';
require_once 'includes/helpers.php';
requireLogin();
requireAdmin();

$user_id = $_SESSION['user_id'];

// Secure document viewer for admins
if (isset($_GET['view_doc'])) {
    $target_user_id = (int)$_GET['view_doc'];
    $stmtDoc = $pdo->prepare("SELECT verification_document FROM users WHERE id = ?");
    $stmtDoc->execute([$target_user_id]);
    $doc = $stmtDoc->fetch();
    if ($doc && !empty($doc['verification_document'])) {
        $filePath = APP_ROOT . '/' . ltrim($doc['verification_document'], '/');
        if (file_exists($filePath)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath) ?: 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
            readfile($filePath);
            exit;
        }
    }
    http_response_code(404);
    die("Dokument weryfikacyjny nie został odnaleziony.");
}

// Handling POST actions (Admin operations)
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'delete_user') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            if ($target_user_id === 999999) {
                throw new Exception("Nie można usunąć głównego konta administratora.");
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $success_msg = "Użytkownik został usunięty pomyślnie.";
        }
        
        elseif ($action === 'edit_user') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $class_level = trim($_POST['class_level'] ?? '');
            
            if (empty($username) || empty($email)) {
                throw new Exception("Nazwa użytkownika oraz e-mail nie mogą być puste.");
            }
            
            if ($target_user_id === 999999) {
                throw new Exception("Nie można edytować konta systemowego administratora.");
            }
            
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, class_level = ? WHERE id = ?");
            $stmt->execute([$username, $email, $class_level ?: null, $target_user_id]);
            $success_msg = "Dane użytkownika zostały zaktualizowane.";
        }
        
        elseif ($action === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $type = $_POST['type'] ?? 'student';
            $class_level = trim($_POST['class_level'] ?? '');
            
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception("Nazwa użytkownika, e-mail oraz hasło są wymagane.");
            }
            if (!in_array($type, ['student', 'teacher'])) {
                throw new Exception("Nieprawidłowy typ użytkownika.");
            }
            
            // Check check
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Użytkownik o takim loginie lub e-mailu już istnieje.");
            }
            
            $hashed = SecurityEnterprise::hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, type, class_level, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
            $is_verified = ($type === 'teacher') ? 1 : 0;
            $stmt->execute([$username, $email, $hashed, $type, $class_level ?: null, $is_verified]);
            $success_msg = "Pomyślnie utworzono nowego użytkownika.";
        }
        
        elseif ($action === 'approve_teacher') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $success_msg = "Nauczyciel został zweryfikowany pomyślnie.";
        }
        
        elseif ($action === 'reject_teacher') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            
            // Delete document
            $stmtDoc = $pdo->prepare("SELECT verification_document FROM users WHERE id = ?");
            $stmtDoc->execute([$target_user_id]);
            $doc = $stmtDoc->fetch();
            if ($doc && !empty($doc['verification_document'])) {
                $filePath = APP_ROOT . '/' . ltrim($doc['verification_document'], '/');
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 0, verification_document = NULL WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $success_msg = "Weryfikacja została odrzucona, a dokument usunięty.";
        }
        
        elseif ($action === 'add_school_code') {
            $code = trim($_POST['code'] ?? '');
            $school_name = trim($_POST['school_name'] ?? '');
            
            if (empty($code) || empty($school_name)) {
                throw new Exception("Kod i nazwa szkoły nie mogą być puste.");
            }
            
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO school_codes (code, school_name) VALUES (?, ?)");
            $stmt->execute([$code, $school_name]);
            $success_msg = "Kod szkoły został dodany/zaktualizowany.";
        }
        
        elseif ($action === 'delete_school_code') {
            $code = $_POST['code'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM school_codes WHERE code = ?");
            $stmt->execute([$code]);
            $success_msg = "Kod szkoły został usunięty.";
        }
        
        elseif ($action === 'change_user_type') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $new_type = $_POST['new_type'] ?? '';
            if (!in_array($new_type, ['student', 'teacher'])) {
                throw new Exception("Nieprawidłowy typ użytkownika.");
            }
            $stmt = $pdo->prepare("UPDATE users SET type = ? WHERE id = ?");
            $stmt->execute([$new_type, $target_user_id]);
            $success_msg = "Typ użytkownika został zaktualizowany.";
        }
        
        elseif ($action === 'toggle_verification') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $verify = (int)($_POST['verify'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
            $stmt->execute([$verify, $target_user_id]);
            $success_msg = $verify ? "Nauczyciel został zweryfikowany." : "Weryfikacja nauczyciela została cofnięta.";
        }
        
        elseif ($action === 'toggle_student_creator') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $creator = (int)($_POST['creator'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET is_student_creator = ? WHERE id = ?");
            $stmt->execute([$creator, $target_user_id]);
            $success_msg = $creator ? "Uprawnienia twórcy studenckiego zostały przyznane." : "Uprawnienia twórcy studenckiego zostały cofnięte.";
        }
        
        elseif ($action === 'delete_material') {
            $note_id = (int)($_POST['note_id'] ?? 0);
            $stmtFile = $pdo->prepare("SELECT filepath FROM notes WHERE id = ?");
            $stmtFile->execute([$note_id]);
            $note = $stmtFile->fetch();
            
            if ($note && !empty($note['filepath']) && file_exists($note['filepath'])) {
                @unlink($note['filepath']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$note_id]);
            $success_msg = "Materiał dydaktyczny został usunięty.";
        }
        
        elseif ($action === 'update_premium') {
            $note_id = (int)($_POST['note_id'] ?? 0);
            $access_type = $_POST['access_type'] ?? 'free';
            $premium_price = (float)($_POST['premium_price'] ?? 0.0);
            
            if (!in_array($access_type, ['free', 'premium'])) {
                throw new Exception("Nieprawidłowy typ dostępu.");
            }
            
            $stmt = $pdo->prepare("UPDATE notes SET access_type = ?, premium_price = ? WHERE id = ?");
            $stmt->execute([$access_type, $premium_price, $note_id]);
            $success_msg = "Ustawienia dostępu premium zostały zaktualizowane.";
        }
        
        elseif ($action === 'dismiss_report') {
            $report_id = (int)($_POST['report_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);
            $success_msg = "Zgłoszenie zostało odrzucone.";
        }
        
        elseif ($action === 'delete_reported_material') {
            $report_id = (int)($_POST['report_id'] ?? 0);
            $note_id = (int)($_POST['note_id'] ?? 0);
            
            $stmtFile = $pdo->prepare("SELECT filepath FROM notes WHERE id = ?");
            $stmtFile->execute([$note_id]);
            $note = $stmtFile->fetch();
            if ($note && !empty($note['filepath']) && file_exists($note['filepath'])) {
                @unlink($note['filepath']);
            }
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$note_id]);
            
            $stmtRep = $pdo->prepare("DELETE FROM reports WHERE note_id = ?");
            $stmtRep->execute([$note_id]);
            
            $success_msg = "Materiał dydaktyczny i powiązane zgłoszenia zostały usunięte.";
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Fetch totals for badge counts
$totalReports = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();

// Active tab helper
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel Administratora - Yti School') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
    <script src="security.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.08), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.04), transparent 40%),
                        #070a13;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
        }
        .admin-navbar {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            font-size: 1.35rem;
        }
        .admin-nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .admin-main-wrapper {
            max-width: 1440px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .admin-header-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(168, 85, 247, 0.04) 50%, rgba(16, 185, 129, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 32px;
            border-radius: 24px;
            margin-bottom: 30px;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 8px;
            overflow-x: auto;
        }
        .admin-tab-btn {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            text-decoration: none;
            display: inline-block;
        }
        .admin-tab-btn.active {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.25);
        }
        .admin-tab-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
        }
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .search-input, .filter-select {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f1f5f9;
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }
        .search-input { width: 320px; }
        .search-input:focus, .filter-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }
        .badge-admin {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.03em;
        }
        .badge-admin.admin { background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-admin.teacher { background: rgba(59, 130, 246, 0.12); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); }
        .badge-admin.student { background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-admin.verified { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
        .badge-admin.pending-verification {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.2);
        }
        .badge-admin.unverified { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.2); }
        
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .pulse-amber {
            animation: pulseGlow 2s infinite;
        }

        .sortable-header {
            cursor: pointer;
            position: relative;
            user-select: none;
            transition: color 0.2s;
        }
        .sortable-header:hover {
            color: #fff !important;
        }
        .sort-icon {
            font-size: 0.75rem;
            margin-left: 6px;
            opacity: 0.6;
        }

        /* Modal System */
        .admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(3, 7, 18, 0.65);
            backdrop-filter: blur(12px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .admin-modal.active {
            display: flex;
        }
        .admin-modal-card {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .admin-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 14px;
        }
        .admin-modal-close {
            cursor: pointer;
            font-size: 1.35rem;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .admin-modal-close:hover { color: #fff; }

        .btn-logout {
            text-decoration: none;
            color: #f87171;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 9px 18px;
            border-radius: 10px;
            transition: all 0.2s;
            background: rgba(239, 68, 68, 0.02);
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.4);
        }
        
        .audit-log-box {
            background: rgba(3, 7, 18, 0.4);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 18px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.82rem;
            color: #34d399;
            max-height: 280px;
            overflow-y: auto;
        }
        .audit-log-line {
            margin-bottom: 8px;
            line-height: 1.5;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            padding-bottom: 6px;
        }
        .audit-log-line:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .btn-add {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-add:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .saas-table-wrapper {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            overflow: hidden;
        }
        .saas-table th {
            background: rgba(30, 41, 59, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px 20px;
            font-weight: 600;
        }
        .saas-table td {
            padding: 14px 20px;
            vertical-align: middle;
        }
        .saas-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
    </style>
</head>
<body>

    <!-- Standalone Admin Navbar -->
    <nav class="admin-navbar">
        <a href="admin_dashboard.php" class="admin-logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
            </svg>
            <span>yti School <span style="font-weight: 300; font-size: 0.9rem; color: #8e8e93;">ADMIN</span></span>
        </a>
        <div class="admin-nav-right">
            <span class="badge-admin admin">Administrator</span>
            <a href="logout.php" class="btn-logout">Wyloguj panel</a>
        </div>
    </nav>

    <!-- Standalone Main Wrapper -->
    <div class="admin-main-wrapper">
        <!-- Header -->
        <div class="admin-header-card animate__animated animate__fadeIn">
            <h1 style="font-size: 2.2rem; font-weight: 800; color: #fff; margin: 0 0 6px 0; letter-spacing:-0.03em;">Panel Kontrolny SaaS</h1>
            <p style="color: #8e8e93; margin: 0; font-size: 0.98rem; line-height: 1.5;">Przeglądaj statystyki szkoły, zarządzaj uprawnieniami, weryfikuj nauczycieli i kontroluj finanse.</p>
        </div>

        <!-- Feedback alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success animate__animated animate__fadeIn" style="margin-bottom: 20px;">
                ✓ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn" style="margin-bottom: 20px;">
                ⚠ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="admin-tabs">
            <a href="admin_dashboard.php" class="admin-tab-btn <?= ($current_page === 'admin_dashboard.php') ? 'active' : '' ?>">Kokpit (Dashboard)</a>
            <a href="admin_users.php" class="admin-tab-btn <?= ($current_page === 'admin_users.php') ? 'active' : '' ?>">Użytkownicy</a>
            <a href="admin_materials.php" class="admin-tab-btn <?= ($current_page === 'admin_materials.php') ? 'active' : '' ?>">Materiały dydaktyczne</a>
            <a href="admin_reports.php" class="admin-tab-btn <?= ($current_page === 'admin_reports.php') ? 'active' : '' ?>">Zgłoszenia (<?= $totalReports ?>)</a>
            <a href="admin_payments.php" class="admin-tab-btn <?= ($current_page === 'admin_payments.php') ? 'active' : '' ?>">Transakcje i zakupy</a>
            <a href="admin_school_codes.php" class="admin-tab-btn <?= ($current_page === 'admin_school_codes.php') ? 'active' : '' ?>">Kody Szkół</a>
        </div>
