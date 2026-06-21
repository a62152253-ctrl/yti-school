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
<?php
$pageTitle = 'Moje Lekcje - Yti School';
$activePage = 'my_lessons.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

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
