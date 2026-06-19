<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$is_teacher = isTeacher();

try {
    if ($is_teacher) {
        // Teachers see reports on their own uploaded notes
        $stmt = $pdo->prepare("SELECT r.*, n.title as note_title, u.username as reporter_username, n.filepath 
                               FROM reports r 
                               JOIN notes n ON r.note_id = n.id 
                               JOIN users u ON r.user_id = u.id 
                               WHERE n.user_id = ? 
                               ORDER BY r.created_at DESC");
        $stmt->execute([$user_id]);
        $reports = $stmt->fetchAll();

        // Calculate stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_notes, SUM(views) as total_views FROM notes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();
        $myNotesCount = $stats['total_notes'] ?? 0;
        $myViewsCount = $stats['total_views'] ?? 0;
    } else {
        // Students see reports they filed
        $stmt = $pdo->prepare("SELECT r.*, n.title as note_title, u.username as author_username 
                               FROM reports r 
                               JOIN notes n ON r.note_id = n.id 
                               JOIN users u ON n.user_id = u.id 
                               WHERE r.user_id = ? 
                               ORDER BY r.created_at DESC");
        $stmt->execute([$user_id]);
        $reports = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    $reports = [];
    $myNotesCount = 0;
    $myViewsCount = 0;
}

// Action to dismiss a report (secured with POST and CSRF)
if ($is_teacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dismiss') {
    try {
        SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
        $report_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($report_id > 0) {
            // Verify ownership first
            $stmt = $pdo->prepare("SELECT r.id FROM reports r JOIN notes n ON r.note_id = n.id WHERE r.id = ? AND n.user_id = ?");
            $stmt->execute([$report_id, $user_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
            }
        }
    } catch (\Exception $e) {
        // CSRF check failed or DB error
    }
    redirect('report.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zgłoszenia - Yti School Hub</title>
    <link rel="stylesheet" href="/styleapp.css">
    <style>
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            color: var(--text-primary);
        }
        .report-table th, .report-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--card-border);
        }
        .report-table th {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            font-weight: 600;
        }
        .report-table tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }
        .report-action-btn {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php 
        $activePage = 'report.php';
        require_once 'partials/sidebar.php'; 
        ?>

        <!-- Main Analytics/Report View -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2>Zgłoszenia i Statystyki</h2>
                    <p style="color: var(--text-secondary); margin-top: 5px;">
                        <?= $is_teacher ? 'Monitoruj zgłoszone nieprawidłowości w Twoich materiałach' : 'Lista przesłanych przez Ciebie zgłoszeń' ?>
                    </p>
                </div>
            </header>

            <?php if ($is_teacher): ?>
                <!-- Teacher specific stats -->
                <div class="stats-grid">
                    <div class="glass-card stat-card">
                        <span class="stat-label">Moje Opublikowane Notatki</span>
                        <span class="stat-value"><?= (int)$myNotesCount ?></span>
                    </div>
                    <div class="glass-card stat-card">
                        <span class="stat-label">Łączna Liczba Wyświetleń</span>
                        <span class="stat-value"><?= (int)$myViewsCount ?></span>
                    </div>
                    <div class="glass-card stat-card">
                        <span class="stat-label">Aktywne Zgłoszenia Błędów</span>
                        <span class="stat-value" style="color: var(--danger-color);"><?= count($reports) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table section -->
            <div class="glass-card" style="padding: 25px;">
                <h3 style="font-weight: 600; margin-bottom: 15px;">
                    <?= $is_teacher ? 'Zgłoszenia od uczniów' : 'Zgłoszone przeze mnie' ?>
                </h3>
                
                <?php if (empty($reports)): ?>
                    <p style="color: var(--text-secondary); padding: 20px 0; text-align: center;">Brak aktywnych zgłoszeń.</p>
                <?php else: ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Tytuł Materiału</th>
                                <th><?= $is_teacher ? 'Zgłaszający' : 'Nauczyciel' ?></th>
                                <th>Powód / Opis</th>
                                <th>Data</th>
                                <?php if ($is_teacher): ?>
                                    <th>Akcje</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['note_title']) ?></strong></td>
                                    <td><?= htmlspecialchars($is_teacher ? $r['reporter_username'] : $r['author_username']) ?></td>
                                    <td><?= htmlspecialchars($r['reason']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
                                    <?php if ($is_teacher): ?>
                                        <td>
                                            <form action="report.php" method="POST" class="dismiss-report-form" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="dismiss">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="report-action-btn btn-secondary" style="border:none; cursor:pointer;">Odrzuć</button>
                                            </form>
                                            <form action="delete_note.php" method="POST" class="delete-note-form" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= $r['note_id'] ?>">
                                                <button type="submit" class="report-action-btn btn-danger" style="border:none; cursor:pointer;">Usuń notatkę</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
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
            // Dismiss confirm
            document.querySelectorAll('.dismiss-report-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Odrzucić to zgłoszenie?')) {
                        e.preventDefault();
                    }
                });
            });

            // Delete confirm
            document.querySelectorAll('.delete-note-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Czy na pewno chcesz usunąć powiązany materiał?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
