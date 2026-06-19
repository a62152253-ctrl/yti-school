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
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
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
        }
        .history-item-card:hover {
            background: #272727;
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
</head>
<body>
    <!-- Topbar Header like YouTube -->
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
        <div class="yt-header-center">
            <form action="student_dashboard.php" method="GET" class="yt-search-form">
                <div class="yt-search-box">
                    <input type="text" name="search" placeholder="Szukaj lekcji, notatek, tagów...">
                </div>
                <button type="submit" class="yt-search-btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
        </div>
        <div class="yt-header-right">
            <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['username']) ?>">
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="app-container">
        <!-- Sidebar Navigation -->
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
                        <a href="my_lessons.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                            Moje Lekcje
                        </a>
                    </li>
                    <li class="active">
                        <a href="history.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Historia
                        </a>
                    </li>
                    <li>
                        <a href="playlists.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/></svg>
                            Playlisty
                        </a>
                    </li>
                </ul>
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
                <div class="glass-card" style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 20px; color: var(--accent-color);">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 style="color:#fff;">Twoja historia jest pusta</h3>
                    <p style="margin-top: 10px;">Otwieraj materiały ze strony głównej, aby pojawiały się na tej liście.</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($historyItems as $item): 
                        $isPres = $item['file_type'] === 'presentation';
                        $thumbnailUrl = '';
                        if ($isPres) {
                            $slides = json_decode($item['filepath'], true);
                            $thumbnailUrl = !empty($slides) ? $slides[0] : '';
                        } else {
                            $fileExtension = strtolower(pathinfo($item['filepath'], PATHINFO_EXTENSION));
                            $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                            if ($isImage) {
                                $thumbnailUrl = $item['filepath'];
                            }
                        }
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
                                    Nauczyciel: <strong><?= htmlspecialchars($item['username']) ?></strong> &bull; Wyświetlenia: <?= (int)$item['views'] ?> &bull; Otwarto: <?= date('Y-m-d H:i', strtotime($item['watched_at'])) ?>
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
</body>
</html>
