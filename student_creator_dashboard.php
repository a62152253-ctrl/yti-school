<?php
require_once 'db.php';
requireLogin();

if (!isStudentCreator()) {
    redirect('student_dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get simple stats
try {
    // Total uploaded notes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totalUploaded = (int)$stmt->fetchColumn();

    // Total views on uploaded notes
    $stmt = $pdo->prepare("SELECT SUM(views) FROM notes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totalViews = (int)$stmt->fetchColumn();
    
    // Fetch creator's notes
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $myNotes = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
<?php
$pageTitle = 'Panel Twórcy (Uczeń) - Yti School';
require_once 'partials/head.php';
?>
    <style>
        .creator-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .creator-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .creator-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .creator-title p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .creator-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            font-size: 2rem;
        }
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        .stat-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-top: 4px;
        }
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            overflow: hidden;
        }
        .notes-table th, .notes-table td {
            padding: 14px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.9rem;
        }
        .notes-table th {
            background: rgba(255,255,255,0.04);
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .notes-table tr:last-child td {
            border-bottom: none;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
    </style>
    <?php require_once 'partials/topbar.php'; ?>
    <div class="app-container">
        <?php 
        $activePage = 'student_creator_dashboard.php';
        require_once 'partials/sidebar.php'; 
        ?>
        <main class="main-content">
            <div class="creator-container" style="margin-top: 0; padding-top: 0;">

        <div class="creator-header">
            <div class="creator-title">
                <h1>Panel Twórcy (Uczeń)</h1>
                <p>Zarządzaj swoimi materiałami edukacyjnymi i sprawdzaj ich zasięgi.</p>
            </div>
            <div>
                <a href="upload_student.php" class="btn btn-primary" style="margin-bottom: 0;">Dodaj nową lekcję</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="creator-stats">
            <div class="stat-box">
                <span class="stat-icon">📚</span>
                <div class="stat-info">
                    <span class="stat-label">Twoje materiały</span>
                    <span class="stat-value"><?= $totalUploaded ?></span>
                </div>
            </div>
            <div class="stat-box">
                <span class="stat-icon">👁</span>
                <div class="stat-info">
                    <span class="stat-label">Łączne wyświetlenia</span>
                    <span class="stat-value"><?= $totalViews ?></span>
                </div>
            </div>
            <div class="stat-box">
                <span class="stat-icon">💬</span>
                <div class="stat-info">
                    <span class="stat-label">Komentarze</span>
                    <a href="student_creator_comments.php" style="color: #0a84ff; text-decoration: none; font-size: 0.8rem; margin-top: 4px;">Zarządzaj komentarzami →</a>
                </div>
            </div>
        </div>

        <!-- Uploaded Notes Table -->
        <h2 style="font-size: 1.2rem; font-weight: 600; color: #fff; margin-bottom: 15px;">Twoje publikacje</h2>
        
        <?php if (empty($myNotes)): ?>
            <div class="glass-card empty-state">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 15px;">📝</span>
                <h3>Brak opublikowanych materiałów</h3>
                <p style="margin-top: 6px; margin-bottom: 20px;">Nie dodałeś jeszcze żadnych lekcji ani notatek.</p>
                <a href="upload_student.php" class="btn btn-primary btn-sm">Dodaj pierwszą lekcję</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="notes-table">
                    <thead>
                        <tr>
                            <th>Tytuł</th>
                            <th>Przedmiot</th>
                            <th>Klasa</th>
                            <th>Typ</th>
                            <th>Wyświetlenia</th>
                            <th>Opcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myNotes as $note): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($note['title']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($note['subject'])) ?></td>
                                <td><?= htmlspecialchars($note['class_level']) ?></td>
                                <td><?= $note['file_type'] === 'presentation' ? 'Prezentacja' : strtoupper($note['file_type']) ?></td>
                                <td><?= $note['views'] ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="watch.php?id=<?= $note['id'] ?>" class="btn btn-secondary btn-sm" target="_blank" style="margin-bottom: 0; padding: 4px 10px;">Podgląd</a>
                                        <a href="student_creator_edit.php?id=<?= $note['id'] ?>" class="btn btn-secondary btn-sm" style="margin-bottom: 0; padding: 4px 10px; background: rgba(10, 132, 255, 0.15); color: #0a84ff; border-color: rgba(10, 132, 255, 0.2);">Edytuj</a>
                                        <a href="student_creator_delete.php?id=<?= $note['id'] ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tę publikację?');" style="margin-bottom: 0; padding: 4px 10px; background: rgba(255, 69, 58, 0.15); color: #ff453a; border-color: rgba(255, 69, 58, 0.2);">Usuń</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </div>
        </main>
    </div>
    <script src="app.js"></script>
</body>
</html>
