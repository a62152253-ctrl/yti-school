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

// Build query - Filtered for notes only (non-presentation)
$queryStr = "SELECT n.*, u.username, 
            (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
            FROM notes n 
            JOIN users u ON n.user_id = u.id 
            WHERE n.file_type != 'presentation'";
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
<?php
$pageTitle = 'Notatki i PDF - Yti School';
$activePage = 'notatki.php';
$searchFormAction = 'notatki.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="content-header">
                <h2>Notatki i Dokumenty (PDF / Obrazy)</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Przeglądaj wgrane materiały w tradycyjnej formie</p>
            </header>

            <!-- Subject Chips Navigation (horizontal scroll) -->
            <div class="subject-chips-container">
                <a href="notatki.php" class="chip-btn <?= empty($selected_subject) ? 'active' : '' ?>">Wszystkie</a>
                <a href="notatki.php?subject=subscriptions" class="chip-btn <?= $selected_subject === 'subscriptions' ? 'active' : '' ?>" style="border: 1px solid var(--accent-color);">Subskrypcje</a>
                <a href="notatki.php?subject=matematyka" class="chip-btn <?= $selected_subject === 'matematyka' ? 'active' : '' ?>">Matematyka</a>
                <a href="notatki.php?subject=fizyka" class="chip-btn <?= $selected_subject === 'fizyka' ? 'active' : '' ?>">Fizyka</a>
                <a href="notatki.php?subject=biologia" class="chip-btn <?= $selected_subject === 'biologia' ? 'active' : '' ?>">Biologia</a>
                <a href="notatki.php?subject=chemia" class="chip-btn <?= $selected_subject === 'chemia' ? 'active' : '' ?>">Chemia</a>
                <a href="notatki.php?subject=geografia" class="chip-btn <?= $selected_subject === 'geografia' ? 'active' : '' ?>">Geografia</a>
                <a href="notatki.php?subject=historia" class="chip-btn <?= $selected_subject === 'historia' ? 'active' : '' ?>">Historia</a>
                <a href="notatki.php?subject=polski" class="chip-btn <?= $selected_subject === 'polski' ? 'active' : '' ?>">Język Polski</a>
                <a href="notatki.php?subject=angielski" class="chip-btn <?= $selected_subject === 'angielski' ? 'active' : '' ?>">Język Angielski</a>
                <a href="notatki.php?subject=inne" class="chip-btn <?= $selected_subject === 'inne' ? 'active' : '' ?>">Inne</a>
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
                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h3>Brak tradycyjnych notatek</h3>
                    <p style="margin-top: 10px;">Spróbuj wyczyścić filtry wyszukiwania.</p>
                </div>
            <?php else: ?>
                <div class="note-grid">
                    <?php foreach ($notes as $note): 
                        $thumbnailUrl = '';
                        if (($note['file_type'] ?? '') === 'image') {
                            $thumbnailUrl = 'download.php?id=' . (int)$note['id'];
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
                                            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <div style="font-size: 0.78rem; margin-top: 5px; font-weight: 500;">Dokument PDF</div>
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
