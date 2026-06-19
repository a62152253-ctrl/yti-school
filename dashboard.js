document.addEventListener('DOMContentLoaded', () => {
    // Tab switching logic
    const switchTab = (tabId) => {
        const tabs = document.querySelectorAll('.tab-content');
        const activeTab = Array.from(tabs).find(el => el.classList.contains('active'));
        const targetTab = document.getElementById(tabId);

        if (activeTab && targetTab && activeTab.id !== tabId) {
            // Fade out active tab
            activeTab.style.opacity = '0';
            activeTab.style.transform = 'translateY(6px)';
            
            setTimeout(() => {
                activeTab.classList.remove('active');
                
                // Prepare target tab
                targetTab.style.opacity = '0';
                targetTab.style.transform = 'translateY(-6px)';
                targetTab.classList.add('active');
                
                // Trigger reflow
                targetTab.offsetHeight;
                
                // Fade in target tab
                targetTab.style.opacity = '1';
                targetTab.style.transform = 'translateY(0)';
            }, 150);
        } else if (targetTab && !targetTab.classList.contains('active')) {
            targetTab.classList.add('active');
            targetTab.style.opacity = '1';
            targetTab.style.transform = 'translateY(0)';
        }

        document.querySelectorAll('.tab-header-btn').forEach(el => {
            const isTarget = el.id === 'btn-' + tabId || el.getAttribute('aria-controls') === tabId;
            el.classList.toggle('active', isTarget);
            el.setAttribute('aria-selected', isTarget ? 'true' : 'false');
        });
        
        const statusMsg = document.getElementById('status-msg');
        if (statusMsg) {
            statusMsg.style.display = 'none';
            statusMsg.style.opacity = '0';
        }
    };

    // Attach tab switching events to Action Cards
    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', () => {
            const tabId = card.getAttribute('data-tab');
            if (tabId) {
                switchTab(tabId);
            }
        });
    });

    // Attach tab switching events to Tab Header Buttons
    document.querySelectorAll('.tab-header-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('aria-controls');
            if (tabId) {
                switchTab(tabId);
            }
        });
    });

    // Auto-submit filters on change with debounce
    let filterTimeout;
    ['filter_subject', 'filter_type', 'sort'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                el.style.opacity = '0.6';
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    if (el.form) {
                        el.form.submit();
                    }
                }, 100);
            });
        }
    });

    // Toast utility
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

    // AJAX Like Toggle logic
    document.querySelectorAll('.like-toggle-btn').forEach(btn => {
        const noteId = btn.getAttribute('data-note-id');
        btn.addEventListener('click', () => {
            btn.disabled = true;
            fetch('toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `note_id=${noteId}&type=like&csrf_token=${csrfToken}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById(`likes-count-${noteId}`);
                    if (countEl) {
                        countEl.textContent = `♥ ${data.likes}`;
                    }
                    if (data.user_vote === 'like') {
                        btn.classList.add('active');
                        btn.style.color = '#ff453a';
                        btn.style.borderColor = 'rgba(255, 69, 58, 0.28)';
                        btn.style.background = 'rgba(255, 69, 58, 0.12)';
                    } else {
                        btn.classList.remove('active');
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        btn.style.background = '';
                    }
                    showToast(data.user_vote === 'like' ? 'Polubiono materiał.' : 'Cofnięto polubienie.');
                } else {
                    showToast(data.message || 'Wystąpił błąd.');
                }
            })
            .catch(() => showToast('Błąd połączenia.'))
            .finally(() => {
                btn.disabled = false;
            });
        });
    });

    // Share link copy logic
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.getAttribute('data-share-url');
            navigator.clipboard.writeText(url).then(() => {
                showToast('Skopiowano link do schowka!');
            }).catch(() => {
                showToast('Nie udało się skopiować linku.');
            });
        });
    });

    // Playlist Popover management logic
    document.querySelectorAll('.playlist-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const noteId = btn.getAttribute('data-note-id');
            
            // Remove existing popovers
            document.querySelectorAll('.playlist-popover').forEach(el => el.remove());
            
            // Create popover container
            const popover = document.createElement('div');
            popover.className = 'playlist-popover';
            
            // Calculate coordinates
            const rect = btn.getBoundingClientRect();
            popover.style.top = `${window.scrollY + rect.bottom + 5}px`;
            popover.style.left = `${window.scrollX + rect.left - 80}px`;
            
            if (!window.myPlaylists || window.myPlaylists.length === 0) {
                const emptyMsg = document.createElement('div');
                emptyMsg.style.cssText = 'font-size:0.8rem; color:#8e8e93; text-align:center; padding: 12px 0;';
                emptyMsg.innerHTML = 'Brak playlist.<br>Stwórz nową obok!';
                popover.appendChild(emptyMsg);
            } else {
                window.myPlaylists.forEach(pl => {
                    const isInPlaylist = window.playlistNotesMap && window.playlistNotesMap.some(pn => pn.playlist_id == pl.id && pn.note_id == noteId);
                    
                    const label = document.createElement('label');
                    label.className = 'playlist-popover-item';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.checked = isInPlaylist;
                    
                    checkbox.addEventListener('change', () => {
                        checkbox.disabled = true;
                        fetch('add_to_playlist.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `note_id=${noteId}&playlist_id=${pl.id}&csrf_token=${csrfToken}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                if (data.added) {
                                    if (window.playlistNotesMap) {
                                        window.playlistNotesMap.push({ playlist_id: pl.id, note_id: noteId });
                                    }
                                    checkbox.checked = true;
                                } else {
                                    if (window.playlistNotesMap) {
                                        const idx = window.playlistNotesMap.findIndex(pn => pn.playlist_id == pl.id && pn.note_id == noteId);
                                        if (idx !== -1) window.playlistNotesMap.splice(idx, 1);
                                    }
                                    checkbox.checked = false;
                                }
                            } else {
                                showToast(data.message || 'Wystąpił błąd.');
                                checkbox.checked = !checkbox.checked;
                            }
                        })
                        .catch(() => {
                            showToast('Błąd połączenia.');
                            checkbox.checked = !checkbox.checked;
                        })
                        .finally(() => {
                            checkbox.disabled = false;
                        });
                    });
                    
                    label.append(checkbox, document.createTextNode(pl.title));
                    popover.appendChild(label);
                });
            }
            
            // Prevent clicks inside popover from bubbling and closing itself
            popover.addEventListener('click', (e) => e.stopPropagation());
            
            document.body.appendChild(popover);
        });
    });
    
    // Auto-close popover when clicking anywhere else
    document.addEventListener('click', () => {
        document.querySelectorAll('.playlist-popover').forEach(el => el.remove());
    });

    // Confirm note deletion
    document.querySelectorAll('form[action="delete_note.php"]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Czy na pewno chcesz usunąć tę lekcję?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm playlist deletion
    document.querySelectorAll('form.delete-playlist-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Czy na pewno chcesz usunąć tę playlistę? Powiązane lekcje nie zostaną usunięte.')) {
                e.preventDefault();
            }
        });
    });

    // Setup Presentation upload script behaviors
    const presDrop = document.getElementById('pres-drop-area');
    const presFiles = document.getElementById('presentationFiles');
    const presForm = document.getElementById('presentationForm');
    const status = document.getElementById('status-msg');
    const progressContainer = document.querySelector('.progress-container');
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');

    const presAccessType = document.getElementById('pres_access_type');
    const presPremiumPrice = document.getElementById('pres_premium_price');

    const syncPresPremiumPrice = () => {
        const isPremium = presAccessType && presAccessType.value === 'premium';
        if (presPremiumPrice) {
            presPremiumPrice.disabled = !isPremium;
            presPremiumPrice.required = isPremium;
            if (!isPremium) {
                presPremiumPrice.value = '';
            }
        }
    };

    if (presAccessType) {
        presAccessType.addEventListener('change', syncPresPremiumPrice);
    }
    syncPresPremiumPrice();

    if (presDrop && presFiles) {
        presDrop.addEventListener('click', () => presFiles.click());
        presDrop.addEventListener('dragover', (e) => {
            e.preventDefault();
            presDrop.classList.add('dragover');
        });
        presDrop.addEventListener('dragleave', () => presDrop.classList.remove('dragover'));
        presDrop.addEventListener('drop', (e) => {
            e.preventDefault();
            presDrop.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                presFiles.files = e.dataTransfer.files;
                updatePresLabel(presFiles.files);
            }
        });

        presFiles.addEventListener('change', () => {
            if (presFiles.files.length) {
                updatePresLabel(presFiles.files);
            }
        });
    }

    function updatePresLabel(files) {
        const count = files.length;
        const p = presDrop.querySelector('p');
        if (p) {
            p.textContent = `Wybrano pliki: ${count} (maks. 15)`;
            p.style.color = '#fff';
            p.style.fontWeight = '600';
        }
    }

    const showStatusMsg = (msg, type) => {
        if (status) {
            status.className = `alert alert-${type}`;
            status.textContent = msg;
            status.style.display = 'block';
            status.style.opacity = '0';
            status.offsetHeight; // trigger reflow
            status.style.opacity = '1';
            status.style.transition = 'opacity 0.3s ease-out';
        }
    };

    if (presForm) {
        presForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!presFiles.files.length) {
                showStatusMsg('Proszę dodać przynajmniej jedno zdjęcie jako slajd.', 'danger');
                return;
            }

            const formData = new FormData(presForm);
            const xhr = new XMLHttpRequest();

            if (progressContainer) {
                progressContainer.style.display = 'block';
                progressContainer.style.opacity = '0';
                progressContainer.offsetHeight;
                progressContainer.style.opacity = '1';
                progressContainer.style.transition = 'opacity 0.3s ease-out';
            }
            if (progressBar) progressBar.style.width = '0%';
            if (progressText) progressText.textContent = 'Wysyłanie: 0%';
            if (status) status.style.display = 'none';

            xhr.upload.addEventListener('progress', (ev) => {
                if (ev.lengthComputable) {
                    const percent = Math.round((ev.loaded / ev.total) * 100);
                    if (progressBar) progressBar.style.width = percent + '%';
                    if (progressText) progressText.textContent = `Wysyłanie: ${percent}%`;
                }
            });

            xhr.addEventListener('load', () => {
                let res = {};
                try {
                    res = JSON.parse(xhr.responseText);
                } catch(err) {
                    res = { success: false, message: 'Wystąpił błąd parsowania odpowiedzi.' };
                }

                if (xhr.status === 200 && res.success) {
                    showStatusMsg(res.message, 'success');
                    setTimeout(() => { window.location.href = 'dashboard.php'; }, 1500);
                } else {
                    showStatusMsg(res.message || 'Błąd zapisu.', 'danger');
                    if (progressContainer) progressContainer.style.display = 'none';
                }
            });

            xhr.addEventListener('error', () => {
                showStatusMsg('Błąd sieciowy.', 'danger');
                if (progressContainer) progressContainer.style.display = 'none';
            });

            xhr.open('POST', 'create_presentation.php', true);
            xhr.send(formData);
        });
    }
});
