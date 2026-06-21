<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT p.*, n.title, n.subject, n.file_type, n.filepath, n.access_type, n.premium_price, u.username AS teacher_name
                           FROM purchases p
                           JOIN notes n ON p.note_id = n.id
                           JOIN users u ON n.user_id = u.id
                           WHERE p.user_id = ?
                           ORDER BY p.paid_at DESC");
    $stmt->execute([$user_id]);
    $purchases = $stmt->fetchAll();

    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $purchases = [];
    $sidebar_subs = [];
}
?>
<?php
$pageTitle = 'Kupione - Yti School';
$activePage = 'purchased.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h2>Kupione materiały</h2>
                <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Tutaj znajdziesz wszystkie zakupione materiały premium.</p>
            </header>

            <?php if (empty($purchases)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <h3>Brak zakupionych materiałów</h3>
                    <p style="margin-top: 10px;">Kup materiał premium, aby zobaczyć go tutaj.</p>
                </div>
            <?php else: ?>
                <div class="note-grid">
                    <?php foreach ($purchases as $item): 
                        $thumb = '';
                        if ($item['file_type'] === 'presentation') {
                            $thumb = 'download.php?id=' . (int)$item['note_id'] . '&slide=0';
                        } elseif (($item['file_type'] ?? '') === 'image') {
                            $thumb = 'download.php?id=' . (int)$item['note_id'];
                        }
                        $price = number_format((float)($item['amount'] ?? $item['premium_price'] ?? 0), 2, ',', ' ');
                    ?>
                        <div class="note-card">
                            <a href="watch.php?id=<?= $item['note_id'] ?>" class="note-thumbnail-wrapper" style="display: block; text-decoration: none;">
                                <span class="note-badge">Premium • <?= $price ?> PLN</span>
                                <?php if (!empty($thumb)): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="note-thumbnail">
                                <?php else: ?>
                                    <div class="note-file-preview">
                                        <div class="note-file-icon">
                                            <?php if ($item['file_type'] === 'presentation'): ?>
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
                                <div class="note-creator-avatar"><?= strtoupper(substr(htmlspecialchars($item['teacher_name']), 0, 1)) ?></div>
                                <div class="note-text-group">
                                    <h4 class="note-title"><a href="watch.php?id=<?= $item['note_id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($item['title']) ?></a></h4>
                                    <p class="note-author-name"><?= htmlspecialchars($item['teacher_name']) ?> &bull; Kupione</p>
                                    <p class="note-metrics-youtube">Zapłacono <?= $price ?> PLN &bull; <?= htmlspecialchars(date('Y-m-d H:i', strtotime($item['paid_at']))) ?></p>
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
