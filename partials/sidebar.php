<?php
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$userType = isTeacher() ? 'teacher' : 'student';
$studentClass = $_SESSION['class_level'] ?? '';
$links = [];
if ($userType === 'teacher') {
    $links = [
        ['href' => 'dashboard.php', 'label' => 'Panel Nauczyciela', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'active' => 'dashboard.php'],
        ['href' => 'upload.php', 'label' => 'Dodaj Materiał', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12', 'active' => 'upload.php'],
        ['href' => 'playlists.php', 'label' => 'Playlisty', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m10 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7', 'active' => 'playlists.php'],
        ['href' => 'teacher_orders.php', 'label' => 'Zamówienia Premium', 'icon' => 'M3 3h18v4H3V3zm0 7h18v11a2 2 0 01-2 2H5a2 2 0 01-2-2V10z', 'active' => 'teacher_orders.php'],
        ['href' => 'report.php', 'label' => 'Zgłoszenia / Raporty', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2', 'active' => 'report.php'],
        ['href' => 'notifications.php', 'label' => 'Powiadomienia', 'icon' => 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'active' => 'notifications.php'],
        ['href' => 'profile.php', 'label' => 'Mój Profil', 'icon' => 'M12 12a5 5 0 100-10 5 5 0 000 10zm-7 9a7 7 0 0114 0', 'active' => 'profile.php'],
    ];
} else {
    $links = [
        ['href' => 'student_dashboard.php', 'label' => 'Główna', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'active' => 'student_dashboard.php'],
        ['href' => 'notatki.php', 'label' => 'Notatki', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'active' => 'notatki.php'],
        ['href' => 'prezentacje.php', 'label' => 'Prezentacje', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'active' => 'prezentacje.php'],
        ['href' => 'my_lessons.php', 'label' => 'Ulubione', 'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z', 'active' => 'favorites.php'],
        ['href' => 'history.php', 'label' => 'Historia', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'active' => 'history.php'],
        ['href' => 'purchased.php', 'label' => 'Kupione', 'icon' => 'M3 7h18M5 7v13a1 1 0 001 1h12a1 1 0 001-1V7M8 7V4a2 2 0 012-2h4a2 2 0 012 2v3', 'active' => 'student_orders.php'],
        ['href' => 'cart.php', 'label' => 'Koszyk', 'icon' => 'M3 3h18v4H3V3zm0 7h18v11a2 2 0 01-2 2H5a2 2 0 01-2-2V10z', 'active' => 'cart.php'],
        ['href' => 'report.php', 'label' => 'Zgłoszenia / Raporty', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2', 'active' => 'report.php'],
        ['href' => 'notifications.php', 'label' => 'Powiadomienia', 'icon' => 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'active' => 'notifications.php'],
        ['href' => 'profile.php', 'label' => 'Mój Profil', 'icon' => 'M12 12a5 5 0 100-10 5 5 0 000 10zm-7 9a7 7 0 0114 0', 'active' => 'profile.php'],
    ];
}
?>
<aside class="sidebar">
    <nav style="width: 100%;">
        <ul class="nav-links">
            <?php foreach ($links as $link): ?>
                <li class="<?= $activePage === $link['active'] ? 'active' : '' ?>">
                    <a href="<?= $link['href'] ?>">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="<?= $link['icon'] ?>"/></svg>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($userType === 'student' && !empty($studentClass)): ?>
            <div class="sidebar-section-title">Klasa</div>
            <div class="sidebar-chip"><?= htmlspecialchars($studentClass) ?></div>
        <?php endif; ?>

        <?php if (!empty($notificationCount)): ?>
            <div class="sidebar-chip" style="margin-top: 12px;">Nowych powiadomień: <?= $notificationCount ?></div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="user-avatar"><?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U'), 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
                    <span class="user-role-badge"><?= $userType === 'teacher' ? 'Nauczyciel' : 'Student' ?></span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Wyloguj się</a>
        </div>
    </div>
</aside>
