<?php
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$userType = isTeacher() ? 'teacher' : 'student';
$studentClass = $_SESSION['class_level'] ?? '';
$links = [];
if ($userType === 'teacher') {
    $links = [
        ['href' => 'dashboard.php', 'label' => 'Panel Nauczyciela', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'active' => 'dashboard.php'],
        ['href' => 'teacher_channel_manager.php', 'label' => 'Mój Kanał', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'active' => 'teacher_channel_manager.php'],
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
    ];
    if (isStudentCreator()) {
        $links[] = ['href' => 'student_creator_dashboard.php', 'label' => 'Panel Twórcy', 'icon' => 'M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z', 'active' => 'student_creator_dashboard.php'];
    }
    $links = array_merge($links, [
        ['href' => 'page_notes.php', 'label' => 'Notatki', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'active' => 'page_notes.php'],
        ['href' => 'page_presentations.php', 'label' => 'Prezentacje', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'active' => 'page_presentations.php'],
        ['href' => 'page_favorites.php', 'label' => 'Ulubione', 'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z', 'active' => 'page_favorites.php'],
        ['href' => 'history.php', 'label' => 'Historia', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'active' => 'history.php'],
        ['href' => 'purchased.php', 'label' => 'Kupione', 'icon' => 'M3 7h18M5 7v13a1 1 0 001 1h12a1 1 0 001-1V7M8 7V4a2 2 0 012-2h4a2 2 0 012 2v3', 'active' => 'purchased.php'],
        ['href' => 'cart.php', 'label' => 'Koszyk', 'icon' => 'M3 3h18v4H3V3zm0 7h18v11a2 2 0 01-2 2H5a2 2 0 01-2-2V10z', 'active' => 'cart.php'],
        ['href' => 'report.php', 'label' => 'Zgłoszenia / Raporty', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2', 'active' => 'report.php'],
        ['href' => 'notifications.php', 'label' => 'Powiadomienia', 'icon' => 'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'active' => 'notifications.php'],
        ['href' => 'profile.php', 'label' => 'Mój Profil', 'icon' => 'M12 12a5 5 0 100-10 5 5 0 000 10zm-7 9a7 7 0 0114 0', 'active' => 'profile.php'],
    ]);
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

        <?php
        if ($userType === 'student' && isset($pdo, $_SESSION['user_id'])) {
            try {
                $stmtSidebar = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM subscriptions s 
                    JOIN users u ON s.teacher_id = u.id 
                    WHERE s.student_id = ?
                ");
                $stmtSidebar->execute([$_SESSION['user_id']]);
                $sidebar_subs = $stmtSidebar->fetchAll();
                if (!empty($sidebar_subs)) {
                    echo '<div class="sidebar-section-title">Subskrypcje</div>';
                    echo '<ul class="nav-links">';
                    foreach ($sidebar_subs as $sub) {
                        $isActive = (basename($_SERVER['PHP_SELF']) === 'channel.php' && isset($_GET['id']) && $_GET['id'] == $sub['id']) ? 'active' : '';
                        echo '<li class="' . $isActive . '">';
                        echo '<a href="channel.php?id=' . $sub['id'] . '" style="display: flex; align-items: center; gap: 12px;">';
                        echo '<div class="user-avatar" style="width: 24px; height: 24px; font-size: 0.75rem;">' . strtoupper(substr(htmlspecialchars($sub['username']), 0, 1)) . '</div>';
                        echo '<span style="font-weight: normal;">' . htmlspecialchars($sub['username']) . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
            } catch (\PDOException $e) {}
        }
        ?>

        <?php if ($userType === 'student' && !empty($studentClass)): ?>
            <div class="sidebar-section-title">Klasa</div>
            <div class="sidebar-chip"><?= htmlspecialchars($studentClass) ?></div>
        <?php endif; ?>

        <?php if (!empty($notificationCount)): ?>
            <div class="sidebar-chip" style="margin-top: 12px;">Nowych powiadomień: <?= $notificationCount ?></div>
        <?php endif; ?>

        <!-- Theme Presets Switcher -->
        <div class="preset-switcher">
            <div class="preset-switcher-title">Motyw platformy</div>
            <div class="preset-dots">
                <div class="preset-dot" data-theme="violet" style="background: #6366f1;" title="Fioletowy Premium"></div>
                <div class="preset-dot" data-theme="emerald" style="background: #10b981;" title="Szmaragdowy Oasis"></div>
                <div class="preset-dot" data-theme="sapphire" style="background: #0a84ff;" title="Szafirowy Ocean"></div>
                <div class="preset-dot" data-theme="sunset" style="background: #ff453a;" title="Karminowy Zachód"></div>
                <div class="preset-dot" data-theme="gold" style="background: #ffd60a;" title="Złoty Cyber"></div>
            </div>
        </div>

        <!-- Audio Settings Switcher -->
        <div style="padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--card-border); margin-top: 5px;">
            <span style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Dźwięki</span>
            <button id="audioToggleBtn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 4px 10px; color: #fff; cursor: pointer; font-size: 0.8rem; outline: none; transition: all 0.2s ease;">
                🔇 Wyciszone
            </button>
        </div>
        
        <!-- Shortcuts Helper Trigger -->
        <div style="padding: 10px 14px; border-top: 1px solid var(--card-border); margin-top: 5px; margin-bottom: 10px;">
            <button id="showShortcutsBtn" style="background: transparent; border: 1px solid rgba(255,255,255,0.15); color: var(--text-secondary); font-size: 0.75rem; border-radius: 6px; padding: 6px 12px; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; outline: none;">
                ⌨️ Skróty (Wciśnij ?)
            </button>
        </div>
    </nav>

<script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
window.playSystemSound = (type = 'click') => {
    if (localStorage.getItem('yt-system-audio') !== '1') return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'click') {
            osc.frequency.setValueAtTime(600, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(150, ctx.currentTime + 0.08);
            gain.gain.setValueAtTime(0.04, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.08);
            osc.start();
            osc.stop(ctx.currentTime + 0.08);
        } else if (type === 'success') {
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);
            osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.06);
            osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.04, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.25);
            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } else if (type === 'delete') {
            osc.frequency.setValueAtTime(220, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(80, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.05, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.12);
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
        }
    } catch(e){}
};

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('yt-theme') || 'violet';
    document.body.setAttribute('data-theme', savedTheme);
    
    const dots = document.querySelectorAll('.preset-dot');
    dots.forEach(dot => {
        const themeName = dot.getAttribute('data-theme');
        if (themeName === savedTheme) {
            dot.classList.add('active');
        }
        dot.addEventListener('click', () => {
            dots.forEach(d => d.classList.remove('active'));
            dot.classList.add('active');
            document.body.setAttribute('data-theme', themeName);
            localStorage.setItem('yt-theme', themeName);
            window.playSystemSound('success');
        });
    });

    const audioBtn = document.getElementById('audioToggleBtn');
    const updateAudioBtn = () => {
        const enabled = localStorage.getItem('yt-system-audio') === '1';
        audioBtn.textContent = enabled ? '🔊 Dźwięki: Wł.' : '🔇 Wyciszone';
        audioBtn.style.background = enabled ? 'rgba(48, 209, 88, 0.15)' : 'rgba(255,255,255,0.05)';
        audioBtn.style.color = enabled ? '#30d158' : '#fff';
        audioBtn.style.borderColor = enabled ? 'rgba(48, 209, 88, 0.2)' : 'rgba(255,255,255,0.1)';
    };
    if (audioBtn) {
        audioBtn.addEventListener('click', () => {
            const enabled = localStorage.getItem('yt-system-audio') === '1';
            localStorage.setItem('yt-system-audio', enabled ? '0' : '1');
            updateAudioBtn();
            if (!enabled) {
                setTimeout(() => window.playSystemSound('success'), 50);
            }
        });
        updateAudioBtn();
    }

    const modalHTML = `
        <div class="shortcuts-modal" id="shortcutsModal">
            <div class="shortcuts-card">
                <div class="shortcuts-title">
                    <span>Skróty klawiszowe</span>
                    <span id="closeShortcutsModal" style="cursor:pointer; font-size:1.2rem;">✕</span>
                </div>
                <div class="shortcuts-list">
                    <div class="shortcut-item"><span>Przejdź do strony głównej</span><span class="shortcut-key">G + D</span></div>
                    <div class="shortcut-item"><span>Wyszukaj (aktywuj szukarkę)</span><span class="shortcut-key">S</span></div>
                    <div class="shortcut-item"><span>Toggluj menu boczne</span><span class="shortcut-key">M</span></div>
                    <div class="shortcut-item"><span>Włącz panel skrótów</span><span class="shortcut-key">?</span></div>
                    <div class="shortcut-item"><span>Zamknij dowolne okno</span><span class="shortcut-key">Esc</span></div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const shortcutsModal = document.getElementById('shortcutsModal');
    const closeBtn = document.getElementById('closeShortcutsModal');
    const showBtn = document.getElementById('showShortcutsBtn');

    const toggleShortcuts = (show) => {
        if (show) {
            shortcutsModal.classList.add('active');
            window.playSystemSound('click');
        } else {
            shortcutsModal.classList.remove('active');
        }
    };

    if (showBtn) showBtn.addEventListener('click', () => toggleShortcuts(true));
    if (closeBtn) closeBtn.addEventListener('click', () => toggleShortcuts(false));
    if (shortcutsModal) {
        shortcutsModal.addEventListener('click', (e) => {
            if (e.target === shortcutsModal) toggleShortcuts(false);
        });
    }

    let lastKey = '';
    document.addEventListener('keydown', (e) => {
        const activeTag = document.activeElement.tagName.toLowerCase();
        if (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select') return;

        if (e.key === '?') {
            toggleShortcuts(true);
        } else if (e.key === 'Escape') {
            toggleShortcuts(false);
        } else if (e.key.toLowerCase() === 's') {
            e.preventDefault();
            const searchInput = document.getElementById('ytSearchInput');
            if (searchInput) searchInput.focus();
        } else if (e.key.toLowerCase() === 'm') {
            const menuToggle = document.getElementById('menuToggle');
            if (menuToggle) menuToggle.click();
        } else if (e.key.toLowerCase() === 'd' && lastKey.toLowerCase() === 'g') {
            window.location.href = 'index.php';
        }
        lastKey = e.key;
    });

    document.querySelectorAll('a, button, .preset-dot').forEach(el => {
        el.addEventListener('click', () => {
            if (!el.id || el.id !== 'audioToggleBtn') {
                window.playSystemSound('click');
            }
        });
    });
});
</script>

    <div class="sidebar-footer">
        <div class="user-profile">
            <a href="profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; flex-grow: 1;">
                <div class="user-avatar"><?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U'), 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
                    <span class="user-role-badge"><?= $userType === 'teacher' ? 'Nauczyciel' : 'Student' ?></span>
                </div>
            </a>
            <a href="logout.php" class="logout-btn">Wyloguj się</a>
        </div>
    </div>
</aside>
