<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Collect filter criteria
$selected_subject = SecurityEnterprise::getSanitizedString('subject');
$search_query = SecurityEnterprise::getSanitizedString('search');
$selected_filter = SecurityEnterprise::getSanitizedString('filter');

// Fetch subscriptions for sidebar
try {
    $subQuery = $pdo->prepare("SELECT u.id, u.username FROM subscriptions s JOIN users u ON s.teacher_id = u.id WHERE s.student_id = ?");
    $subQuery->execute([$user_id]);
    $sidebar_subs = $subQuery->fetchAll();
} catch (\PDOException $e) {
    $sidebar_subs = [];
}

// Fetch student dashboard summary counts
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_subscriptions FROM subscriptions WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $subscriptionsCount = (int)$stmt->fetch()['total_subscriptions'];
} catch (\PDOException $e) {
    $subscriptionsCount = 0;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as saved_items FROM my_lessons WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $savedCount = (int)$stmt->fetch()['saved_items'];
} catch (\PDOException $e) {
    $savedCount = 0;
}

try {
    $stmt = $pdo->prepare("SELECT note_id FROM purchases WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $purchasedNoteIds = array_map('intval', array_column($stmt->fetchAll(), 'note_id'));
} catch (\PDOException $e) {
    $purchasedNoteIds = [];
}

// Build query
$queryStr = "SELECT n.*, u.username, 
            (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
            FROM notes n 
            JOIN users u ON n.user_id = u.id";
$params = [$user_id];

$whereClauses = [];

if (!empty($student_class)) {
    $whereClauses[] = "n.class_level = ?";
    $params[] = $student_class;
}

if ($selected_filter === 'saved') {
    $whereClauses[] = "n.id IN (SELECT note_id FROM my_lessons WHERE user_id = ?)";
    $params[] = $user_id;
} elseif ($selected_filter === 'subscriptions' || $selected_subject === 'subscriptions') {
    $whereClauses[] = "n.user_id IN (SELECT teacher_id FROM subscriptions WHERE student_id = ?)";
    $params[] = $user_id;
} elseif (!empty($selected_subject)) {
    $whereClauses[] = "LOWER(n.subject) = LOWER(?)";
    $params[] = $selected_subject;
}

if (!empty($search_query)) {
    $whereClauses[] = "(n.title LIKE ? OR n.description LIKE ? OR n.tags LIKE ?)";
    $like = '%' . $search_query . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (count($whereClauses) > 0) {
    $queryStr .= " WHERE " . implode(" AND ", $whereClauses);
}

$queryStr .= " ORDER BY n.created_at DESC";

try {
    $stmt = $pdo->prepare($queryStr);
    $stmt->execute($params);
    $notes = $stmt->fetchAll();
} catch (\PDOException $e) {
    $notes = [];
}

// Subject breakdown stats query
$subjectLabels = [];
$subjectValues = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.subject, COUNT(*) as cnt 
        FROM history h 
        JOIN notes n ON h.note_id = n.id 
        WHERE h.user_id = ? 
        GROUP BY n.subject 
        ORDER BY cnt DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $subjectBreakdown = $stmt->fetchAll();
    foreach ($subjectBreakdown as $row) {
        $subjectLabels[] = ucfirst($row['subject']);
        $subjectValues[] = (int)$row['cnt'];
    }
} catch (\PDOException $e) {
    $subjectBreakdown = [];
}

$hasActivity = !empty($subjectBreakdown);

// Fallback in case of empty stats, so we always show a beautiful placeholder or mock graph
if (empty($subjectLabels)) {
    $subjectLabels = ['Brak danych', 'Rozpocznij', 'Naukę'];
    $subjectValues = [0, 0, 0];
}

$maxVal = max(1, max($subjectValues));

// Weekly target progress
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM history WHERE user_id = ? AND watched_at >= date('now', '-7 days')");
    $stmt->execute([$user_id]);
    $weeklyWatchedCount = (int)$stmt->fetchColumn();
} catch (\PDOException $e) {
    $weeklyWatchedCount = 0;
}
$weeklyTarget = 5;
$weeklyProgressPercent = min(100, round(($weeklyWatchedCount / $weeklyTarget) * 100));
$strokeDashoffset = 251 - (251 * $weeklyProgressPercent / 100);
?>
<?php
$pageTitle = 'Główna - Yti School';
$activePage = 'student_dashboard.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
<?php require_once 'partials/sidebar.php'; ?>
        <!-- Main Workspace -->
        <main class="main-content">
            <section class="dashboard-section">
                <div class="content-header dashboard-hero-lite">
                    <h2>Panel ucznia</h2>
                    <div class="dashboard-quick-actions" aria-label="Szybkie akcje">
                        <a href="my_lessons.php" class="quick-action">Zapisane</a>
                        <a href="history.php" class="quick-action">Historia</a>
                        <a href="cart.php" class="quick-action">Koszyk</a>
                    </div>
                    <p>Przeglądaj materiały, filtry i wyszukiwanie w jednym miejscu.</p>
                </div>
                <div class="student-summary-row">
                    <a href="?filter=subscriptions<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="stats-card mini<?= $selected_filter === 'subscriptions' ? ' active-filter' : '' ?>" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <span class="stats-card-icon">👥</span>
                        <div class="stats-card-info">
                            <span class="stats-label">Subskrybowani nauczyciele</span>
                            <span class="stats-value"><?= $subscriptionsCount ?></span>
                        </div>
                    </a>
                    <a href="?filter=saved<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="stats-card mini<?= $selected_filter === 'saved' ? ' active-filter' : '' ?>" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <span class="stats-card-icon">⭐</span>
                        <div class="stats-card-info">
                            <span class="stats-label">Zapisane lekcje</span>
                            <span class="stats-value" id="saved-count-value"><?= $savedCount ?></span>
                        </div>
                    </a>
                    <a href="student_dashboard.php<?= !empty($search_query) ? '?search=' . urlencode($search_query) : '' ?>" class="stats-card mini<?= empty($selected_filter) ? ' active-filter' : '' ?>" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <span class="stats-card-icon">📚</span>
                        <div class="stats-card-info">
                            <span class="stats-label">Wszystkie materiały</span>
                            <span class="stats-value"><?= count($notes) ?></span>
                        </div>
                    </a>
                </div>

                <!-- Premium Stats and Planner Widgets -->
                <div class="streak-badge-row" style="margin-top: 10px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px;">
                    <!-- Study Planner Card -->
                    <div class="glass-card planner-widget" style="display: flex; gap: 20px; align-items: center; padding: 20px;">
                        <div class="planner-progress-svg" style="position: relative; width: 90px; height: 90px; flex-shrink: 0;">
                            <svg width="90" height="90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" class="planner-circle-bg" style="fill: none; stroke: rgba(255,255,255,0.05); stroke-width: 8;"></circle>
                                <circle cx="50" cy="50" r="40" class="planner-circle-fg" style="fill: none; stroke: var(--accent-color); stroke-width: 8; stroke-linecap: round; stroke-dasharray: 251; stroke-dashoffset: <?= $strokeDashoffset ?>; transform: rotate(-90deg); transform-origin: 50px 50px; transition: stroke-dashoffset 0.6s ease;"></circle>
                            </svg>
                            <div class="planner-progress-text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 800; font-size: 1rem; color: #fff;"><?= $weeklyProgressPercent ?>%</div>
                        </div>
                        <div style="flex-grow: 1;">
                            <h3 style="font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 4px;">Twój Cel Tygodniowy</h3>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; margin-bottom: 8px;">Ukończono <strong><?= $weeklyWatchedCount ?></strong> z <?= $weeklyTarget ?> lekcji w ciągu ostatnich 7 dni.</p>
                            <div style="font-size: 0.72rem; color: #3ea6ff; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?= $weeklyProgressPercent >= 100 ? '🎉 Cel osiągnięty!' : 'Trzymaj tak dalej!' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Statistics Card -->
                    <div class="glass-card" style="padding: 20px; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <h3 style="font-size: 1rem; font-weight: 600; color: #fff;">Aktywność Przedmiotowa</h3>
                            <span style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase;">Top Przedmioty</span>
                        </div>
                        
                        <?php if (!$hasActivity): ?>
                            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: var(--text-secondary); padding: 20px 0;">
                                <span style="font-size: 1.5rem; margin-bottom: 4px;">📊</span>
                                <p style="font-size: 0.8rem; margin: 0;">Brak historii oglądania. Przejdź do lekcji, aby wygenerować statystyki.</p>
                            </div>
                        <?php else: ?>
                            <div style="flex-grow: 1; display: flex; align-items: flex-end; justify-content: center;">
                                <svg viewBox="0 0 400 130" class="chart-container-svg" style="width: 100%; height: 110px;">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#a855f7" />
                                            <stop offset="100%" stop-color="#6366f1" />
                                        </linearGradient>
                                    </defs>
                                    <line x1="30" y1="15" x2="390" y2="15" class="chart-grid-line" />
                                    <line x1="30" y1="55" x2="390" y2="55" class="chart-grid-line" />
                                    <line x1="30" y1="95" x2="390" y2="95" class="chart-axis-line" />
                                    
                                    <?php 
                                    $barWidth = 40;
                                    $gap = 25;
                                    $startX = 50;
                                    $graphHeight = 80; 
                                    foreach ($subjectLabels as $i => $label):
                                        $val = $subjectValues[$i];
                                        $pct = $val / $maxVal;
                                        $barHeight = $pct * $graphHeight;
                                        $barX = $startX + $i * ($barWidth + $gap);
                                        $barY = 95 - $barHeight;
                                    ?>
                                        <rect x="<?= $barX ?>" y="<?= $barY ?>" width="<?= $barWidth ?>" height="<?= $barHeight ?>" class="chart-bar-rect" />
                                        <text x="<?= $barX + $barWidth/2 ?>" y="112" class="chart-label-text" style="font-size: 9px; fill: var(--text-secondary); text-anchor: middle;"><?= htmlspecialchars($label) ?></text>
                                        <text x="<?= $barX + $barWidth/2 ?>" y="<?= $barY - 5 ?>" fill="#fff" font-size="9" text-anchor="middle" font-weight="600"><?= $val ?></text>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-panel student-toolbar">
                    <form method="GET" class="search-form" aria-label="Szukaj materiałów">
                        <?php if (!empty($selected_subject)): ?>
                            <input type="hidden" name="subject" value="<?= htmlspecialchars($selected_subject) ?>">
                        <?php endif; ?>
                        <?php if (!empty($selected_filter)): ?>
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($selected_filter) ?>">
                        <?php endif; ?>
                        <input type="search" name="search" class="form-control search-input" id="search" placeholder="Szukaj materiałów..." value="<?= htmlspecialchars($search_query) ?>">
                        <button type="submit" class="btn btn-secondary">Szukaj</button>
                    </form>

                    <div class="subject-chips-container" role="tablist" aria-label="Filtry przedmiotów">
                        <?php
                            $filterQs = !empty($selected_filter) ? 'filter=' . urlencode($selected_filter) : '';
                            $searchQs = !empty($search_query) ? 'search=' . urlencode($search_query) : '';
                            $baseParams = array_filter([$filterQs, $searchQs]);
                            $baseLink = 'student_dashboard.php' . (count($baseParams) > 0 ? '?' . implode('&', $baseParams) : '');
                            $separator = count($baseParams) > 0 ? '&' : '?';
                        ?>
                        <a href="<?= $baseLink ?>" class="chip-btn <?= empty($selected_subject) ? 'active' : '' ?>">Wszystkie</a>
                        <a href="<?= $baseLink . $separator ?>subject=subscriptions" class="chip-btn <?= $selected_subject === 'subscriptions' ? 'active' : '' ?>">Subskrypcje</a>
                        <a href="<?= $baseLink . $separator ?>subject=matematyka" class="chip-btn <?= $selected_subject === 'matematyka' ? 'active' : '' ?>">Matematyka</a>
                        <a href="<?= $baseLink . $separator ?>subject=fizyka" class="chip-btn <?= $selected_subject === 'fizyka' ? 'active' : '' ?>">Fizyka</a>
                        <a href="<?= $baseLink . $separator ?>subject=biologia" class="chip-btn <?= $selected_subject === 'biologia' ? 'active' : '' ?>">Biologia</a>
                        <a href="<?= $baseLink . $separator ?>subject=chemia" class="chip-btn <?= $selected_subject === 'chemia' ? 'active' : '' ?>">Chemia</a>
                        <a href="<?= $baseLink . $separator ?>subject=geografia" class="chip-btn <?= $selected_subject === 'geografia' ? 'active' : '' ?>">Geografia</a>
                        <a href="<?= $baseLink . $separator ?>subject=historia" class="chip-btn <?= $selected_subject === 'historia' ? 'active' : '' ?>">Historia</a>
                        <a href="<?= $baseLink . $separator ?>subject=polski" class="chip-btn <?= $selected_subject === 'polski' ? 'active' : '' ?>">Język Polski</a>
                        <a href="<?= $baseLink . $separator ?>subject=angielski" class="chip-btn <?= $selected_subject === 'angielski' ? 'active' : '' ?>">Język Angielski</a>
                        <a href="<?= $baseLink . $separator ?>subject=inne" class="chip-btn <?= $selected_subject === 'inne' ? 'active' : '' ?>">Inne</a>
                    </div>

                    <div class="result-count">
                        <?= count($notes) ?> materiałów w wynikach
                        <?php if ($selected_filter === 'saved'): ?>
                            • Filtr: Zapisane lekcje
                        <?php elseif ($selected_filter === 'subscriptions'): ?>
                            • Filtr: Subskrypcje
                        <?php endif; ?>
                        <?php if ($selected_subject !== '' && $selected_subject !== 'subscriptions'): ?>
                            • Przedmiot: <?= htmlspecialchars($selected_subject) ?>
                        <?php elseif ($selected_subject === 'subscriptions'): ?>
                            • Subskrypcje
                        <?php endif; ?>
                        <?php if ($search_query !== ''): ?>
                            • Szukaj: "<?= htmlspecialchars($search_query) ?>"
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if (empty($notes)): ?>
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <h3>Nie znaleziono żadnych materiałów</h3>
                    <p style="margin-top: 10px;">Wyczyść filtry wyszukiwania lub zaczekaj na publikacje nauczycieli.</p>
                </div>
            <?php else: ?>
                <section class="dashboard-section">
                    <div class="note-grid">
                        <?php foreach ($notes as $note): 
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
                        $watchHref = 'watch.php?id=' . (int)$note['id'];
                        if ((($note['access_type'] ?? 'free') === 'premium') && (int)$note['user_id'] !== (int)$user_id && !in_array((int)$note['id'], $purchasedNoteIds, true)) {
                            $watchHref = 'paypal_mock.php?note_id=' . (int)$note['id'];
                        }
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
                            
                            <!-- YouTube Style Cards Metadata -->
                            <div class="note-details-youtube" style="display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; width: 100%;">
                                <div style="display: flex; gap: 12px; flex-grow: 1; min-width: 0;">
                                    <a href="channel.php?id=<?= $note['user_id'] ?>" class="note-creator-avatar note-creator-link" style="flex-shrink: 0;">
                                        <?= strtoupper(substr(htmlspecialchars($note['username']), 0, 1)) ?>
                                    </a>
                                    <div class="note-text-group" style="min-width: 0; flex-grow: 1;">
                                        <h4 class="note-title" style="margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <a href="<?= htmlspecialchars($watchHref) ?>" class="note-title-link"><?= htmlspecialchars($note['title']) ?></a>
                                        </h4>
                                        <p class="note-author-name" style="margin-bottom: 2px;">
                                            <a href="channel.php?id=<?= $note['user_id'] ?>" class="note-creator-link"><?= htmlspecialchars($note['username']) ?></a> 
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
            <?php endif; ?>
        </main>
    </div>

    <script src="student_dashboard.js"></script>
    <script src="app.js"></script>
</body>
</html>
