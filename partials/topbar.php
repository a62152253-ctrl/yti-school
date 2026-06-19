<?php
$notificationCount = 0;
if (isset($pdo, $_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $notificationCount = (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        $notificationCount = 0;
    }
}
$homeLink = isTeacher() ? 'dashboard.php' : 'student_dashboard.php';
?>
<header class="yt-header">
    <div class="yt-header-left">
        <a href="<?= $homeLink ?>" class="logo-section">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
            </svg>
            <span class="yt-logo-text">yti School</span>
        </a>
    </div>
    <div class="yt-header-center">
        <?php if (!isset($hideSearch) || !$hideSearch): ?>
        <form action="<?= basename($_SERVER['PHP_SELF']) ?>" method="GET" class="yt-search-form">
            <div class="yt-search-box autocomplete-container">
                <input type="text" name="search" id="ytSearchInput" placeholder="Szukaj lekcji, notatek, tagów..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
                <div class="autocomplete-suggestions" id="ytSearchSuggestions"></div>
            </div>
            <button type="submit" class="yt-search-btn" aria-label="Szukaj">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
        <?php endif; ?>
    </div>
    <div class="yt-header-right">
        <?php if (isLoggedIn()): ?>
            <a href="notifications.php" class="header-icon" title="Powiadomienia">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <?php if ($notificationCount > 0): ?>
                    <span class="notification-badge"><?= $notificationCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['username'] ?? 'Użytkownik') ?>">
            <?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U'), 0, 1)) ?>
        </div>
    </div>
</header>
