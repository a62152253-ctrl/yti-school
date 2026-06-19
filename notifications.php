<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    $notif_id = (int)$_POST['notif_id'];
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user_id]);
    } catch (PDOException $e) {}
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notif'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    $notif_id = (int)$_POST['notif_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user_id]);
    } catch (PDOException $e) {}
    exit;
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {}
    exit;
}

// Fetch all notifications
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 100
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}

// Count unread
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}

$pageTitle = 'Powiadomienia - Yti School';
$activePage = 'notifications.php';
require_once 'partials/head.php';
require_once 'partials/topbar.php';
?>
    <div class="app-container">
<?php require_once 'partials/sidebar.php'; ?>
        <main class="main-content" style="max-width: 900px; margin: 0 auto;">
            <section class="dashboard-section">
                <div class="content-header dashboard-hero-lite">
                    <h2>Powiadomienia</h2>
                    <p>Bieżące informacje o zakupach, subskrypcjach i interakcjach.</p>
                </div>

                <div class="dashboard-panel glass-card" style="padding: 24px;">
                    <div class="notifications-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #ffffff;">
                                <?= $unread_count > 0 ? "Nieprzeczytane: <span style='color: var(--accent-color);' id='unread-count-badge'>" . $unread_count . "</span>" : "<span id='unread-count-badge'>Wszystkie przeczytane</span>" ?>
                            </h3>
                        </div>
                        <?php if ($unread_count > 0): ?>
                            <button class="btn btn-secondary" id="mark-all-btn" style="width: auto; padding: 8px 14px; font-size: 0.85rem;">Oznacz wszystkie jako przeczytane</button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 12px;">🔔</div>
                            <p style="color: var(--text-secondary); margin: 0;">Brak powiadomień</p>
                        </div>
                    <?php else: ?>
                        <!-- Category Tabs -->
                        <div class="notifications-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding: 4px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; width: max-content; max-width: 100%;">
                            <button class="tab-btn active" data-filter="all" style="background: var(--accent-gradient); color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 500; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;">
                                Wszystkie
                            </button>
                            <button class="tab-btn" data-filter="finance" style="background: transparent; color: var(--text-secondary); border: none; padding: 8px 16px; border-radius: 8px; font-weight: 500; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;">
                                💳 Finanse
                            </button>
                            <button class="tab-btn" data-filter="community" style="background: transparent; color: var(--text-secondary); border: none; padding: 8px 16px; border-radius: 8px; font-weight: 500; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;">
                                💬 Społeczność
                            </button>
                            <button class="tab-btn" data-filter="system" style="background: transparent; color: var(--text-secondary); border: none; padding: 8px 16px; border-radius: 8px; font-weight: 500; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;">
                                📢 Systemowe
                            </button>
                        </div>

                        <div class="notifications-list" style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($notifications as $notif): 
                                // Parse notification type from message
                                $is_purchase = strpos($notif['message'], 'Kupiłeś') !== false || strpos($notif['message'], 'za') !== false;
                                $is_subscription = strpos($notif['message'], 'obserwujesz') !== false || strpos($notif['message'], 'Subskrypcja') !== false;
                                $is_sale = strpos($notif['message'], 'kupił') !== false;
                                $is_comment = strpos($notif['message'], 'komentarz') !== false;
                                
                                $icon = '📢';
                                $category = 'system';
                                if ($is_purchase) { $icon = '💳'; $category = 'finance'; }
                                if ($is_subscription) { $icon = '⭐'; $category = 'community'; }
                                if ($is_sale) { $icon = '💰'; $category = 'finance'; }
                                if ($is_comment) { $icon = '💬'; $category = 'community'; }
                            ?>
                                <div class="notification-item" data-id="<?= $notif['id'] ?>" data-category="<?= $category ?>" data-read="<?= $notif['is_read'] ? '1' : '0' ?>" style="background: <?= $notif['is_read'] ? 'rgba(255, 255, 255, 0.02)' : 'rgba(99, 102, 241, 0.06)' ?>; border: 1px solid rgba(255, 255, 255, <?= $notif['is_read'] ? '0.05' : '0.12' ?>); border-radius: 12px; padding: 16px; display: flex; gap: 14px; align-items: flex-start; transition: all 0.2s ease; cursor: pointer;">
                                    <div style="font-size: 1.6rem; flex-shrink: 0;"><?= $icon ?></div>
                                    
                                    <div style="flex-grow: 1; min-width: 0;">
                                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                                <?= htmlspecialchars($notif['title']) ?>
                                            </h4>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="unread-dot" style="width: 8px; height: 8px; background: var(--accent-color); border-radius: 50%; display: inline-block; flex-shrink: 0;"></span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 0.85rem;">
                                            <?= htmlspecialchars($notif['message']) ?>
                                        </p>
                                        <div style="font-size: 0.75rem; color: #8e8e93;">
                                            <?= htmlspecialchars(date('d.m.Y H:i', strtotime($notif['created_at']))) ?>
                                        </div>
                                    </div>

                                    <div style="flex-shrink: 0; display: flex; gap: 8px;">
                                        <?php if ($notif['link']): ?>
                                            <a href="<?= htmlspecialchars($notif['link']) ?>" class="btn btn-secondary" style="width: auto; padding: 6px 12px; font-size: 0.8rem; text-decoration: none;">Przejdź</a>
                                        <?php endif; ?>
                                        <button type="button" class="delete-notif-btn" data-id="<?= $notif['id'] ?>" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: #8e8e93; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; transition: all 0.2s ease;">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="category-empty-state" style="display: none; padding: 60px 20px; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 12px;">📭</div>
                            <p style="color: var(--text-secondary); margin: 0;">Brak powiadomień w tej kategorii</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        const csrfToken = '<?= SecurityEnterprise::csrfToken() ?>';
        let currentFilter = 'all';

        function updateUnreadBadgeCount() {
            const unreadCount = document.querySelectorAll('.notification-item[data-read="0"]').length;
            const badge = document.getElementById('unread-count-badge');
            if (badge) {
                if (unreadCount > 0) {
                    badge.innerHTML = unreadCount;
                    badge.style.color = 'var(--accent-color)';
                    if (badge.parentElement) {
                        badge.parentElement.innerHTML = "Nieprzeczytane: <span style='color: var(--accent-color);' id='unread-count-badge'>" + unreadCount + "</span>";
                    }
                } else {
                    badge.innerHTML = "Wszystkie przeczytane";
                    badge.style.color = '#ffffff';
                    if (badge.parentElement) {
                        badge.parentElement.innerHTML = "<span id='unread-count-badge'>Wszystkie przeczytane</span>";
                    }
                    const markAllBtn = document.getElementById('mark-all-btn');
                    if (markAllBtn) markAllBtn.style.display = 'none';
                }
            }
        }

        function filterNotifications() {
            let visibleCount = 0;
            const items = document.querySelectorAll('.notification-item');
            
            items.forEach(item => {
                const category = item.getAttribute('data-category');
                if (currentFilter === 'all' || category === currentFilter) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            const emptyState = document.getElementById('category-empty-state');
            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        // Set up tab switching
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(t => {
                    t.classList.remove('active');
                    t.style.background = 'transparent';
                    t.style.color = 'var(--text-secondary)';
                });
                
                this.classList.add('active');
                this.style.background = 'var(--accent-gradient)';
                this.style.color = '#ffffff';
                
                currentFilter = this.getAttribute('data-filter');
                filterNotifications();
            });
        });

        // Click on notification to mark as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('button') || e.target.closest('a')) return;
                if (this.getAttribute('data-read') === '1') return;
                
                const id = this.getAttribute('data-id');
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'mark_read=1&notif_id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
                });
                
                this.setAttribute('data-read', '1');
                this.style.background = 'rgba(255, 255, 255, 0.02)';
                this.style.borderColor = 'rgba(255, 255, 255, 0.05)';
                this.querySelector('.unread-dot')?.remove();
                
                updateUnreadBadgeCount();
            });
        });

        // Delete button click
        document.querySelectorAll('.delete-notif-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                const item = this.closest('.notification-item');
                
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'delete_notif=1&notif_id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
                });
                
                item.style.opacity = '0';
                item.style.transform = 'translateY(10px)';
                item.style.transition = 'all 0.2s ease';
                setTimeout(() => {
                    item.remove();
                    updateUnreadBadgeCount();
                    filterNotifications();
                }, 200);
            });
        });

        // Mark all as read button
        document.getElementById('mark-all-btn')?.addEventListener('click', function() {
            fetch('notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'mark_all_read=1&csrf_token=' + encodeURIComponent(csrfToken)
            });
            
            document.querySelectorAll('.notification-item').forEach(item => {
                item.setAttribute('data-read', '1');
                item.style.background = 'rgba(255, 255, 255, 0.02)';
                item.style.borderColor = 'rgba(255, 255, 255, 0.05)';
                item.querySelector('.unread-dot')?.remove();
            });
            
            updateUnreadBadgeCount();
            this.style.display = 'none';
        });
    </script>

    <script src="app.js"></script>
</body>
</html>

