<?php
require_once '../db.php';
requireLogin();

if (!isTeacher()) {
    redirect('../student_dashboard.php');
}

$teacher_id = $_SESSION['user_id'];

// Fetch stats
try {
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as upload_count, SUM(views) as total_views FROM notes WHERE user_id = ?");
    $stmtStats->execute([$teacher_id]);
    $stats = $stmtStats->fetch();
    $uploadCount = $stats['upload_count'] ?? 0;
    $totalViews = $stats['total_views'] ?? 0;

    // Fetch subscriber count
    $stmtSub = $pdo->prepare("SELECT COUNT(*) as sub_count FROM subscriptions WHERE teacher_id = ?");
    $stmtSub->execute([$teacher_id]);
    $subCount = $stmtSub->fetch()['sub_count'] ?? 0;

    // Fetch all uploads (both presentations and notes)
    $stmtUploads = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
    $stmtUploads->execute([$teacher_id]);
    $uploads = $stmtUploads->fetchAll();

    // Fetch teacher profile details
    $stmtProfile = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtProfile->execute([$teacher_id]);
    $teacher = $stmtProfile->fetch();

} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mój Kanał - Yti School</title>
    <link rel="stylesheet" href="/styleapp.css">
    <style>
        .channel-banner {
            width: 100%;
            height: 160px;
            background: linear-gradient(90deg, #2c3e50 0%, #0f0f0f 100%);
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid var(--card-border);
        }
        .channel-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 0 8px;
        }
        .channel-left-group {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        .channel-big-avatar {
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
            border: 2px solid #272727;
        }
        .channel-meta-group h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .channel-handle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .channel-stats {
            font-size: 0.88rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Header Topbar like YouTube -->
    <header class="yt-header">
        <div class="yt-header-left">
            <a href="../dashboard.php" class="logo-section">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                </svg>
                <span class="yt-logo-text">yti School</span>
            </a>
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
                        <a href="../dashboard.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Panel Dydaktyczny
                        </a>
                    </li>
                    <li class="active">
                        <a href="channel_manager.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Mój Kanał
                        </a>
                    </li>
                    <li>
                        <a href="../report.php">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                            Zgłoszenia uczniów
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
                            <span class="user-role-badge">Nauczyciel</span>
                        </div>
                    </div>
                    <a href="../logout.php" class="logout-btn">Wyloguj się</a>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Channel Header Banner -->
            <div class="channel-banner"></div>

            <!-- Channel Header Info Row -->
            <div class="channel-header-info">
                <div class="channel-left-group">
                    <div class="channel-big-avatar">
                        <?= strtoupper(substr(htmlspecialchars($teacher['username']), 0, 1)) ?>
                    </div>
                    <div class="channel-meta-group">
                        <h1><?= htmlspecialchars($teacher['username']) ?></h1>
                        <div class="channel-handle">@<?= htmlspecialchars(strtolower($teacher['username'])) ?> &bull; <?= htmlspecialchars($teacher['email']) ?></div>
                        <div class="channel-stats"><?= $subCount ?> subskrybentów &bull; <?= $uploadCount ?> materiałów &bull; <?= (int)$totalViews ?> wyświetleń</div>
                    </div>
                </div>
                <div>
                    <a href="channel_edit.php" class="btn btn-secondary" style="border-radius: 20px; font-weight: 700; width: auto; padding: 10px 24px;">Dostosuj Kanał</a>
                </div>
            </div>

            <!-- Dashboard Stats widgets -->
            <div class="stats-grid" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Subskrypcje</span>
                    </div>
                    <div class="stat-value-group">
                        <span class="stat-value"><?= (int)$subCount ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Wszystkie Lekcje</span>
                    </div>
                    <div class="stat-value-group">
                        <span class="stat-value"><?= (int)$uploadCount ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Łączne Wyświetlenia</span>
                    </div>
                    <div class="stat-value-group">
                        <span class="stat-value"><?= (int)$totalViews ?></span>
                    </div>
                </div>
            </div>

            <!-- Published content management table -->
            <div class="saas-table-wrapper">
                <div style="padding: 20px; border-bottom: 1px solid var(--card-border);">
                    <h3 style="font-weight: 500; font-size: 1rem; color: #fff;">Materiały wideo na Twoim Kanale</h3>
                </div>

                <?php if (empty($uploads)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 40px 0; font-size: 0.9rem;">Twój kanał jest obecnie pusty. Wgraj materiały w panelu dydaktycznym.</p>
                <?php else: ?>
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th>Tytuł</th>
                                <th>Typ</th>
                                <th>Przedmiot</th>
                                <th>Klasa</th>
                                <th>Dostep</th>
                                <th>Wyświetlenia</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploads as $note): 
                                $subjClass = strtolower($note['subject']);
                                if (!in_array($subjClass, ['matematyka', 'fizyka', 'biologia', 'chemia'])) {
                                    $subjClass = 'default';
                                }
                                $typeLabel = $note['file_type'] === 'presentation' ? 'Prezentacja' : (pathinfo($note['filepath'], PATHINFO_EXTENSION) === 'pdf' ? 'PDF' : 'Zdjęcie');
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($note['title']) ?></strong></td>
                                    <td><span style="font-size: 0.8rem; opacity: 0.8;"><?= $typeLabel ?></span></td>
                                    <td><span class="subject-badge <?= $subjClass ?>"><?= htmlspecialchars($note['subject']) ?></span></td>
                                    <td><?= htmlspecialchars($note['class_level']) ?></td>
                                    <td>
                                        <?php if (($note['access_type'] ?? 'free') === 'premium'): ?>
                                            <span style="color: #f59e0b; font-weight: 700;">Premium <?= number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ') ?> PLN</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)$note['views'] ?></td>
                                    <td>
                                        <form action="../delete_note.php" method="POST" class="delete-note-form" style="display:inline;">
                                            <?= SecurityEnterprise::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.78rem; width: auto; border: none; cursor: pointer;">Usuń</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.delete-note-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Czy na pewno chcesz usunąć tę lekcję?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
