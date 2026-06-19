<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$successMsg = '';

if (isset($_GET['remove'])) {
    $removeNoteId = (int)$_GET['remove'];
    try {
        $stmt = $pdo->prepare('DELETE FROM my_lessons WHERE user_id = ? AND note_id = ?');
        $stmt->execute([$user_id, $removeNoteId]);
        $successMsg = 'Usunięto materiał z ulubionych.';
    } catch (\PDOException $e) {
        $error = 'Nie udało się usunąć materiału.';
    }
}

try {
    $stmt = $pdo->prepare('SELECT n.*, u.username as teacher_name FROM my_lessons m JOIN notes n ON m.note_id = n.id JOIN users u ON n.user_id = u.id WHERE m.user_id = ? ORDER BY m.created_at DESC');
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll();
} catch (\PDOException $e) {
    $favorites = [];
    $error = 'Nie udało się pobrać ulubionych materiałów.';
}

$pageTitle = 'Ulubione materiały - Yti School';
$activePage = 'favorites.php';
$hideSearch = true;
require APP_ROOT . '/partials/head.php';
require APP_ROOT . '/partials/topbar.php';
require APP_ROOT . '/partials/sidebar.php';
?>
<main class="main-content">
    <div class="glass-card" style="max-width: 980px; margin: 24px auto; padding: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px;">
            <div>
                <h2>Ulubione materiały</h2>
                <p style="color: var(--text-secondary); margin-top: 4px;">Przeglądaj materiały, które oznaczyłeś jako ulubione.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if (empty($favorites)): ?>
            <div class="glass-card" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                <h3>Brak ulubionych materiałów</h3>
                <p style="margin-top: 10px;">Dodaj materiały do ulubionych, aby mieć do nich szybki dostęp.</p>
            </div>
        <?php else: ?>
            <div class="note-grid">
                <?php foreach ($favorites as $note):
                    $thumbnailUrl = '';
                    $fileExtension = strtolower(pathinfo($note['filepath'] ?? '', PATHINFO_EXTENSION));
                    $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                    if ($isImage) {
                        $thumbnailUrl = $note['filepath'] ?? '';
                    } else if (($note['file_type'] ?? '') === 'presentation') {
                        $slides = json_decode($note['filepath'] ?? '', true);
                        if (is_array($slides) && !empty($slides)) {
                            $thumbnailUrl = $slides[0];
                        }
                    }
                ?>
                    <div class="note-card">
                        <a href="watch.php?id=<?= $note['id'] ?>" class="note-thumbnail-wrapper" style="display:block; text-decoration:none;">
                            <span class="note-badge"><?= htmlspecialchars($note['subject']) ?> • <?= ($note['access_type'] ?? 'free') === 'premium' ? 'Premium' : 'Free' ?></span>
                            <?php if (!empty($thumbnailUrl)): ?>
                                <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail">
                            <?php else: ?>
                                <div class="note-file-preview">
                                    <div class="note-file-icon">
                                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="note-details-youtube">
                            <a href="channel.php?id=<?= $note['user_id'] ?>" class="note-creator-avatar" style="text-decoration:none;">
                                <?= strtoupper(substr(htmlspecialchars($note['teacher_name']), 0, 1)) ?>
                            </a>
                            <div class="note-text-group">
                                <h4 class="note-title"><a href="watch.php?id=<?= $note['id'] ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($note['title']) ?></a></h4>
                                <p class="note-author-name"><?= htmlspecialchars($note['teacher_name']) ?> &bull; <?= htmlspecialchars($note['class_level'] ?? '') ?></p>
                                <p class="note-metrics-youtube"><?= (int)$note['views'] ?> wyświetleń</p>
                            </div>
                        </div>
                        <div style="margin-top: 12px; display:flex; justify-content:flex-end; gap:8px;">
                            <a href="favorites.php?remove=<?= $note['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px;">Usuń</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
