<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}

// Fetch all notes in Watch Later by the student
try {
    $stmt = $pdo->prepare("SELECT n.*, u.username 
                           FROM watch_later wl 
                           JOIN notes n ON wl.note_id = n.id 
                           JOIN users u ON n.user_id = u.id 
                           WHERE wl.user_id = ? 
                           ORDER BY wl.created_at DESC");
    $stmt->execute([$user_id]);
    $watch_later_notes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $watch_later_notes = [];
}
?>
<?php
$pageTitle = 'Do Obejrzenia - Yti School';
$activePage = 'watch_later.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="content-header">
                <h2>Do Obejrzenia</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Materiały zapisane do obejrzenia na później</p>
            </header>

            <?php if (empty($watch_later_notes)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                    </svg>
                    <h3 style="color:#fff;">Brak materiałów na liście</h3>
                    <p style="margin-top: 10px; margin-bottom: 20px;">Dodaj lekcje do listy "Do obejrzenia", klikając odpowiedni przycisk na stronie watch.</p>
                    <a href="student_dashboard.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 24px; border-radius: 18px;">Przeglądaj Lekcje</a>
                </div>
            <?php else: ?>
                <div class="note-grid">
                    <?php foreach ($watch_later_notes as $note): 
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
</body>
</html>
