<?php
require_once 'db.php';
requireLogin();

if (!isStudentCreator()) {
    redirect('student_dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Handle delete comment request
$delete_id = SecurityEnterprise::getInt('delete_id', 0);
if ($delete_id > 0) {
    try {
        // Verify comment ownership (must be on one of this creator's notes)
        $stmt = $pdo->prepare("
            SELECT c.id 
            FROM comments c 
            JOIN notes n ON c.note_id = n.id 
            WHERE c.id = ? AND n.user_id = ?
        ");
        $stmt->execute([$delete_id, $user_id]);
        $comment = $stmt->fetch();

        if ($comment) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$delete_id]);
            $msg = "Komentarz został pomyślnie usunięty.";
        } else {
            $error = "Nie masz uprawnień do usunięcia tego komentarza.";
        }
    } catch (\PDOException $e) {
        $error = "Błąd bazy danych: " . $e->getMessage();
    }
}

// Fetch comments on creator's notes
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, u.username as author, n.title as note_title, n.id as note_id
        FROM comments c
        JOIN notes n ON c.note_id = n.id
        JOIN users u ON c.user_id = u.id
        WHERE n.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $comments = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
<?php
$pageTitle = 'Komentarze (Uczeń-Twórca) - Yti School';
require_once 'partials/head.php';
?>
    <style>
        .creator-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .comments-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }
        .comments-table th, .comments-table td {
            padding: 14px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.9rem;
        }
        .comments-table th {
            background: rgba(255,255,255,0.04);
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .comments-table tr:last-child td {
            border-bottom: none;
        }
        .comment-content {
            color: #fff;
            font-weight: 500;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>
<?php require_once 'partials/topbar.php'; ?>
    <div class="app-container">
        <?php 
        $activePage = 'student_creator_comments.php';
        require_once 'partials/sidebar.php'; 
        ?>
        <main class="main-content">
            <div class="creator-container" style="margin-top: 0; padding-top: 0;">

        <h1 style="font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 10px;">Zarządzaj komentarzami</h1>
        <p style="color: var(--text-secondary); margin-bottom: 30px;">Przeglądaj opinie uczniów i moderuj wypowiedzi pod Twoimi lekcjami.</p>

        <?php if (isset($msg)): ?>
            <div class="alert" style="background-color: rgba(48, 209, 88, 0.15); color: #30d158; border: 1px solid rgba(48, 209, 88, 0.2);"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert" style="background-color: rgba(255, 69, 58, 0.15); color: #ff453a; border: 1px solid rgba(255, 69, 58, 0.2);"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <div class="glass-card empty-state">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 15px;">💬</span>
                <h3>Brak komentarzy</h3>
                <p style="margin-top: 6px;">Nikt jeszcze nie skomentował Twoich publikacji.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="comments-table">
                    <thead>
                        <tr>
                            <th>Autor</th>
                            <th>Treść</th>
                            <th>Lekcja</th>
                            <th>Data</th>
                            <th>Opcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--accent-color); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                            <?= strtoupper(substr(htmlspecialchars($comment['author']), 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($comment['author']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-content" title="<?= htmlspecialchars($comment['content']) ?>">
                                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="watch.php?id=<?= (int)$comment['note_id'] ?>" target="_blank" style="color: #0a84ff; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <?= htmlspecialchars($comment['note_title']) ?>
                                    </a>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    <time datetime="<?= date('c', strtotime($comment['created_at'])) ?>">
                                        <?= date('d.m.Y', strtotime($comment['created_at'])) ?> <br>
                                        <span style="opacity: 0.7;"><?= date('H:i', strtotime($comment['created_at'])) ?></span>
                                    </time>
                                </td>
                                <td>
                                    <a href="student_creator_comments.php?delete_id=<?= (int)$comment['id'] ?>" 
                                       class="btn btn-secondary" 
                                       onclick="return confirm('Czy na pewno chcesz usunąć ten komentarz autora <?= htmlspecialchars(addslashes($comment['author'])) ?>?');"
                                       style="margin-bottom: 0; padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; background: rgba(255, 69, 58, 0.15); color: #ff453a; border-color: rgba(255, 69, 58, 0.2); transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Usuń
                                    </a>
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
