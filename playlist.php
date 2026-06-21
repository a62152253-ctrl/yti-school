<?php
require_once 'db.php';
requireLogin();



$user_id = $_SESSION['user_id'];
$student_class = $_SESSION['class_level'] ?? '';
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$playlist_id) {
    redirect('playlists.php');
}

// Fetch playlist details
try {
    $stmt = $pdo->prepare("SELECT p.*, u.username 
                           FROM playlists p
                           JOIN users u ON p.user_id = u.id
                           WHERE p.id = ?");
    $stmt->execute([$playlist_id]);
    $playlist = $stmt->fetch();

    if (!$playlist) {
        redirect('playlists.php');
    }

    // Fetch notes inside this playlist
    $stmtNotes = $pdo->prepare("SELECT n.*, u.username FROM playlist_notes pn 
                                JOIN notes n ON pn.note_id = n.id 
                                JOIN users u ON n.user_id = u.id
                                WHERE pn.playlist_id = ? 
                                ORDER BY pn.position ASC, n.created_at ASC");
    $stmtNotes->execute([$playlist_id]);
    $notes = $stmtNotes->fetchAll();

    // Determine cover url from first note
    $cover_url = '';
    if (!empty($notes)) {
        $firstNote = $notes[0];
        if ($firstNote['file_type'] === 'presentation') {
            $cover_url = 'download.php?id=' . (int)$firstNote['id'] . '&slide=0';
        } elseif ($firstNote['file_type'] === 'image') {
            $cover_url = 'download.php?id=' . (int)$firstNote['id'];
        }
    }
} catch (\PDOException $e) {
    die("Błąd systemu: " . $e->getMessage());
}
?>
<?php
$pageTitle = $playlist['title'] . ' - Yti School';
$activePage = 'playlists.php';
require_once 'partials/head.php';
?>
    <style>
        .playlist-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        @media (max-width: 900px) {
            .playlist-layout {
                grid-template-columns: 1fr;
            }
        }
        /* YouTube style Playlist Left Banner */
        .playlist-cover-panel {
            background: linear-gradient(180deg, rgba(40, 40, 40, 0.95) 0%, rgba(20, 20, 20, 0.95) 100%);
            border-radius: 24px;
            padding: 24px;
            height: fit-content;
            border: 1px solid var(--card-border);
            position: sticky;
            top: 80px;
        }
        .playlist-cover-img {
            width: 100%;
            padding-top: 56.25%;
            position: relative;
            background: #121212;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }
        .playlist-cover-img img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .playlist-cover-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        /* Right Side Playlist Items List */
        .playlist-items-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .playlist-item-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 8px 12px;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.15s ease;
        }
        .playlist-item-row:hover {
            background: #272727;
        }
        .playlist-item-index {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 700;
            width: 20px;
            text-align: center;
        }
        .playlist-item-thumb {
            width: 120px;
            height: 68px;
            border-radius: 8px;
            overflow: hidden;
            background: #1f1f1f;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
            position: relative;
        }
        .playlist-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .playlist-item-thumb-preview {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-secondary);
        }
        .playlist-item-details {
            flex-grow: 1;
            overflow: hidden;
        }
        .playlist-item-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .playlist-item-meta {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }
    </style>
<?php
require_once 'partials/topbar.php';
?>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php 
        $activePage = 'playlists.php';
        require_once 'partials/sidebar.php'; 
        ?>

        <!-- Main Workspace -->
        <main class="main-content">
            <div class="playlist-layout">
                <!-- Left: Playlist Info Cover Banner -->
                <div class="playlist-cover-panel">
                    <div class="playlist-cover-img">
                        <?php if (!empty($cover_url)): ?>
                            <img src="<?= htmlspecialchars($cover_url) ?>" alt="Cover">
                        <?php else: ?>
                            <div class="playlist-cover-placeholder">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 style="font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 8px;"><?= htmlspecialchars($playlist['title']) ?></h2>
                    <div style="font-size: 0.85rem; color: #fff; opacity: 0.8; margin-bottom: 6px;">Nauczyciel: <strong><a href="channel.php?id=<?= $playlist['user_id'] ?>" style="color: #3ea6ff; text-decoration: none;"><?= htmlspecialchars($playlist['username']) ?></a></strong></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 20px;"><?= count($notes) ?> lekcji &bull; Ostatnia zmiana: <?= substr($playlist['created_at'], 0, 10) ?></div>
                    <p style="font-size: 0.88rem; color: #fff; line-height: 1.5; margin-bottom: 25px;"><?= htmlspecialchars($playlist['description'] ?: 'Brak opisu dla tej playlisty.') ?></p>

                    <?php if (!empty($notes)): ?>
                        <a href="watch.php?id=<?= $notes[0]['id'] ?>&playlist_id=<?= $playlist['id'] ?>" class="btn btn-primary" style="border-radius: 24px; text-transform: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Odtwórz wszystko
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Right: Playlist Items List -->
                <div class="playlist-items-list">
                    <?php if (empty($notes)): ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-secondary); background: #1f1f1f; border-radius: 12px; border: 1px solid var(--card-border);">
                            Brak lekcji w tej playliście.
                        </div>
                    <?php else: 
                        $idx = 1;
                        foreach ($notes as $n): 
                            $isItemPres = $n['file_type'] === 'presentation';
                            $itemThumb = '';
                            if ($isItemPres) {
                                $itemThumb = 'download.php?id=' . (int)$n['id'] . '&slide=0';
                            } elseif ($n['file_type'] === 'image') {
                                $itemThumb = 'download.php?id=' . (int)$n['id'];
                            }
                            ?>
                            <div class="playlist-item-row" draggable="<?= ($playlist['user_id'] == $user_id) ? 'true' : 'false' ?>" data-note-id="<?= $n['id'] ?>" style="cursor: <?= ($playlist['user_id'] == $user_id) ? 'grab' : 'default' ?>; display: flex; align-items: center; gap: 16px; padding: 8px 12px; border-radius: 12px; transition: background-color 0.15s ease;">
                            <?php if ($playlist['user_id'] == $user_id): ?>
                                <div class="drag-handle" style="color: var(--text-secondary); cursor: grab; display: flex; align-items: center;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M20 9H4v2h16V9zM4 15h16v-2H4v2z"/></svg>
                                </div>
                            <?php endif; ?>
                            <span class="playlist-item-index"><?= $idx++ ?></span>
                            
                            <a href="watch.php?id=<?= $n['id'] ?>&playlist_id=<?= $playlist['id'] ?>" style="display: flex; align-items: center; gap: 16px; text-decoration: none; color: inherit; flex-grow: 1; overflow: hidden;">
                                <div class="playlist-item-thumb">
                                    <?php if (!empty($itemThumb)): ?>
                                        <img src="<?= htmlspecialchars($itemThumb) ?>" alt="<?= htmlspecialchars($n['title']) ?>">
                                    <?php else: ?>
                                        <div class="playlist-item-thumb-preview">
                                            <?php if ($n['file_type'] === 'presentation'): ?>
                                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <?php else: ?>
                                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="note-badge"><?= htmlspecialchars($n['subject']) ?></span>
                                </div>

                                <div class="playlist-item-details">
                                    <div class="playlist-item-title"><?= htmlspecialchars($n['title']) ?></div>
                                    <div class="playlist-item-meta"><?= htmlspecialchars($n['username']) ?> &bull; <?= (int)$n['views'] ?> wyświetleń</div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.querySelector('.playlist-items-list');
            if (!list) return;

            let draggedItem = null;

            list.querySelectorAll('.playlist-item-row').forEach(row => {
                if (row.getAttribute('draggable') !== 'true') return;

                row.addEventListener('dragstart', (e) => {
                    draggedItem = row;
                    row.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                });

                row.addEventListener('dragend', () => {
                    draggedItem = null;
                    row.style.opacity = '1';
                    list.querySelectorAll('.playlist-item-row').forEach(r => {
                        r.style.borderTop = 'none';
                        r.style.borderBottom = 'none';
                    });
                });

                row.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const bounding = row.getBoundingClientRect();
                    const offset = bounding.y + bounding.height / 2;
                    if (e.clientY - offset > 0) {
                        row.style.borderBottom = '2px solid #3ea6ff';
                        row.style.borderTop = 'none';
                    } else {
                        row.style.borderTop = '2px solid #3ea6ff';
                        row.style.borderBottom = 'none';
                    }
                });

                row.addEventListener('dragleave', () => {
                    row.style.borderTop = 'none';
                    row.style.borderBottom = 'none';
                });

                row.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (!draggedItem || draggedItem === row) return;

                    const bounding = row.getBoundingClientRect();
                    const offset = bounding.y + bounding.height / 2;
                    
                    if (e.clientY - offset > 0) {
                        row.after(draggedItem);
                    } else {
                        row.before(draggedItem);
                    }

                    // Reset borders
                    row.style.borderTop = 'none';
                    row.style.borderBottom = 'none';

                    // Update index numbers visually
                    let idx = 1;
                    list.querySelectorAll('.playlist-item-index').forEach(span => {
                        span.textContent = idx++;
                    });

                    // Save new order via fetch AJAX
                    updatePlaylistOrder();
                });
            });

            function updatePlaylistOrder() {
                const noteIds = [];
                list.querySelectorAll('.playlist-item-row').forEach(row => {
                    noteIds.push(row.getAttribute('data-note-id'));
                });

                const formData = new FormData();
                formData.append('playlist_id', <?= $playlist_id ?>);
                formData.append('note_ids', JSON.stringify(noteIds));
                formData.append('csrf_token', '<?= SecurityEnterprise::csrfToken() ?>');

                fetch('update_playlist_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Wystąpił błąd podczas zapisywania kolejności.');
                    }
                })
                .catch(() => {
                    alert('Błąd połączenia z serwerem.');
                });
            }
        });
    </script>
</body>
</html>
