<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('teacher/channel_manager.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';
$teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$teacher_id) {
    redirect('student_dashboard.php');
}

// Check subscription status
$isSubscribed = false;
$subCount = 0;
try {
    $stmtSub = $pdo->prepare("SELECT 1 FROM subscriptions WHERE student_id = ? AND teacher_id = ?");
    $stmtSub->execute([$user_id, $teacher_id]);
    $isSubscribed = (bool)$stmtSub->fetch();

    $stmtSubCount = $pdo->prepare("SELECT COUNT(*) as c FROM subscriptions WHERE teacher_id = ?");
    $stmtSubCount->execute([$teacher_id]);
    $subCount = $stmtSubCount->fetch()['c'] ?? 0;
} catch (\PDOException $e) {}

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}


// Fetch teacher details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND type = 'teacher'");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        redirect('student_dashboard.php');
    }

    // Fetch stats
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as upload_count, SUM(views) as total_views FROM notes WHERE user_id = ?");
    $stmtStats->execute([$teacher_id]);
    $stats = $stmtStats->fetch();
    $uploadCount = $stats['upload_count'] ?? 0;
    $totalViews = $stats['total_views'] ?? 0;

    // Fetch free lessons (not presentation)
    $stmtLessons = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? AND file_type != 'presentation' AND COALESCE(access_type, 'free') = 'free' ORDER BY created_at DESC");
    $stmtLessons->execute([$teacher_id]);
    $lessons = $stmtLessons->fetchAll();

    // Fetch premium lessons package
    $stmtPremium = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? AND file_type != 'presentation' AND access_type = 'premium' ORDER BY created_at DESC");
    $stmtPremium->execute([$teacher_id]);
    $premiumLessons = $stmtPremium->fetchAll();

    // Fetch Presentations
    $stmtPres = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? AND file_type = 'presentation' ORDER BY created_at DESC");
    $stmtPres->execute([$teacher_id]);
    $presentations = $stmtPres->fetchAll();

    // Fetch Playlists
    $stmtPlaylists = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM playlist_notes WHERE playlist_id = p.id) as notes_count 
                                    FROM playlists p WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmtPlaylists->execute([$teacher_id]);
    $playlists = $stmtPlaylists->fetchAll();

    // For playlists, fetch covers
    foreach ($playlists as &$p) {
        $p['cover_url'] = '';
        if ($p['notes_count'] > 0) {
            $stmtCover = $pdo->prepare("SELECT n.filepath, n.file_type FROM playlist_notes pn 
                                        JOIN notes n ON pn.note_id = n.id 
                                        WHERE pn.playlist_id = ? 
                                        ORDER BY pn.position ASC LIMIT 1");
            $stmtCover->execute([$p['id']]);
            $coverNote = $stmtCover->fetch();
            if ($coverNote) {
                if ($coverNote['file_type'] === 'presentation') {
                    $slides = json_decode($coverNote['filepath'], true);
                    $p['cover_url'] = !empty($slides) ? $slides[0] : '';
                } else {
                    $ext = strtolower(pathinfo($coverNote['filepath'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        $p['cover_url'] = $coverNote['filepath'];
                    }
                }
            }
        }
    }
} catch (\PDOException $e) {
    die("Błąd systemu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanał <?= htmlspecialchars($teacher['username']) ?> - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
    <style>
        /* Channel specific styling */
        .channel-banner {
            width: 100%;
            height: 150px;
            background: linear-gradient(90deg, #2c3e50 0%, #0f0f0f 100%);
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid var(--card-border);
        }
        .channel-header-info {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
            padding: 0 8px;
        }
        @media (max-width: 600px) {
            .channel-header-info {
                flex-direction: column;
                text-align: center;
            }
        }
        .channel-big-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #3f3f3f;
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 2px solid #272727;
        }
        .channel-meta-group h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .channel-handle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .channel-stats {
            font-size: 0.88rem;
            color: var(--text-secondary);
        }
        .channel-tab-content {
            display: none;
        }
        .channel-tab-content.active {
            display: block;
        }
        
        /* Playlists inside Channel Grid */
        .playlist-card {
            border: none;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        .playlist-card:hover .playlist-thumbnail-wrapper img {
            transform: scale(1.02);
        }
        .playlist-thumbnail-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            background: #1f1f1f;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }
        .playlist-thumbnail-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }
        .playlist-thumbnail-overlay {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 35%;
            background: rgba(15, 15, 15, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .playlist-info-section {
            padding-top: 10px;
        }
        .playlist-title {
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.35;
            color: #fff;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Topbar Header like YouTube -->
    <header class="yt-header">
        <div class="yt-header-left">
            <a href="student_dashboard.php" class="logo-section">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                </svg>
                <span class="yt-logo-text">yti School</span>
            </a>
        </div>
        <div class="yt-header-center">
            <form action="student_dashboard.php" method="GET" class="yt-search-form">
                <div class="yt-search-box autocomplete-container">
                    <input type="text" name="search" id="ytSearchInput" placeholder="Szukaj lekcji, notatek, tagów..." autocomplete="off">
                    <div class="autocomplete-suggestions" id="ytSearchSuggestions"></div>
                </div>
                <button type="submit" class="yt-search-btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
        </div>
        <div class="yt-header-right">
            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['username']) ?>">
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav style="width: 100%;">
                <ul class="nav-links">
                    <li>
                        <a href="student_dashboard.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Główna
                        </a>
                    </li>
                    <li>
                        <a href="notatki.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Notatki i PDF
                        </a>
                    </li>
                    <li>
                        <a href="prezentacje.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Prezentacje
                        </a>
                    </li>
                    <li>
                        <a href="my_lessons.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            Moje Lekcje
                        </a>
                    </li>
                    <li>
                        <a href="history.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Historia
                        </a>
                    </li>
                    <li>
                        <a href="watch_later.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            Do Obejrzenia
                        </a>
                    </li>
                    <li>
                        <a href="playlists.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/></svg>
                            Playlisty
                        </a>
                    </li>
                </ul>

                <?php if (!empty($sidebar_subs)): ?>
                    <div class="sidebar-section-title" style="padding: 12px 24px 8px 24px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); font-weight: bold;">Subskrypcje</div>
                    <ul class="nav-links">
                        <?php foreach ($sidebar_subs as $sub): ?>
                            <li class="<?= $sub['id'] == $teacher_id ? 'active' : '' ?>">
                                <a href="channel.php?id=<?= $sub['id'] ?>" style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                        <?= strtoupper(substr(htmlspecialchars($sub['username']), 0, 1)) ?>
                                    </div>
                                    <span style="font-weight: normal;"><?= htmlspecialchars($sub['username']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="user-avatar">
                            <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            <span class="user-role-badge">Student &bull; Klasa <?= htmlspecialchars($student_class) ?></span>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn">Wyloguj się</a>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Channel Header Banner -->
            <div class="channel-banner"></div>

            <!-- Channel Header Info Row -->
            <div class="channel-header-info" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div style="display: flex; gap: 24px; align-items: center;">
                    <div class="channel-big-avatar">
                        <?= strtoupper(substr(htmlspecialchars($teacher['username']), 0, 1)) ?>
                    </div>
                    <div class="channel-meta-group">
                        <h1><?= htmlspecialchars($teacher['username']) ?></h1>
                        <div class="channel-handle">@<?= htmlspecialchars(strtolower($teacher['username'])) ?> &bull; <?= htmlspecialchars($teacher['email']) ?></div>
                        <div class="channel-stats"><span id="subCount"><?= $subCount ?></span> subskrybentów &bull; <?= $uploadCount ?> materiałów &bull; <?= (int)$totalViews ?> wyświetleń</div>
                    </div>
                </div>
                <div>
                    <?php if ($teacher_id != $user_id): ?>
                        <button id="subBtn" class="yt-sub-btn <?= $isSubscribed ? 'subscribed' : '' ?>" style="font-size: 1rem; padding: 10px 24px; border-radius: 24px;" data-teacher-id="<?= $teacher_id ?>">
                            <?= $isSubscribed ? 'Subskrybujesz' : 'Subskrybuj' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- YouTube Channel Navigation Tab Headers -->
            <div class="tab-headers">
                <button class="tab-header-btn active" id="btn-lessons-tab" data-tab="lessons-tab">Free</button>
                <button class="tab-header-btn" id="btn-premium-tab" data-tab="premium-tab">Premium</button>
                <button class="tab-header-btn" id="btn-pres-tab" data-tab="pres-tab">Prezentacje</button>
                <button class="tab-header-btn" id="btn-playlists-tab" data-tab="playlists-tab">Playlisty</button>
                <button class="tab-header-btn" id="btn-about-tab" data-tab="about-tab">Informacje</button>
            </div>

            <!-- TAB 1: Lessons List -->
            <div id="lessons-tab" class="channel-tab-content active">
                <?php if (empty($lessons)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Brak opublikowanych lekcji (PDF/obrazów) na tym kanale.</p>
                <?php else: ?>
                    <div class="note-grid">
                        <?php foreach ($lessons as $note): 
                            $ext = strtolower(pathinfo($note['filepath'], PATHINFO_EXTENSION));
                            $thumb = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $note['filepath'] : '';
                        ?>
                            <div class="note-card">
                                <a href="watch.php?id=<?= $note['id'] ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                    <span class="note-badge">Free</span>
                                    <?php if (!empty($thumb)): ?>
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
                                    <?php else: ?>
                                        <div class="note-file-preview">
                                            <div class="note-file-icon">
                                                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Dokument PDF</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="note-details-youtube">
                                    <div class="note-creator-avatar">
                                        <?= strtoupper(substr(htmlspecialchars($teacher['username']), 0, 1)) ?>
                                    </div>
                                    <div class="note-text-group">
                                        <h4 class="note-title">
                                            <a href="watch.php?id=<?= $note['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($note['title']) ?></a>
                                        </h4>
                                        <p class="note-author-name"><?= htmlspecialchars($teacher['username']) ?> &bull; Free</p>
                                        <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyświetleń</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: Premium Lessons Package -->
            <div id="premium-tab" class="channel-tab-content">
                <?php if (empty($premiumLessons)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Ten nauczyciel nie dodal jeszcze notatek premium.</p>
                <?php else: ?>
                    <div class="glass-card" style="padding: 22px; margin-bottom: 24px;">
                        <h3 style="font-weight: 600; color: #fff; margin-bottom: 8px;">Pakiet notatek premium</h3>
                        <p style="color: var(--text-secondary); font-size: 0.92rem;">Platne materialy nauczyciela z dodatkowymi notatkami i opracowaniami.</p>
                    </div>

                    <div class="note-grid">
                        <?php foreach ($premiumLessons as $note): 
                            $ext = strtolower(pathinfo($note['filepath'], PATHINFO_EXTENSION));
                            $thumb = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $note['filepath'] : '';
                            $price = number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ');

                            // determine link: if premium and viewer is not owner and hasn't purchased -> goto paypal mock
                            $href = 'watch.php?id=' . $note['id'];
                            if ((($note['access_type'] ?? 'free') === 'premium') && $teacher_id != $user_id) {
                                try {
                                    $stmtChk = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?");
                                    $stmtChk->execute([$user_id, $note['id']]);
                                    $hasPurchased = (bool)$stmtChk->fetch();
                                } catch (\PDOException $e) { $hasPurchased = false; }
                                if (!$hasPurchased) {
                                    $href = 'paypal_mock.php?note_id=' . $note['id'];
                                }
                            }
                        ?>
                            <div class="note-card">
                                <a href="<?= $href ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                    <span class="note-badge" style="background: #f59e0b; color: #111827;">Premium <?= $price ?> PLN</span>
                                    <?php if (!empty($thumb)): ?>
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
                                    <?php else: ?>
                                        <div class="note-file-preview">
                                            <div class="note-file-icon">
                                                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Dokument PDF</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="note-details-youtube">
                                    <div class="note-creator-avatar">
                                        <?= strtoupper(substr(htmlspecialchars($teacher['username']), 0, 1)) ?>
                                    </div>
                                    <div class="note-text-group">
                                        <h4 class="note-title">
                                            <a href="watch.php?id=<?= $note['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($note['title']) ?></a>
                                        </h4>
                                        <p class="note-author-name"><?= htmlspecialchars($teacher['username']) ?> &bull; Premium</p>
                                        <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyswietlen &bull; <?= $price ?> PLN</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: Presentations List -->
            <div id="pres-tab" class="channel-tab-content">
                <?php if (empty($presentations)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Brak opublikowanych prezentacji na tym kanale.</p>
                <?php else: ?>
                    <div class="note-grid">
                        <?php foreach ($presentations as $note): 
                            $slides = json_decode($note['filepath'], true);
                            $thumb = !empty($slides) ? $slides[0] : '';

                            $href = 'watch.php?id=' . $note['id'];
                            if ((($note['access_type'] ?? 'free') === 'premium') && $teacher_id != $user_id) {
                                try {
                                    $stmtChk = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?");
                                    $stmtChk->execute([$user_id, $note['id']]);
                                    $hasPurchased = (bool)$stmtChk->fetch();
                                } catch (\PDOException $e) { $hasPurchased = false; }
                                if (!$hasPurchased) {
                                    $href = 'paypal_mock.php?note_id=' . $note['id'];
                                }
                            }
                        ?>
                            <div class="note-card">
                                <a href="<?= $href ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                    <span class="note-badge"><?= htmlspecialchars($note['subject']) ?><?= (($note['access_type'] ?? 'free') === 'premium') ? ' • Premium' : '' ?></span>
                                    <?php if (!empty($thumb)): ?>
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
                                    <?php else: ?>
                                        <div class="note-file-preview">
                                            <div class="note-file-icon">
                                                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Prezentacja slajdów</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="note-details-youtube">
                                    <div class="note-creator-avatar">
                                        <?= strtoupper(substr(htmlspecialchars($teacher['username']), 0, 1)) ?>
                                    </div>
                                    <div class="note-text-group">
                                        <h4 class="note-title">
                                            <a href="watch.php?id=<?= $note['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($note['title']) ?></a>
                                        </h4>
                                        <p class="note-author-name"><?= htmlspecialchars($teacher['username']) ?></p>
                                        <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyświetleń</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: Playlists List -->
            <div id="playlists-tab" class="channel-tab-content">
                <?php if (empty($playlists)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Brak utworzonych playlist na tym kanale.</p>
                <?php else: ?>
                    <div class="playlist-grid">
                        <?php foreach ($playlists as $p): ?>
                            <a href="playlist.php?id=<?= $p['id'] ?>" class="playlist-card">
                                <div class="playlist-thumbnail-wrapper">
                                    <?php if (!empty($p['cover_url'])): ?>
                                        <img src="<?= htmlspecialchars($p['cover_url']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                                    <?php else: ?>
                                        <div style="display:flex; justify-content:center; align-items:center; width:100%; height:100%; background: #121212;">
                                            <svg width="40" height="40" fill="none" stroke="var(--text-secondary)" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/></svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="playlist-thumbnail-overlay">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:4px;"><path d="M4 6h16M4 12h16M4 18h12"/></svg>
                                        <span><?= (int)$p['notes_count'] ?></span>
                                    </div>
                                </div>
                                
                                <div class="playlist-info-section">
                                    <h4 class="playlist-title"><?= htmlspecialchars($p['title']) ?></h4>
                                    <p class="playlist-desc"><?= htmlspecialchars($p['description'] ?: 'Brak opisu.') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 4: About Channel -->
            <div id="about-tab" class="channel-tab-content">
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="font-weight:500; font-size:1.15rem; color:#fff; margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:8px;">Opis kanału</h3>
                    <p style="font-size:0.95rem; color: #fff; line-height:1.6; margin-bottom:25px;">
                        Witaj na moim kanale dydaktycznym! Znajdziesz tutaj materiały szkoleniowe, notatki z wykładów oraz prezentacje pogrupowane tematycznie w playlisty. Zapraszam do nauki.
                    </p>

                    <h3 style="font-weight:500; font-size:1.15rem; color:#fff; margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:8px;">Statystyki kanału</h3>
                    <ul style="list-style:none; display:flex; flex-direction:column; gap:12px; font-size:0.92rem; color:var(--text-secondary);">
                        <li>Data dołączenia do Yti School: <strong style="color:#fff;"><?= substr($teacher['created_at'], 0, 10) ?></strong></li>
                        <li>Łączna liczba wgranych materiałów lekcyjnych: <strong style="color:#fff;"><?= $uploadCount ?></strong></li>
                        <li>Łączna liczba wyświetleń wszystkich lekcji: <strong style="color:#fff;"><?= (int)$totalViews ?></strong></li>
                        <li>Email kontaktowy: <strong style="color:#fff;"><?= htmlspecialchars($teacher['email']) ?></strong></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        const csrfToken = '<?= SecurityEnterprise::csrfToken() ?>';

        function switchChannelTab(tabId) {
            document.querySelectorAll('.channel-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-header-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            document.getElementById('btn-' + tabId).classList.add('active');
        }

        // Subscription Toggle
        function toggleSubscription(teacherId) {
            fetch('toggle_sub.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `teacher_id=${teacherId}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const subBtn = document.getElementById('subBtn');
                        const subCount = document.getElementById('subCount');
                        let countVal = parseInt(subCount.textContent) || 0;

                        if (data.subscribed) {
                            subBtn.classList.add('subscribed');
                            subBtn.textContent = 'Subskrybujesz';
                            subCount.textContent = countVal + 1;
                        } else {
                            subBtn.classList.remove('subscribed');
                            subBtn.textContent = 'Subskrybuj';
                            subCount.textContent = Math.max(0, countVal - 1);
                        }
                    } else {
                        alert(data.message || 'Wystąpił błąd.');
                    }
                });
        }

        // Header search Autocomplete logic
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('ytSearchInput');
            const suggestionsBox = document.getElementById('ytSearchSuggestions');

            if (!searchInput || !suggestionsBox) return;

            searchInput.addEventListener('input', function() {
                const query = searchInput.value.trim();
                if (query.length < 1) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                    return;
                }

                fetch('search_suggestions.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            suggestionsBox.innerHTML = '';
                            suggestionsBox.style.display = 'none';
                            return;
                        }

                        suggestionsBox.innerHTML = '';
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-suggestion';
                            div.innerHTML = `
                                <div class="autocomplete-suggestion-icon">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <div style="flex-grow: 1;">${item.title}</div>
                                <span style="font-size: 0.72rem; opacity: 0.6; text-transform: uppercase;">${item.subject}</span>
                            `;
                            div.addEventListener('click', function() {
                                window.location.href = 'watch.php?id=' + item.id;
                            });
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    });
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });

            // Subscription and Tabs
            const subBtn = document.getElementById('subBtn');
            if (subBtn) {
                subBtn.addEventListener('click', () => {
                    const teacherId = subBtn.getAttribute('data-teacher-id');
                    toggleSubscription(teacherId);
                });
            }

            document.querySelectorAll('.tab-header-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.getAttribute('data-tab');
                    switchChannelTab(tabId);
                });
            });
        });
    </script>
</body>
</html>
