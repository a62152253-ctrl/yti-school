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
<header class="yt-header" style="background: rgba(11, 15, 25, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s ease;">
    <div class="yt-header-left" style="display: flex; align-items: center;">
        <button class="menu-toggle-btn" id="menuToggle" aria-label="Menu" style="margin-right: 12px; display: inline-flex; align-items: center; justify-content: center; transition: transform 0.2s;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
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
        <form action="<?= $searchFormAction ?? basename($_SERVER['PHP_SELF']) ?>" method="GET" class="yt-search-form" id="ytSearchForm">
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
        <a href="profile.php" class="user-avatar" title="<?= htmlspecialchars($_SESSION['username'] ?? 'Mój Profil') ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; justify-content: center;">
            <?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'U'), 0, 1)) ?>
        </a>
    </div>
</header>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle control
    const menuToggle = document.getElementById('menuToggle');
    const appContainer = document.querySelector('.app-container');
    const backdrop = document.getElementById('sidebarBackdrop');

    // Restore sidebar state from local storage
    if (appContainer) {
        const isCollapsed = localStorage.getItem('sidebar-collapsed');
        if (isCollapsed === '1' || (isCollapsed === null && window.innerWidth <= 768)) {
            appContainer.classList.add('sidebar-collapsed');
        }
    }

    if (menuToggle && appContainer && backdrop) {
        menuToggle.addEventListener('click', () => {
            appContainer.classList.toggle('sidebar-collapsed');
            const collapsed = appContainer.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
            
            // Toggle backdrop on smaller screens
            if (window.innerWidth <= 768) {
                backdrop.classList.toggle('active', !collapsed);
            }
        });

        backdrop.addEventListener('click', () => {
            appContainer.classList.add('sidebar-collapsed');
            backdrop.classList.remove('active');
            localStorage.setItem('sidebar-collapsed', '1');
        });
    }
    // Search History & Autocomplete logic
    const searchForm = document.getElementById('ytSearchForm');
    const searchInput = document.getElementById('ytSearchInput');
    const suggestionsBox = document.getElementById('ytSearchSuggestions');

    if (searchInput && suggestionsBox) {
        let selectedIndex = -1;

        const getHistory = () => JSON.parse(localStorage.getItem('yt-recent-searches') || '[]');
        
        const saveSearch = (term) => {
            if (!term.trim()) return;
            let history = getHistory();
            history = history.filter(item => item.toLowerCase() !== term.toLowerCase());
            history.unshift(term.trim());
            localStorage.setItem('yt-recent-searches', JSON.stringify(history.slice(0, 5)));
        };

        const showSuggestions = (items, isHistory = false) => {
            suggestionsBox.innerHTML = '';
            selectedIndex = -1;

            if (items.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }

            if (isHistory) {
                const title = document.createElement('div');
                title.style.cssText = 'padding: 8px 14px; font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;';
                title.textContent = 'Ostatnie wyszukiwania';
                suggestionsBox.appendChild(title);

                items.forEach((item, idx) => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 10px 14px;';
                    div.setAttribute('data-index', idx);
                    div.setAttribute('data-value', item);
                    
                    const termText = document.createElement('span');
                    termText.textContent = item;
                    termText.style.color = '#fff';
                    
                    const clearBtn = document.createElement('span');
                    clearBtn.innerHTML = '✕';
                    clearBtn.style.cssText = 'color: #ff453a; font-size: 0.75rem; padding: 4px; cursor: pointer; margin-left: 10px;';
                    clearBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        let updated = getHistory().filter(h => h !== item);
                        localStorage.setItem('yt-recent-searches', JSON.stringify(updated));
                        renderHistoryOrFetch();
                    });

                    div.appendChild(termText);
                    div.appendChild(clearBtn);

                    div.addEventListener('click', () => {
                        searchInput.value = item;
                        saveSearch(item);
                        suggestionsBox.style.display = 'none';
                        if (searchForm) searchForm.submit();
                    });

                    suggestionsBox.appendChild(div);
                });
            } else {
                items.forEach((item, idx) => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.setAttribute('data-index', idx);
                    div.setAttribute('data-value', item.title);
                    div.style.cssText = 'display: flex; gap: 10px; align-items: center; cursor: pointer; padding: 10px 14px;';
                    div.innerHTML = `
                        <div class="autocomplete-suggestion-icon" style="color: var(--text-secondary); display: flex; align-items: center;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <div style="flex-grow: 1; color: #fff;">${item.title}</div>
                        <span style="font-size: 0.72rem; opacity: 0.6; text-transform: uppercase; color: var(--text-secondary);">${item.subject}</span>
                    `;

                    div.addEventListener('click', () => {
                        saveSearch(item.title);
                        window.location.href = 'watch.php?id=' + item.id;
                    });

                    suggestionsBox.appendChild(div);
                });
            }

            suggestionsBox.style.display = 'block';
        };

        const renderHistoryOrFetch = () => {
            const query = searchInput.value.trim();
            if (query.length === 0) {
                showSuggestions(getHistory(), true);
            } else if (query.length >= 1) {
                fetch('search_suggestions.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        showSuggestions(data, false);
                    });
            } else {
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
            }
        };

        searchInput.addEventListener('input', renderHistoryOrFetch);
        searchInput.addEventListener('focus', renderHistoryOrFetch);

        // Keyboard navigation (Arrow keys, Enter)
        searchInput.addEventListener('keydown', (e) => {
            const suggestionItems = suggestionsBox.querySelectorAll('.autocomplete-suggestion');
            if (suggestionItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % suggestionItems.length;
                highlightSuggestion(suggestionItems);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + suggestionItems.length) % suggestionItems.length;
                highlightSuggestion(suggestionItems);
            } else if (e.key === 'Enter') {
                if (selectedIndex > -1 && selectedIndex < suggestionItems.length) {
                    e.preventDefault();
                    suggestionItems[selectedIndex].click();
                }
            }
        });

        const highlightSuggestion = (items) => {
            items.forEach((item, idx) => {
                if (idx === selectedIndex) {
                    item.classList.add('selected');
                    item.style.background = '#272727';
                    const val = item.getAttribute('data-value');
                    if (val) searchInput.value = val;
                } else {
                    item.classList.remove('selected');
                    item.style.background = 'transparent';
                }
            });
        };

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        if (searchForm) {
            searchForm.addEventListener('submit', () => {
                saveSearch(searchInput.value);
            });
        }
    }
});
</script>
