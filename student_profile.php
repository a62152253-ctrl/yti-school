<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';
$target_student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$target_student_id) {
    redirect('student_dashboard.php');
}

// Fetch student details
try {
    $stmt = $pdo->prepare('SELECT id, username, email, type, class_level, is_student_creator, created_at FROM users WHERE id = ?');
    $stmt->execute([$target_student_id]);
    $targetUser = $stmt->fetch();
    
    if (!$targetUser) {
        redirect('student_dashboard.php');
    }
    
    // Redirect if they are a teacher
    if ($targetUser['type'] === 'teacher') {
        redirect('channel.php?id=' . $target_student_id);
    }
    
    // Stats queries
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
    $stmtCount->execute([$target_student_id]);
    $totalUploaded = (int)$stmtCount->fetchColumn();
    
    $stmtViews = $pdo->prepare("SELECT SUM(views) FROM notes WHERE user_id = ?");
    $stmtViews->execute([$target_student_id]);
    $totalViews = (int)$stmtViews->fetchColumn();
    
    // Shared notes list
    $stmtNotes = $pdo->prepare("SELECT n.*, u.username, u.type as user_type,
                                (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked 
                                FROM notes n 
                                JOIN users u ON n.user_id = u.id 
                                WHERE n.user_id = ? 
                                ORDER BY n.created_at DESC");
    $stmtNotes->execute([$user_id, $target_student_id]);
    $notes = $stmtNotes->fetchAll();
    
} catch (\PDOException $e) {
    die('Błąd systemu: ' . $e->getMessage());
}

// Calculate study streak dynamically for this user
// Calculate study streak dynamically for this user with timezone consideration
$streak = 0;
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(watched_at) as watch_date 
        FROM history 
        WHERE user_id = ? 
        ORDER BY watch_date DESC
    ");
    $stmt->execute([$target_student_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($dates)) {
        // Używamy obiektu DateTime dla większej precyzji w obliczeniach i problemów z czasem letnim
        $now = new DateTime();
        $todayStr = $now->format('Y-m-d');
        
        $yesterday = clone $now;
        $yesterday->modify('-1 day');
        $yesterdayStr = $yesterday->format('Y-m-d');

        if ($dates[0] === $todayStr || $dates[0] === $yesterdayStr) {
            $streak = 1;
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $currDate = new DateTime($dates[$i]);
                $nextDate = new DateTime($dates[$i + 1]);
                
                $diff = $nextDate->diff($currDate)->days;
                
                if ($diff === 1) {
                    $streak++;
                } else {
                    break;
                }
            }
        }
    }
} catch (\Exception $e) {
    error_log("Błąd obliczania serii dla użytkownika $target_student_id: " . $e->getMessage());
    $streak = 0;
}

// Calculate achievement badges for this user
$badgeExplorer = false;
$badgeCollector = false;
$badgeStudent = false;
$badgeSponsor = false;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE student_id = ?");
    $stmt->execute([$target_student_id]);
    $badgeExplorer = $stmt->fetchColumn() >= 1;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM my_lessons WHERE user_id = ?");
    $stmt->execute([$target_student_id]);
    $badgeCollector = $stmt->fetchColumn() >= 3;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE user_id = ?");
    $stmt->execute([$target_student_id]);
    $badgeStudent = $stmt->fetchColumn() >= 5;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE user_id = ?");
    $stmt->execute([$target_student_id]);
    $badgeSponsor = $stmt->fetchColumn() >= 1;
} catch (\PDOException $e) {}

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
$pageTitle = "Profil twórcy - " . htmlspecialchars($targetUser['username']) . " - Yti School";
$activePage = '';
require_once 'partials/head.php';
?>
    <style>
        .profile-banner {
            width: 100%;
            height: 150px;
            background: linear-gradient(90deg, #6366f1 0%, #a855f7 100%);
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid var(--card-border);
        }
        .profile-header-info {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
            padding: 0 8px;
        }
        @media (max-width: 600px) {
            .profile-header-info {
                flex-direction: column;
                text-align: center;
            }
        }
        .profile-big-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #3f3f3f;
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 2px solid #272727;
        }
        .profile-meta-group h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .profile-handle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .profile-stats {
            font-size: 0.88rem;
            color: var(--text-secondary);
        }
        .profile-tab-content {
            display: none;
        }
        .profile-tab-content.active {
            display: block;
        }
    </style>
