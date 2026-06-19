document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('ytSearchInput');
    const suggestionsBox = document.getElementById('ytSearchSuggestions');

    if (searchInput && suggestionsBox) {
        let timer;
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            const query = searchInput.value.trim();
            if (query.length < 1) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
                return;
            }

            timer = setTimeout(() => {
                fetch('search_suggestions.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';
                        if (!Array.isArray(data) || data.length === 0) {
                            suggestionsBox.style.display = 'none';
                            return;
                        }
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-suggestion';
                            div.innerHTML = `
                                <div class="autocomplete-suggestion-icon">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <div style="flex-grow: 1;">${item.title}</div>
                                <span style="font-size: 0.72rem; opacity: 0.6; text-transform: uppercase;">${item.subject}</span>
                            `;
                            div.addEventListener('click', () => {
                                window.location.href = 'watch.php?id=' + item.id;
                            });
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    })
                    .catch(() => {
                        suggestionsBox.innerHTML = '';
                        suggestionsBox.style.display = 'none';
                    });
            }, 220);
        });

        document.addEventListener('click', (event) => {
            if (!searchInput.contains(event.target) && !suggestionsBox.contains(event.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
    }
});
