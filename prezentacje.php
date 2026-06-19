<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Collect filter criteria
$selected_subject = $_GET['subject'] ?? '';
$search_query = trim($_GET['search'] ?? '');

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}

// Build query - Filtered for presentations
$queryStr = "SELECT n.*, u.username, 
            (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
            FROM notes n 
            JOIN users u ON n.user_id = u.id 
            WHERE n.file_type = 'presentation'";
$params = [$user_id];

if ($selected_subject === 'subscriptions') {
    $queryStr .= " AND n.user_id IN (SELECT teacher_id FROM subscriptions WHERE student_id = ?)";
    $params[] = $user_id;
} else if (!empty($selected_subject)) {
    $queryStr .= " AND LOWER(n.subject) = LOWER(?)";
    $params[] = $selected_subject;
}

if (!empty($student_class)) {
    $queryStr .= " AND n.class_level = ?";
    $params[] = $student_class;
}

if (!empty($search_query)) {
    $queryStr .= " AND (n.title LIKE ? OR n.description LIKE ? OR n.tags LIKE ?)";
    $like = '%' . $search_query . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$queryStr .= " ORDER BY n.created_at DESC";

try {
    $stmt = $pdo->prepare($queryStr);
    $stmt->execute($params);
    $notes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $notes = [];
}

// Extract popular tags from notes
$allTags = [];
foreach ($notes as $n) {
    if (!empty($n['tags'])) {
        $tagsList = explode(',', $n['tags']);
        foreach ($tagsList as $t) {
            $trimmed = trim($t);
            if (!empty($trimmed)) {
                $allTags[strtolower($trimmed)] = ($allTags[strtolower($trimmed)] ?? 0) + 1;
            }
        }
    }
}
arsort($allTags);
$popularTags = array_slice($allTags, 0, 10);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prezentacje - Yti School</title>
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
            <form action="prezentacje.php" method="GET" class="yt-search-form">
                <div class="yt-search-box autocomplete-container">
                    <input type="text" name="search" id="ytSearchInput" placeholder="Szukaj prezentacji..." value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
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
                    <li class="active">
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
                        <a href="purchased.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7h18M5 7v13a1 1 0 001 1h12a1 1 0 001-1V7M8 7V4a2 2 0 012-2h4a2 2 0 012 2v3"/><path d="M9 12h6M9 16h6"/></svg>
                            Kupione
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
                <h2>Prezentacje (Slideshows)</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Przeglądaj interaktywne slajdy przygotowane przez nauczycieli</p>
            </header>

            <!-- Subject Chips Navigation (horizontal scroll) -->
            <div class="subject-chips-container">
                <a href="prezentacje.php" class="chip-btn <?= empty($selected_subject) ? 'active' : '' ?>">Wszystkie</a>
                <a href="prezentacje.php?subject=subscriptions" class="chip-btn <?= $selected_subject === 'subscriptions' ? 'active' : '' ?>" style="border: 1px solid var(--accent-color);">Subskrypcje</a>
                <a href="prezentacje.php?subject=matematyka" class="chip-btn <?= $selected_subject === 'matematyka' ? 'active' : '' ?>">Matematyka</a>
                <a href="prezentacje.php?subject=fizyka" class="chip-btn <?= $selected_subject === 'fizyka' ? 'active' : '' ?>">Fizyka</a>
                <a href="prezentacje.php?subject=biologia" class="chip-btn <?= $selected_subject === 'biologia' ? 'active' : '' ?>">Biologia</a>
                <a href="prezentacje.php?subject=chemia" class="chip-btn <?= $selected_subject === 'chemia' ? 'active' : '' ?>">Chemia</a>
                <a href="prezentacje.php?subject=geografia" class="chip-btn <?= $selected_subject === 'geografia' ? 'active' : '' ?>">Geografia</a>
                <a href="prezentacje.php?subject=historia" class="chip-btn <?= $selected_subject === 'historia' ? 'active' : '' ?>">Historia</a>
                <a href="prezentacje.php?subject=polski" class="chip-btn <?= $selected_subject === 'polski' ? 'active' : '' ?>">Język Polski</a>
                <a href="prezentacje.php?subject=angielski" class="chip-btn <?= $selected_subject === 'angielski' ? 'active' : '' ?>">Język Angielski</a>
                <a href="prezentacje.php?subject=inne" class="chip-btn <?= $selected_subject === 'inne' ? 'active' : '' ?>">Inne</a>
            </div>

            <!-- Tag Cloud -->
            <?php if (!empty($popularTags)): ?>
                <div class="tag-cloud-container" style="margin-bottom: 20px;">
                    <div class="tag-cloud-title">Popularne Tagi</div>
                    <div class="tag-cloud-list">
                        <?php foreach ($popularTags as $tag => $count): ?>
                            <a href="?search=<?= urlencode($tag) ?>" class="tag-cloud-item">#<?= htmlspecialchars($tag) ?> (<?= $count ?>)</a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($notes)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3>Brak prezentacji slajdów</h3>
                    <p style="margin-top: 10px;">Spróbuj wyczyścić filtry wyszukiwania.</p>
                </div>
            <?php else: ?>
                <div class="note-grid">
                    <?php foreach ($notes as $note): 
                        $slides = json_decode($note['filepath'], true);
                        if (is_array($slides) && !empty($slides)) {
                            $thumbnailUrl = $slides[0];
                        } else {
                            $thumbnailUrl = $note['filepath'] ?? '';
                        }
                    ?>
                        <div class="note-card">
                            <a href="watch.php?id=<?= $note['id'] ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                <span class="note-badge">
                                    <?= htmlspecialchars($note['subject']) ?>
                                    <?php if (($note['access_type'] ?? 'free') === 'premium'): ?>
                                        • Premium <?= number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ') ?> PLN
                                    <?php else: ?>
                                        • Free
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($thumbnailUrl)): ?>
                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
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
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                                        <p class="note-metrics-youtube" style="margin: 0;"><?= (int)$note['views'] ?> wyświetleń</p>
                                        <?php 
                                            $wordCount = str_word_count(strip_tags($note['description'] ?? ''));
                                            $readingTime = max(1, ceil($wordCount / 200));
                                        ?>
                                        <span class="reading-time-badge"><?= $readingTime ?> min czytania</span>
                                    </div>
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
