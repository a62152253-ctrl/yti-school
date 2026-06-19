<?php
require_once 'db.php';
requireLogin();



$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Fetch all playlists with teacher info and note counts
try {
    $stmt = $pdo->prepare("SELECT p.*, u.username, 
                           (SELECT COUNT(*) FROM playlist_notes WHERE playlist_id = p.id) as notes_count
                           FROM playlists p
                           JOIN users u ON p.user_id = u.id
                           ORDER BY p.created_at DESC");
    $stmt->execute();
    $playlists = $stmt->fetchAll();

    // For each playlist, fetch the first note to use its thumbnail as the playlist cover
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
    $playlists = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlisty - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
    <style>
        /* Playlist Grid Layout */
        .playlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
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
        /* Playlist Overlay Panel 1:1 like YouTube (side bar showing playlist icon and number of items) */
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
        .playlist-desc {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .playlist-author {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
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
                <div class="yt-search-box">
                    <input type="text" name="search" placeholder="Szukaj lekcji, notatek, tagów...">
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
        <?php 
        $activePage = 'playlists.php';
        require_once 'partials/sidebar.php'; 
        ?>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="content-header">
                <h2>Wszystkie Playlisty</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Przeglądaj zbiory i playlisty materiałów utworzone przez nauczycieli</p>
            </header>

            <?php if (empty($playlists)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/>
                    </svg>
                    <h3 style="color:#fff;">Brak utworzonych playlist</h3>
                    <p style="margin-top: 10px;">Zaczekaj, aż nauczyciele utworzą playlisty dla Twoich przedmiotów.</p>
                </div>
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
                                <p class="playlist-author"><?= htmlspecialchars($p['username']) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
