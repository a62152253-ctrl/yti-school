<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Pagination settings
$limit = 9;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch count
try {
    $countQuery = "SELECT COUNT(*) FROM notes WHERE file_type != 'presentation'";
    $countParams = [];
    if (!empty($student_class)) {
        $countQuery .= " AND class_level = ?";
        $countParams[] = $student_class;
    }
    
    $stmtCount = $pdo->prepare($countQuery);
    $stmtCount->execute($countParams);
    $totalItems = (int)$stmtCount->fetchColumn();
} catch (\PDOException $e) {
    $totalItems = 0;
}

$totalPages = max(1, ceil($totalItems / $limit));
$page = min($page, $totalPages);

// Fetch items
try {
    $queryStr = "SELECT n.*, u.username, u.type as user_type,
                 (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                 FROM notes n 
                 JOIN users u ON n.user_id = u.id 
                 WHERE n.file_type != 'presentation'";
    $params = [$user_id];
    
    if (!empty($student_class)) {
        $queryStr .= " AND n.class_level = ?";
        $params[] = $student_class;
    }
    
    $queryStr .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($queryStr);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $idx = 2;
    if (!empty($student_class)) {
        $stmt->bindValue($idx++, $student_class, PDO::PARAM_STR);
    }
    $stmt->bindValue($idx++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $notes = [];
}

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}

try {
    $stmtPur = $pdo->prepare("SELECT note_id FROM purchases WHERE user_id = ?");
    $stmtPur->execute([$user_id]);
    $purchasedNoteIds = array_map('intval', array_column($stmtPur->fetchAll(), 'note_id'));
} catch (\PDOException $e) {
    $purchasedNoteIds = [];
}
?>
<?php
$pageTitle = 'Notatki i PDF - Yti School';
$activePage = 'page_notes.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h2>Notatki i Dokumenty</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Przeglądaj notatki, PDF i pliki graficzne (Klasa: <?= htmlspecialchars($student_class ?: 'Wszystkie') ?>)</p>
            </header>

            <?php if (empty($notes)): ?>
                <div class="empty-state-card">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h3>Brak dostępnych notatek</h3>
                    <p>Zajrzyj tu później lub zmień klasę w swoim profilu, żeby zobaczyć materiały z innej klasy.</p>
                </div>
            <?php else: ?>
                <section class="dashboard-section">
                    <div class="note-grid">
                        <?php foreach ($notes as $note): 
                            $thumbnailUrl = '';
                            if (($note['file_type'] ?? '') === 'image') {
                                $thumbnailUrl = 'download.php?id=' . (int)$note['id'];
                            }
                            $watchHref = 'watch.php?id=' . (int)$note['id'];
                            if ((($note['access_type'] ?? 'free') === 'premium') && (int)$note['user_id'] !== (int)$user_id && !in_array((int)$note['id'], $purchasedNoteIds, true)) {
                                $watchHref = 'paypal_mock.php?note_id=' . (int)$note['id'];
                            }
                            $isTeacherCreator = isset($note['user_type']) ? ($note['user_type'] === 'teacher') : true;
                            $creatorProfileUrl = $isTeacherCreator ? 'channel.php?id=' . $note['user_id'] : 'student_profile.php?id=' . $note['user_id'];
                        ?>
                            <article class="note-card">
                                <a href="<?= htmlspecialchars($watchHref) ?>" class="note-thumbnail-wrapper">
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
                                
                                <div class="note-details-youtube" style="display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; width: 100%;">
                                    <div style="display: flex; gap: 12px; flex-grow: 1; min-width: 0;">
                                        <a href="<?= $creatorProfileUrl ?>" class="note-creator-avatar note-creator-link" style="flex-shrink: 0; text-decoration: none;">
                                            <?= strtoupper(substr(htmlspecialchars($note['username']), 0, 1)) ?>
                                        </a>
                                        <div class="note-text-group" style="min-width: 0; flex-grow: 1;">
                                            <h4 class="note-title" style="margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <a href="<?= htmlspecialchars($watchHref) ?>" class="note-title-link"><?= htmlspecialchars($note['title']) ?></a>
                                            </h4>
                                            <p class="note-author-name" style="margin-bottom: 2px;">
                                                <a href="<?= $creatorProfileUrl ?>" class="note-creator-link"><?= htmlspecialchars($note['username']) ?></a> 
                                                &bull; <?= htmlspecialchars($note['class_level'] ?? '') ?>
                                            </p>
                                            <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyświetleń</p>
                                        </div>
                                    </div>
                                    <button type="button" class="bookmark-card-btn" data-id="<?= $note['id'] ?>" aria-label="Zapisz lekcję" style="background: none; border: none; color: <?= $note['is_bookmarked'] ? 'var(--accent-color)' : 'var(--text-secondary)' ?>; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; transition: color 0.2s ease;">
                                        <svg width="20" height="20" fill="<?= $note['is_bookmarked'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                        </svg>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Pagination navigation -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <a href="?page=<?= $page - 1 ?>" class="pagination-link <?= $page <= 1 ? 'disabled' : '' ?>" title="Poprzednia strona">&larr;</a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="pagination-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="?page=<?= $page + 1 ?>" class="pagination-link <?= $page >= $totalPages ? 'disabled' : '' ?>" title="Następna strona">&rarr;</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script src="app.js"></script>
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
