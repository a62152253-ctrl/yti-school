<?php
require_once 'db.php';
requireLogin();



$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$playlist_id) {
    redirect('playlists.php');
}

// Fetch playlist details
try {
    $stmt = $pdo->prepare("SELECT p.*, u.username 
                           FROM playlists p
                           JOIN users u ON p.user_id = u.id
                           WHERE p.id = ?");
    $stmt->execute([$playlist_id]);
    $playlist = $stmt->fetch();

    if (!$playlist) {
        redirect('playlists.php');
    }

    // Fetch notes inside this playlist
    $stmtNotes = $pdo->prepare("SELECT n.*, u.username FROM playlist_notes pn 
                                JOIN notes n ON pn.note_id = n.id 
                                JOIN users u ON n.user_id = u.id
                                WHERE pn.playlist_id = ? 
                                ORDER BY pn.position ASC, n.created_at ASC");
    $stmtNotes->execute([$playlist_id]);
    $notes = $stmtNotes->fetchAll();

    // Determine cover url from first note
    $cover_url = '';
    if (!empty($notes)) {
        $firstNote = $notes[0];
        if ($firstNote['file_type'] === 'presentation') {
            $slides = json_decode($firstNote['filepath'], true);
            $cover_url = !empty($slides) ? $slides[0] : '';
        } else {
            $ext = strtolower(pathinfo($firstNote['filepath'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $cover_url = $firstNote['filepath'];
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
    <title><?= htmlspecialchars($playlist['title']) ?> - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
    <style>
        .playlist-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        @media (max-width: 900px) {
            .playlist-layout {
                grid-template-columns: 1fr;
            }
        }
        /* YouTube style Playlist Left Banner */
        .playlist-cover-panel {
            background: linear-gradient(180deg, rgba(40, 40, 40, 0.95) 0%, rgba(20, 20, 20, 0.95) 100%);
            border-radius: 24px;
            padding: 24px;
            height: fit-content;
            border: 1px solid var(--card-border);
            position: sticky;
            top: 80px;
        }
        .playlist-cover-img {
            width: 100%;
            padding-top: 56.25%;
            position: relative;
            background: #121212;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }
        .playlist-cover-img img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .playlist-cover-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        /* Right Side Playlist Items List */
        .playlist-items-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .playlist-item-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 12px;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.15s ease;
        }
        .playlist-item-row:hover {
            background: #272727;
        }
        .playlist-item-index {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 700;
            width: 20px;
            text-align: center;
        }
        .playlist-item-thumb {
            width: 120px;
            height: 68px;
            border-radius: 8px;
            overflow: hidden;
            background: #1f1f1f;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
            position: relative;
        }
        .playlist-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .playlist-item-thumb-preview {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-secondary);
        }
        .playlist-item-details {
            flex-grow: 1;
            overflow: hidden;
        }
        .playlist-item-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .playlist-item-meta {
            font-size: 0.78rem;
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
            <div class="playlist-layout">
                <!-- Left: Playlist Info Cover Banner -->
                <div class="playlist-cover-panel">
                    <div class="playlist-cover-img">
                        <?php if (!empty($cover_url)): ?>
                            <img src="<?= htmlspecialchars($cover_url) ?>" alt="Cover">
                        <?php else: ?>
                            <div class="playlist-cover-placeholder">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 style="font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 8px;"><?= htmlspecialchars($playlist['title']) ?></h2>
                    <div style="font-size: 0.85rem; color: #fff; opacity: 0.8; margin-bottom: 6px;">Nauczyciel: <strong><a href="channel.php?id=<?= $playlist['user_id'] ?>" style="color: #3ea6ff; text-decoration: none;"><?= htmlspecialchars($playlist['username']) ?></a></strong></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 20px;"><?= count($notes) ?> lekcji &bull; Ostatnia zmiana: <?= substr($playlist['created_at'], 0, 10) ?></div>
                    <p style="font-size: 0.88rem; color: #fff; line-height: 1.5; margin-bottom: 25px;"><?= htmlspecialchars($playlist['description'] ?: 'Brak opisu dla tej playlisty.') ?></p>

                    <?php if (!empty($notes)): ?>
                        <a href="watch.php?id=<?= $notes[0]['id'] ?>&playlist_id=<?= $playlist['id'] ?>" class="btn btn-primary" style="border-radius: 24px; text-transform: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Odtwórz wszystko
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Right: Playlist Items List -->
                <div class="playlist-items-list">
                    <?php if (empty($notes)): ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary); background: #1f1f1f; border-radius: 12px; border: 1px solid var(--card-border);">
                            Brak lekcji w tej playliście.
                        </div>
                    <?php else: 
                        $idx = 1;
                        foreach ($notes as $n): 
                            $isItemPres = $n['file_type'] === 'presentation';
                            $itemThumb = '';
                            if ($isItemPres) {
                                $slides = json_decode($n['filepath'], true);
                                $itemThumb = !empty($slides) ? $slides[0] : '';
                            } else {
                                $ext = strtolower(pathinfo($n['filepath'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                    $itemThumb = $n['filepath'];
                                }
                            }
                    ?>
                        <a href="watch.php?id=<?= $n['id'] ?>&playlist_id=<?= $playlist['id'] ?>" class="playlist-item-row">
                            <span class="playlist-item-index"><?= $idx++ ?></span>
                            
                            <div class="playlist-item-thumb">
                                <?php if (!empty($itemThumb)): ?>
                                    <img src="<?= htmlspecialchars($itemThumb) ?>" alt="<?= htmlspecialchars($n['title']) ?>">
                                <?php else: ?>
                                    <div class="playlist-item-thumb-preview">
                                        <?php if ($n['file_type'] === 'presentation'): ?>
                                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <?php else: ?>
                                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="note-badge"><?= htmlspecialchars($n['subject']) ?></span>
                            </div>

                            <div class="playlist-item-details">
                                <div class="playlist-item-title"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="playlist-item-meta"><?= htmlspecialchars($n['username']) ?> &bull; <?= (int)$n['views'] ?> wyświetleń</div>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
