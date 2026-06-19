<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Fetch all notes bookmarked by the student
try {
    $stmt = $pdo->prepare("SELECT n.*, u.username, 1 as is_bookmarked 
                           FROM my_lessons ml 
                           JOIN notes n ON ml.note_id = n.id 
                           JOIN users u ON n.user_id = u.id 
                           WHERE ml.user_id = ? 
                           ORDER BY ml.created_at DESC");
    $stmt->execute([$user_id]);
    $bookmarks = $stmt->fetchAll();
} catch (\PDOException $e) {
    $bookmarks = [];
}

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Lekcje - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
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
                    <li class="active">
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
                            <li>
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
            <header class="content-header">
                <h2>Zapisane Lekcje (Twoja Biblioteka)</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Materiały edukacyjne, które zapisałeś do powtórek</p>
            </header>

            <?php if (empty($bookmarks)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <h3 style="color:#fff;">Brak zapisanych lekcji</h3>
                    <p style="margin-top: 10px; margin-bottom: 20px;">Przejdź na stronę główną i kliknij "Zapisz" pod wybraną lekcją.</p>
                    <a href="student_dashboard.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 24px; border-radius: 18px;">Przeglądaj Lekcje</a>
                </div>
            <?php else: ?>
                <div class="note-grid">
                    <?php foreach ($bookmarks as $note): 
                        $isPres = $note['file_type'] === 'presentation';
                        $thumbnailUrl = '';
                        if ($isPres) {
                            $slides = json_decode($note['filepath'], true);
                            if (is_array($slides) && !empty($slides)) {
                                $thumbnailUrl = $slides[0];
                            } else {
                                $thumbnailUrl = $note['filepath'] ?? '';
                            }
                        } else {
                            $fileExtension = strtolower(pathinfo($note['filepath'] ?? '', PATHINFO_EXTENSION));
                            $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                            if ($isImage) {
                                $thumbnailUrl = $note['filepath'] ?? '';
                            }
                        }
                    ?>
                        <div class="note-card">
                            <a href="watch.php?id=<?= $note['id'] ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                <span class="note-badge"><?= htmlspecialchars($note['subject']) ?></span>
                                <?php if (!empty($thumbnailUrl)): ?>
                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
                                <?php else: ?>
                                    <div class="note-file-preview">
                                        <div class="note-file-icon">
                                            <?php if ($note['file_type'] === 'presentation'): ?>
                                                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Prezentacja slajdów</div>
                                            <?php else: ?>
                                                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Dokument PDF</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <div class="note-details-youtube">
                               <a href="channel.php?id=<?= $note['user_id'] ?>" class="note-creator-avatar" style="text-decoration: none;">
                                    <?= strtoupper(substr(htmlspecialchars($note['username']), 0, 1)) ?>
                                </a>
                                <div class="note-text-group">
                                    <h4 class="note-title">
                                        <a href="watch.php?id=<?= $note['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($note['title']) ?></a>
                                    </h4>
                                    <p class="note-author-name">
                                        <a href="channel.php?id=<?= $note['user_id'] ?>" style="text-decoration: none; color: inherit; font-weight: 500;"><?= htmlspecialchars($note['username']) ?></a> 
                                        &bull; <?= htmlspecialchars($note['class_level'] ?? '') ?>
                                    </p>
                                    <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyświetleń</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <!-- Autocomplete suggestions Javascript -->
    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
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
    </script>
</body>
</html>
