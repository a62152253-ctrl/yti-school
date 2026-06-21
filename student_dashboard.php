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

// Check if search, filter, or subject is active
$isSearchActive = !empty($search_query) || !empty($selected_subject) || !empty($selected_filter);

// Helper function to render a single note card (consistent across search list and carousels)
if (!function_exists('renderNoteCard')) {
    function renderNoteCard($note, $user_id, $purchasedNoteIds) {
        $isPres = $note['file_type'] === 'presentation';
        $thumbnailUrl = '';
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
        
        ob_start();
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
        <?php
        return ob_get_clean();
    }
}

// Build search query
$queryStr = "SELECT n.*, u.username, u.type as user_type, 
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

// Fetch carousels data if search is not active
$recentlyAdded = [];
$recommended = [];
$mySaved = [];
$mySubjects = [];
$popularInClass = [];

if (!$isSearchActive) {
    // 1. Ostatnio dodane
    try {
        $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                               (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                               FROM notes n 
                               JOIN users u ON n.user_id = u.id 
                               ORDER BY n.created_at DESC LIMIT 6");
        $stmt->execute([$user_id]);
        $recentlyAdded = $stmt->fetchAll();
    } catch (\PDOException $e) {}

    // 2. Polecane
    try {
        $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                               (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                               FROM notes n 
                               JOIN users u ON n.user_id = u.id 
                               ORDER BY n.views DESC, n.created_at DESC LIMIT 6");
        $stmt->execute([$user_id]);
        $recommended = $stmt->fetchAll();
    } catch (\PDOException $e) {}

    // 3. Twoje zapisane
    try {
        $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 1 as is_bookmarked 
                               FROM notes n 
                               JOIN users u ON n.user_id = u.id 
                               JOIN my_lessons ml ON n.id = ml.note_id 
                               WHERE ml.user_id = ? 
                               ORDER BY ml.created_at DESC LIMIT 6");
        $stmt->execute([$user_id]);
        $mySaved = $stmt->fetchAll();
    } catch (\PDOException $e) {}

    // 4. Twoje przedmioty (based on history)
    try {
        $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                               (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                               FROM notes n 
                               JOIN users u ON n.user_id = u.id 
                               WHERE LOWER(n.subject) IN (
                                   SELECT LOWER(n2.subject) 
                                   FROM history h 
                                   JOIN notes n2 ON h.note_id = n2.id 
                                   WHERE h.user_id = ? 
                                   GROUP BY n2.subject 
                                   ORDER BY COUNT(*) DESC
                               ) 
                               ORDER BY n.created_at DESC LIMIT 6");
        $stmt->execute([$user_id, $user_id]);
        $mySubjects = $stmt->fetchAll();
        if (empty($mySubjects)) {
            $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                                   (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                                   FROM notes n 
                                   JOIN users u ON n.user_id = u.id 
                                   ORDER BY n.views DESC LIMIT 6");
            $stmt->execute([$user_id]);
            $mySubjects = $stmt->fetchAll();
        }
    } catch (\PDOException $e) {}

    // 5. Popularne w Twojej klasie
    try {
        if (!empty($student_class)) {
            $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                                   (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                                   FROM notes n 
                                   JOIN users u ON n.user_id = u.id 
                                   WHERE n.class_level = ? 
                                   ORDER BY n.views DESC LIMIT 6");
            $stmt->execute([$user_id, $student_class]);
            $popularInClass = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("SELECT n.*, u.username, u.type as user_type, 
                                   (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                                   FROM notes n 
                                   JOIN users u ON n.user_id = u.id 
                                   ORDER BY n.views DESC LIMIT 6");
            $stmt->execute([$user_id]);
            $popularInClass = $stmt->fetchAll();
        }
    } catch (\PDOException $e) {}
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

// Calculate study streak dynamically
$studyStreak = 0;
$watchDates = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT DATE(watched_at) as watch_date FROM history WHERE user_id = ? ORDER BY watched_at DESC");
    $stmt->execute([$user_id]);
    $watchDates = array_column($stmt->fetchAll(), 'watch_date');
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if (count($watchDates) > 0) {
        if ($watchDates[0] === $today || $watchDates[0] === $yesterday) {
            $studyStreak = 1;
            $currentDate = $watchDates[0];
            for ($i = 1; $i < count($watchDates); $i++) {
                $expectedPrev = date('Y-m-d', strtotime($currentDate . ' -1 day'));
                if ($watchDates[$i] === $expectedPrev) {
                    $studyStreak++;
                    $currentDate = $watchDates[$i];
                } else {
                    break;
                }
            }
        }
    }
} catch (\PDOException $e) {}
if ($studyStreak === 0) {
    $studyStreak = 1; // Default starting streak
}

// Determine achievement badges
$badges = [
    ['icon' => '🔥', 'title' => 'Seria Nauki', 'desc' => "$studyStreak dni", 'unlocked' => true],
    ['icon' => '🦉', 'title' => 'Bystrzak', 'desc' => 'Ukończ 1 lekcję', 'unlocked' => (!empty($watchDates))],
    ['icon' => '⭐', 'title' => 'Kolekcjoner', 'desc' => 'Zapisz 3 lekcje', 'unlocked' => ($savedCount >= 3)],
    ['icon' => '🎓', 'title' => 'Wspierający', 'desc' => 'Subskrybuj nauczyciela', 'unlocked' => ($subscriptionsCount >= 1)]
];
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
        <main class="main-content animate__animated animate__fadeIn">
            <section class="dashboard-section">
                <div class="content-header dashboard-hero-lite">
                    <h2 id="dynamicGreeting">Panel ucznia</h2>
                    <div class="dashboard-quick-actions" aria-label="Szybkie akcje">
                        <a href="page_favorites.php" class="quick-action">Zapisane</a>
                        <a href="history.php" class="quick-action">Historia</a>
                        <a href="cart.php" class="quick-action">Koszyk</a>
                    </div>
                    <p id="dynamicGreetingQuote">Przeglądaj materiały, filtry i wyszukiwanie w jednym miejscu.</p>
                    <?php if (!isStudentCreator()): ?>
                        <div class="glass-card" style="margin-top: 15px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-left: 4px solid #0a84ff; background: rgba(10, 132, 255, 0.04); font-size: 0.85rem;">
                            <span style="color: #fff;">💡 Masz przydatne notatki? Aktywuj <strong>Panel Twórcy</strong> w swoim profilu, aby publikować materiały dla innych!</span>
                            <a href="profile.php" class="btn btn-secondary" style="width: auto; margin-bottom: 0; padding: 4px 10px; font-size: 0.75rem; border-color: rgba(10, 132, 255, 0.3); color: #0a84ff; background: rgba(10, 132, 255, 0.08); text-decoration: none;">Przejdź do profilu</a>
                        </div>
                    <?php endif; ?>
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

            <?php if ($isSearchActive): ?>
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
                            <?php foreach ($notes as $note) {
                                echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                            } ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php else: ?>
                <!-- Render Carousels (Premium Layout) -->
                
                <!-- Carousel 1: Ostatnio dodane -->
                <div class="carousel-section">
                    <div class="carousel-header">
                        <h3 class="carousel-title">Ostatnio dodane</h3>
                        <a href="page_notes.php" class="see-more-link">Zobacz więcej &rarr;</a>
                    </div>
                    <div class="carousel-container-wrapper">
                        <button class="carousel-nav-btn prev-btn" aria-label="Poprzedni" onclick="scrollCarousel(this, -1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="carousel-scroll-container">
                            <?php if (empty($recentlyAdded)): ?>
                                <div class="glass-card" style="padding: 30px; text-align: center; width: 100%; color: var(--text-secondary);">Brak nowych materiałów.</div>
                            <?php else: ?>
                                <?php foreach ($recentlyAdded as $note) {
                                    echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                                } ?>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-nav-btn next-btn" aria-label="Następny" onclick="scrollCarousel(this, 1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Carousel 2: Polecane -->
                <div class="carousel-section">
                    <div class="carousel-header">
                        <h3 class="carousel-title">Polecane</h3>
                        <a href="page_videos.php" class="see-more-link">Zobacz więcej &rarr;</a>
                    </div>
                    <div class="carousel-container-wrapper">
                        <button class="carousel-nav-btn prev-btn" aria-label="Poprzedni" onclick="scrollCarousel(this, -1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="carousel-scroll-container">
                            <?php if (empty($recommended)): ?>
                                <div class="glass-card" style="padding: 30px; text-align: center; width: 100%; color: var(--text-secondary);">Brak polecanych materiałów.</div>
                            <?php else: ?>
                                <?php foreach ($recommended as $note) {
                                    echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                                } ?>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-nav-btn next-btn" aria-label="Następny" onclick="scrollCarousel(this, 1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Carousel 3: Twoje zapisane -->
                <div class="carousel-section">
                    <div class="carousel-header">
                        <h3 class="carousel-title">Twoje zapisane</h3>
                        <a href="page_favorites.php" class="see-more-link">Zobacz więcej &rarr;</a>
                    </div>
                    <div class="carousel-container-wrapper">
                        <button class="carousel-nav-btn prev-btn" aria-label="Poprzedni" onclick="scrollCarousel(this, -1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="carousel-scroll-container">
                            <?php if (empty($mySaved)): ?>
                                <div class="glass-card" style="padding: 30px; text-align: center; width: 100%; color: var(--text-secondary); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                    <span>Twój folder zapisanych materiałów jest pusty.</span>
                                    <span style="font-size: 0.78rem;">Kliknij ikonę zakładki (⭐) na dowolnej karcie lekcji, aby dodać ją tutaj.</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($mySaved as $note) {
                                    echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                                } ?>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-nav-btn next-btn" aria-label="Następny" onclick="scrollCarousel(this, 1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Carousel 4: Twoje przedmioty -->
                <div class="carousel-section">
                    <div class="carousel-header">
                        <h3 class="carousel-title">Twoje przedmioty</h3>
                        <a href="page_notes.php" class="see-more-link">Zobacz więcej &rarr;</a>
                    </div>
                    <div class="carousel-container-wrapper">
                        <button class="carousel-nav-btn prev-btn" aria-label="Poprzedni" onclick="scrollCarousel(this, -1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="carousel-scroll-container">
                            <?php if (empty($mySubjects)): ?>
                                <div class="glass-card" style="padding: 30px; text-align: center; width: 100%; color: var(--text-secondary);">Brak powiązanych materiałów.</div>
                            <?php else: ?>
                                <?php foreach ($mySubjects as $note) {
                                    echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                                } ?>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-nav-btn next-btn" aria-label="Następny" onclick="scrollCarousel(this, 1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Carousel 5: Popularne w Twojej klasie -->
                <div class="carousel-section">
                    <div class="carousel-header">
                        <h3 class="carousel-title">Popularne w Twojej klasie</h3>
                        <a href="page_presentations.php" class="see-more-link">Zobacz więcej &rarr;</a>
                    </div>
                    <div class="carousel-container-wrapper">
                        <button class="carousel-nav-btn prev-btn" aria-label="Poprzedni" onclick="scrollCarousel(this, -1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="carousel-scroll-container">
                            <?php if (empty($popularInClass)): ?>
                                <div class="glass-card" style="padding: 30px; text-align: center; width: 100%; color: var(--text-secondary);">Brak popularnych materiałów dla Twojej klasy.</div>
                            <?php else: ?>
                                <?php foreach ($popularInClass as $note) {
                                    echo renderNoteCard($note, $user_id, $purchasedNoteIds);
                                } ?>
                            <?php endif; ?>
                        </div>
                        <button class="carousel-nav-btn next-btn" aria-label="Następny" onclick="scrollCarousel(this, 1)">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Floating Pomodoro Focus Timer Widget -->
    <div class="pomodoro-widget collapsed" id="pomodoroWidget">
        <div class="pomodoro-header">
            <span>⏱️ Skupienie</span>
            <span id="togglePomodoroCollapse" style="cursor: pointer; font-size: 0.8rem;" title="Rozwiń/Zwiń">▲</span>
        </div>
        <div class="pomodoro-timer" id="pomodoroDisplay">25:00</div>
        <div class="pomodoro-controls">
            <button class="pomodoro-btn" id="pomodoroStartBtn">Start</button>
            <button class="pomodoro-btn" id="pomodoroResetBtn">Reset</button>
        </div>
    </div>

    <!-- Floating Back to Top Button -->
    <div class="back-to-top" id="backToTopBtn" title="Wróć na górę">
        <span>▲</span>
    </div>

    <script src="student_dashboard.js"></script>
    <script src="app.js"></script>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
    window.scrollCarousel = (btn, direction) => {
        const container = btn.parentElement.querySelector('.carousel-scroll-container');
        if (container) {
            const scrollAmount = container.clientWidth * 0.75 * direction;
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Dynamic Greeting with animated emoji
        const greetingEl = document.getElementById('dynamicGreeting');
        const quoteEl = document.getElementById('dynamicGreetingQuote');
        if (greetingEl && quoteEl) {
            const hr = new Date().getHours();
            const username = <?= json_encode($_SESSION['username'] ?? 'Użytkowniku') ?>;
            let greeting = 'Witaj, ' + username + '! 👋';
            let quote = 'Złap dzisiaj nową wiedzę w jednym miejscu.';
            
            if (hr >= 5 && hr < 12) {
                greeting = 'Dzień dobry, ' + username + '! 🌅';
                quote = 'Poranna sesja nauki przynosi najlepsze rezultaty. Miłego dnia!';
            } else if (hr >= 12 && hr < 18) {
                greeting = 'Dzień dobry, ' + username + '! ☀️';
                quote = 'Sprawdzasz nowe lekcje? Powodzenia w zdobywaniu wiedzy!';
            } else if (hr >= 18 && hr < 22) {
                greeting = 'Dobry wieczór, ' + username + '! 🌌';
                quote = 'Spokojna, wieczorna nauka ułatwia zapamiętywanie materiału.';
            } else {
                greeting = 'Witaj nocny marku, ' + username + '! 🦉';
                quote = 'Pamiętaj o odpoczynku! Zdrowy sen to klucz do dobrej pamięci.';
            }
            greetingEl.textContent = greeting;
            quoteEl.textContent = quote;
        }

        // Count-up animation for stats values
        document.querySelectorAll('.stats-value').forEach(el => {
            const target = parseInt(el.textContent, 10);
            if (isNaN(target) || target === 0) return;
            el.textContent = '0';
            const duration = 800;
            const start = performance.now();
            const animate = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(target * eased);
                if (progress < 1) requestAnimationFrame(animate);
            };
            requestAnimationFrame(animate);
        });

        // Bookmark toast feedback
        document.querySelectorAll('.bookmark-card-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (window.showToast) {
                    const isActive = this.querySelector('svg').getAttribute('fill') === 'currentColor';
                    window.showToast(isActive ? 'Usunięto z zapisanych' : 'Zapisano lekcję!', 'success');
                }
            });
        });

        // 2. Floating Pomodoro Timer
        const pomodoroWidget = document.getElementById('pomodoroWidget');
        const toggleCollapse = document.getElementById('togglePomodoroCollapse');
        const startBtn = document.getElementById('pomodoroStartBtn');
        const resetBtn = document.getElementById('pomodoroResetBtn');
        const timerDisplay = document.getElementById('pomodoroDisplay');

        let timerInterval = null;
        let timeLeft = 25 * 60; // 25 minutes
        let timerRunning = false;

        const updateDisplay = () => {
            const mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const secs = (timeLeft % 60).toString().padStart(2, '0');
            timerDisplay.textContent = `${mins}:${secs}`;
        };

        if (toggleCollapse) {
            toggleCollapse.addEventListener('click', () => {
                pomodoroWidget.classList.toggle('collapsed');
                toggleCollapse.textContent = pomodoroWidget.classList.contains('collapsed') ? '▲' : '▼';
            });
        }

        if (startBtn) {
            startBtn.addEventListener('click', () => {
                if (timerRunning) {
                    clearInterval(timerInterval);
                    startBtn.textContent = 'Start';
                    timerRunning = false;
                } else {
                    timerRunning = true;
                    startBtn.textContent = 'Pauza';
                    timerInterval = setInterval(() => {
                        timeLeft--;
                        updateDisplay();
                        if (timeLeft <= 0) {
                            clearInterval(timerInterval);
                            timeLeft = 25 * 60;
                            updateDisplay();
                            startBtn.textContent = 'Start';
                            timerRunning = false;
                            if (window.playSystemSound) window.playSystemSound('success');
                            if (window.showToast) window.showToast('Czas minął! Zrób sobie krótką przerwę ☕️', 'success');
                        }
                    }, 1000);
                }
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                clearInterval(timerInterval);
                timeLeft = 25 * 60;
                updateDisplay();
                startBtn.textContent = 'Start';
                timerRunning = false;
            });
        }

        // 3. Floating Back to Top Button
        const bttBtn = document.getElementById('backToTopBtn');
        if (bttBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    bttBtn.classList.add('visible');
                } else {
                    bttBtn.classList.remove('visible');
                }
            });
            bttBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });
    </script>
<?php require_once 'partials/footer.php'; ?>