<?php
require_once 'partials/topbar.php';
?>
    <div class="app-container">
        <?php require_once 'partials/sidebar.php'; ?>
        
        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Header Banner -->
            <div class="profile-banner"></div>

            <!-- Profile Info Row -->
            <div class="profile-header-info">
                <div class="profile-big-avatar">
                    <?= strtoupper(substr(htmlspecialchars($targetUser['username']), 0, 1)) ?>
                </div>
                <div class="profile-meta-group">
                    <h1><?= htmlspecialchars($targetUser['username']) ?></h1>
                    <div class="profile-handle">@<?= htmlspecialchars(strtolower($targetUser['username'])) ?> &bull; Student Twórca</div>
                    <div class="profile-stats">
                        <strong><?= $totalUploaded ?></strong> materiałów &bull; 
                        <strong><?= $totalViews ?></strong> wyświetleń &bull; 
                        <strong>🔥 <?= $streak ?></strong> dni serii nauki
                    </div>
                </div>
            </div>

            <!-- Tab Headers -->
            <div class="tab-headers">
                <button class="tab-header-btn active" id="btn-shared-tab" data-tab="shared-tab">Udostępnione materiały</button>
                <button class="tab-header-btn" id="btn-badges-tab" data-tab="badges-tab">Odznaki</button>
                <button class="tab-header-btn" id="btn-info-tab" data-tab="info-tab">Informacje</button>
            </div>

            <!-- TAB 1: Shared Files Grid -->
            <div id="shared-tab" class="profile-tab-content active">
                <?php if (empty($notes)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Ten student nie opublikował jeszcze żadnych materiałów.</p>
                <?php else: ?>
                    <div class="note-grid">
                        <?php foreach ($notes as $note): 
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
                                        <div class="note-creator-avatar" style="flex-shrink: 0;">
                                            <?= strtoupper(substr(htmlspecialchars($targetUser['username']), 0, 1)) ?>
                                        </div>
                                        <div class="note-text-group" style="min-width: 0; flex-grow: 1;">
                                            <h4 class="note-title" style="margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <a href="<?= htmlspecialchars($watchHref) ?>" class="note-title-link"><?= htmlspecialchars($note['title']) ?></a>
                                            </h4>
                                            <p class="note-author-name" style="margin-bottom: 2px;">
                                                <?= htmlspecialchars($targetUser['username']) ?> &bull; <?= htmlspecialchars($note['class_level'] ?? '') ?>
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
                <?php endif; ?>
            </div>

            <!-- TAB 2: Badges -->
            <div id="badges-tab" class="profile-tab-content">
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="font-weight:600; font-size:1.15rem; color:#fff; margin-bottom:15px;">Odznaki zaangażowania studenta</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 15px;">
                        <div class="glass-card" style="padding: 16px; display: flex; align-items: center; gap: 12px; opacity: <?= $badgeExplorer ? '1' : '0.4' ?>;">
                            <span style="font-size: 2rem;">🧭</span>
                            <div>
                                <h4 style="color:#fff; font-weight:600; font-size:0.9rem;">Młody Odkrywca</h4>
                                <p style="color:var(--text-secondary); font-size:0.75rem;"><?= $badgeExplorer ? 'Odblokowane' : 'Zablokowane (Subskrybuj nauczyciela)' ?></p>
                            </div>
                        </div>

                        <div class="glass-card" style="padding: 16px; display: flex; align-items: center; gap: 12px; opacity: <?= $badgeCollector ? '1' : '0.4' ?>;">
                            <span style="font-size: 2rem;">📂</span>
                            <div>
                                <h4 style="color:#fff; font-weight:600; font-size:0.9rem;">Kolekcjoner</h4>
                                <p style="color:var(--text-secondary); font-size:0.75rem;"><?= $badgeCollector ? 'Odblokowane' : 'Zablokowane (Zapisz 3+ lekcje)' ?></p>
                            </div>
                        </div>

                        <div class="glass-card" style="padding: 16px; display: flex; align-items: center; gap: 12px; opacity: <?= $badgeStudent ? '1' : '0.4' ?>;">
                            <span style="font-size: 2rem;">✍️</span>
                            <div>
                                <h4 style="color:#fff; font-weight:600; font-size:0.9rem;">Pilny Uczeń</h4>
                                <p style="color:var(--text-secondary); font-size:0.75rem;"><?= $badgeStudent ? 'Odblokowane' : 'Zablokowane (Ukończ 5+ lekcji)' ?></p>
                            </div>
                        </div>

                        <div class="glass-card" style="padding: 16px; display: flex; align-items: center; gap: 12px; opacity: <?= $badgeSponsor ? '1' : '0.4' ?>;">
                            <span style="font-size: 2rem;">💎</span>
                            <div>
                                <h4 style="color:#fff; font-weight:600; font-size:0.9rem;">Wspierający</h4>
                                <p style="color:var(--text-secondary); font-size:0.75rem;"><?= $badgeSponsor ? 'Odblokowane' : 'Zablokowane (Kup materiał premium)' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: About student -->
            <div id="info-tab" class="profile-tab-content">
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="font-weight:500; font-size:1.15rem; color:#fff; margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:8px;">O studencie</h3>
                    <ul style="list-style:none; display:flex; flex-direction:column; gap:12px; font-size:0.92rem; color:var(--text-secondary);">
                        <li>Rola w systemie: <strong style="color:#fff;">Student Twórca (Możliwość publikowania notatek)</strong></li>
                        <li>Poziom klasy: <strong style="color:#fff;"><?= htmlspecialchars($targetUser['class_level'] ?: 'Nieokreślono') ?></strong></li>
                        <li>Data rejestracji: <strong style="color:#fff;"><?= substr($targetUser['created_at'], 0, 10) ?></strong></li>
                        <li>Udostępnione materiały: <strong style="color:#fff;"><?= $totalUploaded ?></strong></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        function switchProfileTab(tabId) {
            document.querySelectorAll('.profile-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-header-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            document.getElementById('btn-' + tabId).classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tab-header-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.getAttribute('data-tab');
                    switchProfileTab(tabId);
                });
            });
        });
    </script>
    <script src="app.js"></script>
</body>
</html>
