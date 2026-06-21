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
    $countQuery = "SELECT COUNT(*) FROM my_lessons WHERE user_id = ?";
    $stmtCount = $pdo->prepare($countQuery);
    $stmtCount->execute([$user_id]);
    $totalItems = (int)$stmtCount->fetchColumn();
} catch (\PDOException $e) {
    $totalItems = 0;
}

$totalPages = max(1, ceil($totalItems / $limit));
$page = min($page, $totalPages);

// Fetch items
try {
    $queryStr = "SELECT n.*, u.username, u.type as user_type, 1 as is_bookmarked 
                 FROM my_lessons ml 
                 JOIN notes n ON ml.note_id = n.id 
                 JOIN users u ON n.user_id = u.id 
                 WHERE ml.user_id = ? 
                 ORDER BY ml.created_at DESC LIMIT ? OFFSET ?";
    $params = [$user_id, $limit, $offset];
    
    $stmt = $pdo->prepare($queryStr);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
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
$pageTitle = 'Zapisane lekcje - Yti School';
$activePage = 'page_favorites.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h2>Twoje zapisane lekcje</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Przeglądaj zapisaną bibliotekę materiałów edukacyjnych</p>
            </header>

            <?php if (empty($notes)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <h3>Brak zapisanych lekcji</h3>
                    <p style="margin-top: 10px; margin-bottom: 20px;">Dodaj ciekawe lekcje do ulubionych, aby mieć do nich szybki dostęp.</p>
                    <a href="student_dashboard.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 24px; border-radius: 18px;">Przeglądaj materiały</a>
                </div>
            <?php else: ?>
                <section class="dashboard-section">
                    <div class="note-grid">
                        <?php foreach ($notes as $note): 
                            $thumbnailUrl = '';
                            $isPres = $note['file_type'] === 'presentation';
                            if ($isPres) {
                                $thumbnailUrl = 'download.php?id=' . (int)$note['id'] . '&slide=0';
                            } elseif (($note['file_type'] ?? '') === 'image') {
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

            let searchTimeout = null;
            
            searchInput.addEventListener('input', function() {
                const query = searchInput.value.trim();
                if (query.length < 1) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                    return;
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
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
                                
                                // Bezpieczne renderowanie HTML, zapobieganie XSS
                                const safeTitle = document.createElement('div');
                                safeTitle.style.flexGrow = '1';
                                safeTitle.textContent = item.title;
                                
                                const safeSubject = document.createElement('span');
                                safeSubject.style.fontSize = '0.72rem';
                                safeSubject.style.opacity = '0.6';
                                safeSubject.style.textTransform = 'uppercase';
                                safeSubject.textContent = item.subject;
                                
                                div.innerHTML = `
                                    <div class="autocomplete-suggestion-icon">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                `;
                                div.appendChild(safeTitle);
                                div.appendChild(safeSubject);
                                
                                div.addEventListener('click', function() {
                                    window.location.href = 'watch.php?id=' + encodeURIComponent(item.id);
                                });
                                suggestionsBox.appendChild(div);
                            });
                            suggestionsBox.style.display = 'block';
                        })
                        .catch(err => console.error("Błąd wyszukiwania: ", err));
                }, 300); // debounce 300ms
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
