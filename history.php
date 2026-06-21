<?php
require_once 'db.php';
requireLogin();

if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';

// Handle actions (clear all or remove individual note from history)
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'clear') {
        try {
            $stmt = $pdo->prepare("DELETE FROM history WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } catch (\PDOException $e) {}
        redirect('history.php');
    } elseif ($_GET['action'] === 'remove' && isset($_GET['id'])) {
        $note_id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM history WHERE user_id = ? AND note_id = ?");
            $stmt->execute([$user_id, $note_id]);
        } catch (\PDOException $e) {}
        redirect('history.php');
    }
}

// Fetch history items
try {
    $stmt = $pdo->prepare("SELECT n.*, u.username, h.watched_at,
                           (SELECT COUNT(*) FROM my_lessons WHERE user_id = ? AND note_id = n.id) as is_bookmarked
                           FROM history h
                           JOIN notes n ON h.note_id = n.id
                           JOIN users u ON n.user_id = u.id
                           WHERE h.user_id = ?
                           ORDER BY h.watched_at DESC");
    $stmt->execute([$user_id, $user_id]);
    $historyItems = $stmt->fetchAll();
} catch (\PDOException $e) {
    $historyItems = [];
}
?>
<?php
$pageTitle = 'Historia - Yti School';
$activePage = 'history.php';
require_once 'partials/head.php';
?>
    <style>
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-width: 1000px;
        }
        .history-item-card {
            display: flex;
            gap: 16px;
            padding: 12px;
            position: relative;
            cursor: pointer;
            text-decoration: none;
            background: #1f1f1f;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }
        .history-item-card:hover {
            background: #272727;
            transform: translateX(4px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .history-thumb-container {
            width: 168px;
            height: 94px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            background: #121212;
            border: 1px solid var(--card-border);
        }
        .history-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .history-item-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .history-item-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .history-item-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
        }
        .history-item-meta {
            margin-top: auto;
            font-size: 0.78rem;
            color: var(--text-secondary);
        }
        .remove-history-btn {
            background: rgba(0, 0, 0, 0.6);
            border: none;
            color: #ffffff;
            font-size: 1rem;
            cursor: pointer;
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
            text-decoration: none;
        }
        .remove-history-btn:hover {
            background: var(--danger-color);
        }
    </style>
<?php
require_once 'partials/topbar.php';
?>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php require_once 'partials/sidebar.php'; ?>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2>Historia Oglądania</h2>
                    <p style="color: var(--text-secondary); margin-top: 5px; font-size: 0.9rem;">Lista otwieranych przez Ciebie materiałów i lekcji</p>
                </div>
                <?php if (!empty($historyItems)): ?>
                    <a href="history.php?action=clear" id="clearHistoryBtn" class="btn btn-secondary" style="padding: 10px 20px; font-size: 0.82rem; width: auto; border-radius: 18px;">Wyczyść historię</a>
                <?php endif; ?>
            </header>

            <?php if (empty($historyItems)): ?>
                <div class="empty-state-card">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3>Twoja historia jest pusta</h3>
                    <p>Otwieraj materiały ze strony głównej, aby pojawiały się na tej liście.</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php 
                    $lastDateLabel = '';
                    foreach ($historyItems as $item): 
                        // Timeline date separator
                        $watchedDate = date('Y-m-d', strtotime($item['watched_at']));
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        if ($watchedDate === $today) { $dateLabel = 'Dzisiaj'; }
                        elseif ($watchedDate === $yesterday) { $dateLabel = 'Wczoraj'; }
                        else { $dateLabel = date('d.m.Y', strtotime($item['watched_at'])); }
                        
                        if ($dateLabel !== $lastDateLabel):
                            $lastDateLabel = $dateLabel;
                    ?>
                        <div class="timeline-date-separator"><?= $dateLabel ?></div>
                    <?php endif;
                        $isPres = $item['file_type'] === 'presentation';
                        $thumbnailUrl = '';
                        if ($isPres) {
                            $slides = json_decode($item['filepath'], true);
                            $thumbnailUrl = !empty($slides) ? 'download.php?id=' . (int)$item['id'] . '&slide=0' : '';
                        } else {
                            if (($item['file_type'] ?? '') === 'image') {
                                $thumbnailUrl = 'download.php?id=' . (int)$item['id'];
                            }
                        }
                        // Relative time
                        $watchedTimestamp = strtotime($item['watched_at']);
                        $diff = time() - $watchedTimestamp;
                        if ($diff < 3600) { $relTime = max(1, floor($diff / 60)) . ' min temu'; }
                        elseif ($diff < 86400) { $relTime = floor($diff / 3600) . ' godz. temu'; }
                        else { $relTime = floor($diff / 86400) . ' dni temu'; }
                    ?>
                        <div class="history-item-card" data-href="watch.php?id=<?= $item['id'] ?>">
                            <div class="history-thumb-container">
                                <span class="note-badge"><?= htmlspecialchars($item['subject']) ?></span>
                                <?php if (!empty($thumbnailUrl)): ?>
                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="history-thumb">
                                <?php else: ?>
                                    <div style="display:flex; justify-content:center; align-items:center; width:100%; height:100%; background: #121212;">
                                        <?php if ($item['file_type'] === 'presentation'): ?>
                                            <svg width="32" height="32" fill="none" stroke="var(--text-secondary)" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <?php else: ?>
                                            <svg width="32" height="32" fill="none" stroke="var(--text-secondary)" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="history-item-details">
                                <h3 class="history-item-title"><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="history-item-desc"><?= htmlspecialchars($item['description'] ?? 'Brak opisu.') ?></p>
                                <div class="history-item-meta">
                                    <?= htmlspecialchars($item['username']) ?> &bull; <?= (int)$item['views'] ?> wyświetleń &bull; <span style="color: var(--accent-color);"><?= $relTime ?></span>
                                </div>
                            </div>

                            <!-- Remove individual item from history -->
                            <a href="history.php?action=remove&id=<?= $item['id'] ?>" class="remove-history-btn">&times;</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        document.addEventListener('DOMContentLoaded', () => {
            // Confirm clear history
            const clearBtn = document.getElementById('clearHistoryBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    if (!confirm('Czy na pewno chcesz wyczyścić całą historię?')) {
                        e.preventDefault();
                    }
                });
            }

            // Click card to navigate
            document.querySelector('.history-list')?.addEventListener('click', (e) => {
                if (e.target.closest('.remove-history-btn')) return;
                
                const card = e.target.closest('.history-item-card');
                if (card) {
                    const href = card.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                }
            });

            // Confirm delete individual item
            document.querySelectorAll('.remove-history-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (!confirm('Czy chcesz usunąć tę lekcję z historii?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
<?php require_once 'partials/footer.php'; ?>
