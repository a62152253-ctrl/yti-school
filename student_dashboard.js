document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const showToast = (message) => {
        let toast = document.querySelector('.toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => {
            toast.classList.remove('is-visible');
        }, 2400);
    };

    const createSuggestion = (item) => {
        const div = document.createElement('div');
        div.className = 'autocomplete-suggestion';

        const icon = document.createElement('div');
        icon.className = 'autocomplete-suggestion-icon';
        icon.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';

        const content = document.createElement('div');
        content.style.flexGrow = '1';
        content.style.minWidth = '0';

        const title = document.createElement('div');
        title.style.fontWeight = '600';
        title.textContent = item.title || 'Bez tytułu';

        const author = document.createElement('div');
        author.style.fontSize = '0.82rem';
        author.style.color = 'rgba(255,255,255,0.72)';
        author.textContent = item.author_name || 'Autor anonimowy';

        const subject = document.createElement('span');
        subject.style.fontSize = '0.72rem';
        subject.style.opacity = '0.6';
        subject.style.textTransform = 'uppercase';
        subject.textContent = item.subject || '';

        content.append(title, author);
        div.append(icon, content, subject);
        div.addEventListener('click', () => {
            window.location.href = 'watch.php?id=' + encodeURIComponent(item.id);
        });

        return div;
    };

    document.querySelectorAll('.bookmark-card-btn').forEach(btn => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const noteId = btn.getAttribute('data-id');
            if (!noteId || btn.disabled) return;
            btn.disabled = true;

            fetch('toggle_lesson.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(noteId)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const svg = btn.querySelector('svg');
                        if (data.is_bookmarked) {
                            btn.style.color = 'var(--accent-color)';
                            if (svg) svg.setAttribute('fill', 'currentColor');
                        } else {
                            btn.style.color = 'var(--text-secondary)';
                            if (svg) svg.setAttribute('fill', 'none');
                        }

                        const savedValueEl = document.getElementById('saved-count-value');
                        if (savedValueEl) {
                            const currentCount = parseInt(savedValueEl.textContent, 10) || 0;
                            savedValueEl.textContent = data.is_bookmarked ? currentCount + 1 : Math.max(0, currentCount - 1);
                        }

                        showToast(data.is_bookmarked ? 'Lekcja zapisana.' : 'Lekcja usunięta z zapisanych.');
                    } else {
                        showToast(data.message || 'Błąd zapisu.');
                    }
                })
                .catch(() => showToast('Wystąpił błąd połączenia.'))
                .finally(() => {
                    btn.disabled = false;
                });
        });
    });

    const searchInput = document.getElementById('ytSearchInput');
    const suggestionsBox = document.getElementById('ytSearchSuggestions');

    if (searchInput && suggestionsBox) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();
            if (query.length < 1) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
                return;
            }

            fetch('search_suggestions.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (!Array.isArray(data) || data.length === 0) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }

                    data.forEach(item => suggestionsBox.appendChild(createSuggestion(item)));
                    suggestionsBox.style.display = 'block';
                })
                .catch(() => {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                });
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
    }
});
