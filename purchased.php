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
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupione - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
</head>
<body>
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
        <div class="yt-header-center"></div>
        <div class="yt-header-right">
            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['username']) ?>">
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="app-container">
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
                    <li>
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
                    <li class="active">
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
                            $slides = json_decode($item['filepath'], true);
                            $thumb = !empty($slides) ? $slides[0] : '';
                        } else {
                            $ext = strtolower(pathinfo($item['filepath'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                $thumb = $item['filepath'];
                            }
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
