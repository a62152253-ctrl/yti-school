<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$note_id) {
    redirect(isTeacher() ? 'dashboard.php' : 'student_dashboard.php');
}

// 1. Fetch note
try {
    $stmt = $pdo->prepare("SELECT n.*, u.username, 
                           (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                           FROM notes n 
                           JOIN users u ON n.user_id = u.id 
                           WHERE n.id = ?");
    $stmt->execute([$user_id, $note_id]);
    $note = $stmt->fetch();

    if (!$note) {
        redirect(isTeacher() ? 'dashboard.php' : 'student_dashboard.php');
    }

    // If note is premium and current user is not owner and user hasn't purchased it, redirect to mock PayPal
    if ((($note['access_type'] ?? 'free') === 'premium') && $note['user_id'] != $user_id) {
        try {
            $stmtPur = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?");
            $stmtPur->execute([$user_id, $note_id]);
            $hasPurchased = (bool)$stmtPur->fetch();
            if (!$hasPurchased) {
                redirect('paypal_mock.php?note_id=' . $note_id);
            }
        } catch (\PDOException $e) {
            // ignore and continue to prevent blocking user on DB error
        }
    }

    // 2. Increment view count
    $stmt = $pdo->prepare("UPDATE notes SET views = views + 1 WHERE id = ?");
    $stmt->execute([$note_id]);

    // 3. Add to Watch History
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO history (user_id, note_id, watched_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$user_id, $note_id]);

    // 4. Fetch Recommended notes
    $stmt = $pdo->prepare("SELECT n.*, u.username FROM notes n 
                           JOIN users u ON n.user_id = u.id 
                           WHERE n.id != ? AND (n.subject = ? OR n.user_id = ?) 
                           ORDER BY n.views DESC LIMIT 10");
    $stmt->execute([$note_id, $note['subject'], $note['user_id']]);
    $recommended = $stmt->fetchAll();

    if (count($recommended) < 4) {
        $stmt = $pdo->prepare("SELECT n.*, u.username FROM notes n 
                               JOIN users u ON n.user_id = u.id 
                               WHERE n.id != ? 
                               ORDER BY n.created_at DESC LIMIT 10");
        $stmt->execute([$note_id]);
        $recommended = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    die("Błąd systemu: " . $e->getMessage());
}

// Parse playlist information if active
$playlist_id = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
$playlistNotes = [];
$playlistInfo = null;
$currentIndex = -1;
$next_note_id = 0;
$prev_note_id = 0;

if ($playlist_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.username FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->execute([$playlist_id]);
        $playlistInfo = $stmt->fetch();

        if ($playlistInfo) {
            $stmtNotes = $pdo->prepare("SELECT n.*, u.username FROM playlist_notes pn 
                                        JOIN notes n ON pn.note_id = n.id 
                                        JOIN users u ON n.user_id = u.id
                                        WHERE pn.playlist_id = ? 
                                        ORDER BY pn.position ASC, n.created_at ASC");
            $stmtNotes->execute([$playlist_id]);
            $playlistNotes = $stmtNotes->fetchAll();

            foreach ($playlistNotes as $idx => $pn) {
                if ($pn['id'] == $note_id) {
                    $currentIndex = $idx;
                    break;
                }
            }

            if ($currentIndex !== -1) {
                if (isset($playlistNotes[$currentIndex + 1])) {
                    $next_note_id = $playlistNotes[$currentIndex + 1]['id'];
                }
                if (isset($playlistNotes[$currentIndex - 1])) {
                    $prev_note_id = $playlistNotes[$currentIndex - 1]['id'];
                }
            }
        }
    } catch (\PDOException $e) {}
}

// Parse presentation slides if needed
$slides = [];
if ($note['file_type'] === 'presentation') {
    $decoded = json_decode($note['filepath'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $slides = $decoded;
    } else {
        $slides = [$note['filepath']];
    }
}

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}

// Check subscription status
$isSubscribed = false;
$subCount = 0;
try {
    $stmtSub = $pdo->prepare("SELECT 1 FROM subscriptions WHERE student_id = ? AND teacher_id = ?");
    $stmtSub->execute([$user_id, $note['user_id']]);
    $isSubscribed = (bool)$stmtSub->fetch();

    $stmtSubCount = $pdo->prepare("SELECT COUNT(*) as c FROM subscriptions WHERE teacher_id = ?");
    $stmtSubCount->execute([$note['user_id']]);
    $subCount = $stmtSubCount->fetch()['c'] ?? 0;
} catch (\PDOException $e) {}

// Check Likes/Dislikes
$likeCount = 0;
$dislikeCount = 0;
$userLikeType = null;
try {
    $stmtLikeCount = $pdo->prepare("SELECT COUNT(*) as c FROM likes WHERE note_id = ? AND type = 'like'");
    $stmtLikeCount->execute([$note_id]);
    $likeCount = $stmtLikeCount->fetch()['c'] ?? 0;

    $stmtDislikeCount = $pdo->prepare("SELECT COUNT(*) as c FROM likes WHERE note_id = ? AND type = 'dislike'");
    $stmtDislikeCount->execute([$note_id]);
    $dislikeCount = $stmtDislikeCount->fetch()['c'] ?? 0;

    $stmtUserLike = $pdo->prepare("SELECT type FROM likes WHERE user_id = ? AND note_id = ?");
    $stmtUserLike->execute([$user_id, $note_id]);
    $userLike = $stmtUserLike->fetch();
    $userLikeType = $userLike ? $userLike['type'] : null;
} catch (\PDOException $e) {}

// Check Watch Later
$isWatchLater = false;
try {
    $stmtWatchLater = $pdo->prepare("SELECT 1 FROM watch_later WHERE user_id = ? AND note_id = ?");
    $stmtWatchLater->execute([$user_id, $note_id]);
    $isWatchLater = (bool)$stmtWatchLater->fetch();
} catch (\PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($note['title']) ?> - Yti School</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('styleapp.css')) ?>">
    <style>
        .watch-description-box.expanded {
            cursor: default;
        }
        .watch-description-box.expanded .watch-description-text {
            display: block;
        }
        .watch-description-box.expanded .show-more-btn {
            display: none;
        }
        .show-more-btn {
            color: #ffffff;
            font-weight: 700;
            font-size: 0.8rem;
            margin-top: 8px;
            display: inline-block;
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
        <?php 
        $activePage = $note['file_type'] === 'presentation' ? 'prezentacje.php' : 'notatki.php';
        require_once 'partials/sidebar.php'; 
        ?>

        <!-- Main Watch Space -->
        <main class="main-content">
            <div class="watch-container" id="watchContainer">
                <!-- Left column: Player + Meta + Comments -->
                <div class="watch-player-section">
                    <!-- Reading Theme and Autoplay Toolbar -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <?php if ($note['file_type'] === 'presentation' && count($slides) > 0): ?>
                            <div class="autoplay-panel">
                                <button id="playPauseBtn" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:700; font-family:inherit; font-size:0.75rem; display:flex; align-items:center; gap:5px;">
                                    <span>▶</span> Autoodtwarzanie
                                </button>
                                <span style="color:var(--text-secondary);">co</span>
                                <select id="autoplaySpeed" style="background:#1c1c1e; border:1px solid var(--card-border); color:#fff; font-size:0.75rem; border-radius:4px; padding:2px; font-family:inherit; outline:none; cursor:pointer;">
                                    <option value="3000">3 sekundy</option>
                                    <option value="5000" selected>5 sekund</option>
                                    <option value="10000">10 sekund</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <div class="theme-selector-pill">
                            <button class="theme-option-btn active" data-theme="dark">Ciemny</button>
                            <button class="theme-option-btn" data-theme="sepia">Sepia</button>
                            <button class="theme-option-btn" data-theme="light">Jasny</button>
                        </div>
                    </div>

                    <!-- Slideshow or standard Player -->
                    <div class="slides-player-wrapper" id="playerWrapper">
                        <div class="slide-content-frame" id="slideFrame">
                            <?php if ($note['file_type'] === 'presentation'): ?>
                                <!-- Handled dynamically by JS below -->
                            <?php elseif (in_array(strtolower(pathinfo($note['filepath'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                <img src="<?= htmlspecialchars($note['filepath']) ?>" alt="<?= htmlspecialchars($note['title']) ?>">
                            <?php else: ?>
                                <iframe src="<?= htmlspecialchars($note['filepath']) ?>"></iframe>
                            <?php endif; ?>
                        </div>

                        <!-- Slideshow Overlay Controls (Only for type 'presentation') -->
                        <?php if ($note['file_type'] === 'presentation' && count($slides) > 0): ?>
                            <div class="slides-controls-overlay">
                                <div class="slides-controls-left">
                                    <button class="slides-btn" id="prevSlideBtn" title="Poprzedni slajd">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button class="slides-btn" id="nextSlideBtn" title="Następny slajd">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <span class="slide-counter-badge"><span id="currentSlideNum">1</span> / <?= count($slides) ?></span>
                                </div>
                                <div class="slides-controls-right">
                                    <button class="slides-btn" id="fullscreenBtn" title="Pełny ekran">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 8V4h4m12 4V4h-4M4 16v4h4m12-4v4h-4"/></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Lesson Meta Title / Stats -->
                    <div class="watch-meta-info">
                        <h1 class="watch-title"><?= htmlspecialchars($note['title']) ?></h1>
                        <?php if (($note['access_type'] ?? 'free') === 'premium'): ?>
                            <div style="display: inline-flex; align-items: center; gap: 8px; margin: 10px 0 14px; padding: 8px 14px; border-radius: 18px; background: rgba(245, 158, 11, 0.16); color: #fbbf24; font-weight: 700;">
                                Premium package &bull; <?= number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ') ?> PLN
                            </div>
                        <?php endif; ?>
                        
                        <div class="watch-meta-row">
                            <div class="watch-channel-group">
                                <a href="channel.php?id=<?= $note['user_id'] ?>" class="watch-channel-avatar" style="text-decoration: none;">
                                    <?= strtoupper(substr(htmlspecialchars($note['username']), 0, 1)) ?>
                                </a>
                                <div>
                                    <div class="watch-channel-name"><a href="channel.php?id=<?= $note['user_id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($note['username']) ?></a></div>
                                    <div class="watch-channel-sub"><span id="subCount"><?= $subCount ?></span> subskrybentów</div>
                                </div>
                                <?php if ($note['user_id'] != $user_id): ?>
                                    <button id="subBtn" class="yt-sub-btn <?= $isSubscribed ? 'subscribed' : '' ?>" data-teacher-id="<?= $note['user_id'] ?>">
                                        <?= $isSubscribed ? 'Subskrybujesz' : 'Subskrybuj' ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="watch-actions">
                                <span class="subject-badge default" style="align-self: center; margin-right: 8px; font-weight: 500; font-size: 0.8rem; background:#272727; color:#f1f1f1; border-radius:18px; padding:8px 16px;">
                                    <?= htmlspecialchars($note['subject']) ?>
                                </span>

                                <?php if ($prev_note_id > 0): ?>
                                    <a href="watch.php?id=<?= $prev_note_id ?>&playlist_id=<?= $playlist_id ?>" class="watch-action-btn" title="Poprzednia lekcja z playlisty">
                                        &larr; Poprzednia
                                    </a>
                                <?php endif; ?>
                                <?php if ($next_note_id > 0): ?>
                                    <a href="watch.php?id=<?= $next_note_id ?>&playlist_id=<?= $playlist_id ?>" class="watch-action-btn" title="Następna lekcja z playlisty">
                                        Następna &rarr;
                                    </a>
                                <?php endif; ?>

                                <!-- Like/Dislike Pill -->
                                <div class="like-dislike-wrapper">
                                    <button class="like-btn <?= $userLikeType === 'like' ? 'active' : '' ?>" id="likeBtn">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>
                                        <span id="likeCount"><?= $likeCount ?></span>
                                    </button>
                                    <div class="like-dislike-divider"></div>
                                    <button class="dislike-btn <?= $userLikeType === 'dislike' ? 'active' : '' ?>" id="dislikeBtn">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h3a2 2 0 012 2v7a2 2 0 01-2 2h-3"/></svg>
                                        <span id="dislikeCount"><?= $dislikeCount ?></span>
                                    </button>
                                </div>

                                <button id="watchLaterBtn" class="watch-action-btn <?= $isWatchLater ? 'active' : '' ?>">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    <span id="watchLaterText"><?= $isWatchLater ? 'Do Obejrzenia (Zapisano)' : 'Do Obejrzenia' ?></span>
                                </button>
                                
                                <button id="bookmarkBtn" class="watch-action-btn <?= (int)$note['is_bookmarked'] === 1 ? 'active' : '' ?>">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                    <span><?= (int)$note['is_bookmarked'] === 1 ? 'Zapisano' : 'Zapisz' ?></span>
                                </button>

                                <button id="toggleNotepadBtn" class="watch-action-btn" title="Otwórz osobisty notatnik naukowy">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Notatnik</span>
                                </button>

                                <a href="file_report.php?id=<?= $note['id'] ?>" id="reportBtn" class="watch-action-btn" style="text-decoration:none;">
                                    Zgłoś
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- YouTube style Description Box -->
                    <div class="watch-description-box" id="descriptionBox">
                        <div class="watch-description-meta"><?= (int)$note['views'] ?> wyświetleń &bull; Opublikowano: <?= substr($note['created_at'], 0, 10) ?></div>
                        <div class="watch-description-text" id="descriptionText"><?= htmlspecialchars($note['description'] ?: 'Brak opisu dla tej lekcji.') ?></div>
                        <div class="show-more-btn" id="showMoreBtn">Pokaż więcej</div>
                    </div>

                    <!-- Comments Section -->
                    <section class="comments-section">
                        <div class="comments-count-title" id="commentsCountTitle">Komentarze</div>

                        <!-- Comment input area -->
                        <div class="comment-input-area">
                            <div class="user-avatar">
                                <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
                            </div>
                            <div class="comment-field-container">
                                <input type="text" id="commentText" class="comment-field" placeholder="Dodaj komentarz...">
                                <div class="comment-form-actions" id="commentActions">
                                    <button id="cancelCommentBtn" class="btn btn-secondary" style="width:auto; padding:6px 12px; font-size:0.8rem; border-radius:18px;">Anuluj</button>
                                    <button id="submitCommentBtn" class="btn btn-primary" style="width:auto; padding:6px 12px; font-size:0.8rem; border-radius:18px;">Skomentuj</button>
                                </div>
                            </div>
                        </div>

                        <!-- Comments List -->
                        <div class="comments-list" id="commentsList">
                            <!-- Loaded via Ajax -->
                        </div>
                    </section>
                </div>

                <!-- Middle column: Personal Notebook (hidden by default) -->
                <div class="notepad-panel" id="notepadPanel" style="display: none; width: 340px; flex-shrink: 0;">
                    <div class="notepad-header">
                        <h3>Mój Notatnik</h3>
                        <span class="notepad-status" id="notepadStatus">Wczytywanie...</span>
                    </div>
                    <textarea class="notepad-textarea" id="notepadText" placeholder="Zapisuj swoje notatki na bieżąco podczas oglądania lekcji..."></textarea>
                    <div class="notepad-actions">
                        <button class="btn btn-primary" id="downloadNoteBtn" style="font-size: 0.8rem; padding: 8px 14px; width: 100%;">Pobierz notatkę (.TXT)</button>
                    </div>
                </div>

                <!-- Right column: Up Next Recommendations -->
                <div class="watch-recommendations">
                    <?php if ($playlistInfo): ?>
                        <!-- YouTube style Playlist Queue Panel -->
                        <div class="playlist-queue-panel" style="background: #1f1f1f; border: 1px solid var(--card-border); border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
                            <div style="padding: 14px 16px; background: #272727; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-size: 0.9rem; font-weight: 700; color: #fff; line-height: 1.2;"><?= htmlspecialchars($playlistInfo['title']) ?></h4>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($playlistInfo['username']) ?> &bull; <?= $currentIndex + 1 ?>/<?= count($playlistNotes) ?></span>
                                </div>
                                <a href="playlist.php?id=<?= $playlistInfo['id'] ?>" style="color: #3ea6ff; font-size: 0.78rem; text-decoration: none; font-weight: 500;">Szczegóły</a>
                            </div>
                            <div style="max-height: 380px; overflow-y: auto; display: flex; flex-direction: column;">
                                <?php 
                                $queueIdx = 1;
                                foreach ($playlistNotes as $pn): 
                                    $isActive = $pn['id'] == $note_id;
                                ?>
                                    <a href="watch.php?id=<?= $pn['id'] ?>&playlist_id=<?= $playlist_id ?>" style="display: flex; gap: 10px; padding: 10px 16px; text-decoration: none; align-items: center; border-bottom: 1px solid #272727; background: <?= $isActive ? '#2d2d2d' : 'transparent' ?>;">
                                        <span style="font-size: 0.78rem; font-weight: 700; color: <?= $isActive ? '#ff0000' : 'var(--text-secondary)' ?>; width: 15px;"><?= $queueIdx++ ?></span>
                                        <div style="flex-grow: 1; overflow: hidden;">
                                            <div style="font-size: 0.85rem; font-weight: <?= $isActive ? '700' : '500' ?>; color: <?= $isActive ? '#3ea6ff' : '#fff' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($pn['title']) ?></div>
                                            <div style="font-size: 0.72rem; color: var(--text-secondary);"><?= htmlspecialchars($pn['username']) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h3 style="font-size: 1rem; font-weight: 500; margin-bottom: 8px;">Polecane dla Ciebie</h3>
                    
                    <?php foreach ($recommended as $rec): 
                        $recExt = strtolower(pathinfo($rec['filepath'], PATHINFO_EXTENSION));
                        $recIsImage = in_array($recExt, ['jpg', 'jpeg', 'png', 'webp']);
                        $recIsPres = $rec['file_type'] === 'presentation';
                        $recHref = 'watch.php?id=' . $rec['id'] . ($playlist_id > 0 ? '&playlist_id=' . $playlist_id : '');
                        if ((($rec['access_type'] ?? 'free') === 'premium') && $rec['user_id'] != $user_id) {
                            try {
                                $stmtChkRec = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?");
                                $stmtChkRec->execute([$user_id, $rec['id']]);
                                $recHasPurchased = (bool)$stmtChkRec->fetch();
                            } catch (\PDOException $e) { $recHasPurchased = false; }
                            if (!$recHasPurchased) {
                                $recHref = 'paypal_mock.php?note_id=' . $rec['id'];
                            }
                        }
                    ?>
                        <a href="<?= $recHref ?>" class="recommendation-card">
                            <div class="recommendation-thumbnail">
                                <?php if ($recIsPres): ?>
                                    <div class="recommendation-thumbnail-preview">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <div style="font-size: 0.65rem; font-weight:700; margin-top:4px;">PREZENTACJA</div>
                                    </div>
                                <?php elseif ($recIsImage): ?>
                                    <img src="<?= htmlspecialchars($rec['filepath']) ?>" alt="<?= htmlspecialchars($rec['title']) ?>">
                                <?php else: ?>
                                    <div class="recommendation-thumbnail-preview">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <div style="font-size: 0.65rem; font-weight:700; margin-top:4px;">PDF</div>
                                    </div>
                                <?php endif; ?>
                                <span class="note-badge"><?= htmlspecialchars($rec['subject']) ?></span>
                            </div>
                            <div class="recommendation-info">
                                <div class="rec-title"><?= htmlspecialchars($rec['title']) ?></div>
                                <div class="rec-author"><?= htmlspecialchars($rec['username']) ?></div>
                                <div class="rec-views"><?= (int)$rec['views'] ?> wyświetleń</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="security.js"></script>
    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        const noteId = <?= $note_id ?>;
        const csrfToken = '<?= SecurityEnterprise::csrfToken() ?>';
        const slides = <?= json_encode(is_array($slides) ? $slides : []) ?>;
        let activeSlideIdx = 0;
        let autoplayInterval = null;
        let isPlaying = false;

        function loadSlide() {
            const container = document.getElementById('slideFrame');
            if (!slides || slides.length === 0) {
                container.innerHTML = '<div style="padding:20px; color:var(--text-secondary);">Błąd ładowania slajdów lub brak zawartości.</div>';
                return;
            }
            container.innerHTML = '';
            const img = document.createElement('img');
            img.src = slides[activeSlideIdx];
            img.alt = 'Slajd prezentacji';
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            img.style.objectFit = 'contain';
            container.appendChild(img);
            const count = document.getElementById('currentSlideNum');
            if (count) count.textContent = activeSlideIdx + 1;
        }

        function nextSlide() {
            if (slides.length === 0) return;
            activeSlideIdx = (activeSlideIdx + 1) % slides.length;
            loadSlide();
        }

        function prevSlide() {
            if (slides.length === 0) return;
            activeSlideIdx = (activeSlideIdx - 1 + slides.length) % slides.length;
            loadSlide();
        }

        function toggleFullscreen() {
            const player = document.getElementById('playerWrapper');
            if (!document.fullscreenElement) {
                player.requestFullscreen().catch(err => {
                    alert(`Error attempting to enable full-screen mode: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
            if (e.key === 'ArrowRight') {
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                prevSlide();
            }
        });

        // Initialize presentation
        if (slides.length > 0) {
            loadSlide();
        }

        // Expand description box
        function expandDescription() {
            const box = document.getElementById('descriptionBox');
            box.classList.add('expanded');
        }

        // Bookmark Toggle
        function toggleBookmark() {
            fetch('toggle_lesson.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${noteId}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const btn = document.getElementById('bookmarkBtn');
                        const label = btn.querySelector('span');
                        if (data.is_bookmarked) {
                            btn.classList.add('active');
                            label.textContent = 'Zapisano';
                        } else {
                            btn.classList.remove('active');
                            label.textContent = 'Zapisz';
                        }
                    }
                });
        }

        // Like / Dislike Toggle
        function toggleLike(type) {
            fetch('toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `note_id=${noteId}&type=${type}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const likeBtn = document.getElementById('likeBtn');
                        const dislikeBtn = document.getElementById('dislikeBtn');
                        const likeCount = document.getElementById('likeCount');
                        const dislikeCount = document.getElementById('dislikeCount');

                        likeCount.textContent = data.likes;
                        dislikeCount.textContent = data.dislikes;

                        if (data.user_vote === 'like') {
                            likeBtn.classList.add('active');
                            dislikeBtn.classList.remove('active');
                        } else if (data.user_vote === 'dislike') {
                            dislikeBtn.classList.add('active');
                            likeBtn.classList.remove('active');
                        } else {
                            likeBtn.classList.remove('active');
                            dislikeBtn.classList.remove('active');
                        }
                    } else {
                        alert(data.message || 'Wystąpił błąd.');
                    }
                });
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

        // Watch Later Toggle
        function toggleWatchLater() {
            fetch('toggle_watch_later.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `note_id=${noteId}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const watchLaterBtn = document.getElementById('watchLaterBtn');
                        const watchLaterText = document.getElementById('watchLaterText');

                        if (data.added) {
                            watchLaterBtn.classList.add('active');
                            watchLaterText.textContent = 'Do Obejrzenia (Zapisano)';
                        } else {
                            watchLaterBtn.classList.remove('active');
                            watchLaterText.textContent = 'Do Obejrzenia';
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
        });

        // Comments Actions Toggle
        function activateCommentActions() {
            document.getElementById('commentActions').classList.add('active');
        }

        function cancelComment() {
            document.getElementById('commentText').value = '';
            document.getElementById('commentActions').classList.remove('active');
        }

        function submitComment() {
            const content = document.getElementById('commentText').value.trim();
            if (!content) return;

            fetch('submit_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `note_id=${noteId}&content=${encodeURIComponent(content)}&csrf_token=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    cancelComment();
                    loadComments();
                } else {
                    alert(data.message || 'Błąd dodawania komentarza.');
                }
            });
        }

        function loadComments() {
            fetch('fetch_comments.php?note_id=' + noteId)
                .then(res => res.json())
                .then(comments => {
                    const list = document.getElementById('commentsList');
                    list.innerHTML = '';
                    
                    const title = document.getElementById('commentsCountTitle');
                    title.textContent = `${comments.length} komentarzy`;

                    if (comments.length === 0) {
                        list.innerHTML = '<p style="color:var(--text-secondary); font-size:0.9rem;">Brak komentarzy. Bądź pierwszy!</p>';
                        return;
                    }

                    comments.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'comment-node';

                        const avatar = document.createElement('div');
                        avatar.className = 'user-avatar';
                        avatar.textContent = (c.username || '?').charAt(0).toUpperCase();

                        const wrapper = document.createElement('div');
                        wrapper.className = 'comment-content-wrapper';

                        const header = document.createElement('div');
                        header.className = 'comment-header-row';

                        const userSpan = document.createElement('span');
                        userSpan.className = 'comment-user';
                        userSpan.textContent = c.username || 'Anonim';

                        const timeSpan = document.createElement('span');
                        timeSpan.className = 'comment-time';
                        timeSpan.textContent = c.created_at || '';

                        header.append(userSpan, timeSpan);

                        const msg = document.createElement('div');
                        msg.className = 'comment-msg';
                        msg.textContent = c.content || '';

                        wrapper.append(header, msg);
                        div.append(avatar, wrapper);
                        list.appendChild(div);
                    });
                });
        }

        // Personal Notepad Logic
        var saveTimeout = null;
        var notepadTextEl = document.getElementById('notepadText');
        var notepadStatusEl = document.getElementById('notepadStatus');

        function loadPersonalNote() {
            fetch('save_personal_note.php?note_id=' + noteId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        notepadTextEl.value = data.content;
                        notepadStatusEl.textContent = 'Wszystko zapisane';
                    } else {
                        notepadStatusEl.textContent = 'Błąd wczytywania';
                    }
                })
                .catch(() => {
                    notepadStatusEl.textContent = 'Błąd połączenia';
                });
        }

        function savePersonalNote() {
            notepadStatusEl.textContent = 'Zapisywanie...';
            var noteVal = notepadTextEl.value;

            fetch('save_personal_note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `note_id=${noteId}&content=${encodeURIComponent(noteVal)}&csrf_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        notepadStatusEl.textContent = 'Wszystkie zmiany zapisane';
                    } else {
                        notepadStatusEl.textContent = 'Błąd zapisu';
                    }
                })
                .catch(() => {
                    notepadStatusEl.textContent = 'Błąd połączenia';
                });
        }

        if (notepadTextEl) {
            notepadTextEl.addEventListener('input', function() {
                notepadStatusEl.textContent = 'Niezapisane zmiany...';
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(savePersonalNote, 1200);
            });
        }

        // Reading theme logic
        document.querySelectorAll('.theme-option-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.theme-option-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                var theme = this.getAttribute('data-theme');
                var frame = document.getElementById('slideFrame');
                
                frame.classList.remove('theme-sepia', 'theme-light');
                if (theme === 'sepia') {
                    frame.classList.add('theme-sepia');
                } else if (theme === 'light') {
                    frame.classList.add('theme-light');
                }
            });
        });

        // Autoplay logic
        var playPauseBtn = document.getElementById('playPauseBtn');
        var autoplaySpeed = document.getElementById('autoplaySpeed');

        function startAutoplay() {
            var speed = parseInt(autoplaySpeed.value) || 5000;
            autoplayInterval = setInterval(() => {
                nextSlide();
            }, speed);
            isPlaying = true;
            if (playPauseBtn) playPauseBtn.querySelector('span').textContent = '⏸';
        }

        function stopAutoplay() {
            clearInterval(autoplayInterval);
            isPlaying = false;
            if (playPauseBtn) playPauseBtn.querySelector('span').textContent = '▶';
        }

        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                if (isPlaying) {
                    stopAutoplay();
                } else {
                    startAutoplay();
                }
            });
        }
        if (autoplaySpeed) {
            autoplaySpeed.addEventListener('change', function() {
                if (isPlaying) {
                    stopAutoplay();
                    startAutoplay();
                }
            });
        }

        // Load comments and notepad immediately
        document.addEventListener('DOMContentLoaded', () => {
            loadComments();
            loadPersonalNote();

            // Toggle Notepad Column
            const toggleNotepadBtn = document.getElementById('toggleNotepadBtn');
            const notepadPanel = document.getElementById('notepadPanel');
            const watchContainer = document.getElementById('watchContainer');

            if (toggleNotepadBtn && notepadPanel && watchContainer) {
                toggleNotepadBtn.addEventListener('click', () => {
                    var isHidden = notepadPanel.style.display === 'none';
                    notepadPanel.style.display = isHidden ? 'flex' : 'none';
                    watchContainer.classList.toggle('watch-with-notepad', isHidden);
                    toggleNotepadBtn.classList.toggle('active', isHidden);
                });
            }

            // Download note as text file
            const downloadNoteBtn = document.getElementById('downloadNoteBtn');
            if (downloadNoteBtn) {
                downloadNoteBtn.addEventListener('click', () => {
                    var text = notepadTextEl.value;
                    var blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'notatka_lekcja_' + noteId + '.txt';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            }

            // Slideshow controls
            const prevSlideBtn = document.getElementById('prevSlideBtn');
            const nextSlideBtn = document.getElementById('nextSlideBtn');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            if (prevSlideBtn) prevSlideBtn.addEventListener('click', prevSlide);
            if (nextSlideBtn) nextSlideBtn.addEventListener('click', nextSlide);
            if (fullscreenBtn) fullscreenBtn.addEventListener('click', toggleFullscreen);

            // Subscription
            const subBtn = document.getElementById('subBtn');
            if (subBtn) {
                subBtn.addEventListener('click', () => {
                    const teacherId = subBtn.getAttribute('data-teacher-id');
                    toggleSubscription(teacherId);
                });
            }

            // Likes/Dislikes
            const likeBtn = document.getElementById('likeBtn');
            const dislikeBtn = document.getElementById('dislikeBtn');
            if (likeBtn) likeBtn.addEventListener('click', () => toggleLike('like'));
            if (dislikeBtn) dislikeBtn.addEventListener('click', () => toggleLike('dislike'));

            // Watch Later
            const watchLaterBtn = document.getElementById('watchLaterBtn');
            if (watchLaterBtn) watchLaterBtn.addEventListener('click', toggleWatchLater);

            // Bookmark
            const bookmarkBtn = document.getElementById('bookmarkBtn');
            if (bookmarkBtn) bookmarkBtn.addEventListener('click', toggleBookmark);

            // Report
            const reportBtn = document.getElementById('reportBtn');
            if (reportBtn) {
                reportBtn.addEventListener('click', (e) => {
                    if (!confirm('Zgłosić ten materiał?')) {
                        e.preventDefault();
                    }
                });
            }

            // Description Expand
            const descriptionBox = document.getElementById('descriptionBox');
            if (descriptionBox) descriptionBox.addEventListener('click', expandDescription);

            // Comments input behavior
            const commentText = document.getElementById('commentText');
            if (commentText) {
                commentText.addEventListener('focus', activateCommentActions);
            }

            const cancelCommentBtn = document.getElementById('cancelCommentBtn');
            if (cancelCommentBtn) cancelCommentBtn.addEventListener('click', cancelComment);

            const submitCommentBtn = document.getElementById('submitCommentBtn');
            if (submitCommentBtn) submitCommentBtn.addEventListener('click', submitComment);
        });
    </script>
</body>
</html>
