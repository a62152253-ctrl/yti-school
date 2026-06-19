document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('noteFile');
    const uploadForm = document.getElementById('uploadForm');
    const progressContainer = document.querySelector('.progress-container');
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');
    const statusMsg = document.getElementById('status-msg');
    const accessType = document.querySelector('#access_type, #note_access_type');
    const premiumPrice = document.querySelector('#premium_price, #note_premium_price');

    if (!uploadForm || !fileInput || !dropArea) {
        return;
    }

    if (accessType && premiumPrice) {
        const syncPremiumPrice = () => {
            const isPremium = accessType.value === 'premium';
            premiumPrice.disabled = !isPremium;
            premiumPrice.required = isPremium;
            if (!isPremium) {
                premiumPrice.value = '';
            }
        };

        accessType.addEventListener('change', syncPremiumPrice);
        syncPremiumPrice();
    }

    // Drag-and-drop actions
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropArea.classList.remove('dragover');
        }, false);
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) {
            fileInput.files = files;
            updateDropAreaLabel(files[0].name);
        }
    });

    dropArea.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            updateDropAreaLabel(fileInput.files[0].name);
        }
    });

    function updateDropAreaLabel(fileName) {
        const p = dropArea.querySelector('p');
        if (p) {
            p.textContent = `Wybrany plik: ${fileName}`;
            p.style.color = '#fff';
            p.style.fontWeight = '600';
        }
    }

    // Ajax Upload Form Submission
    uploadForm.addEventListener('submit', (e) => {
        e.preventDefault();

        if (!fileInput.files.length) {
            showStatus('Wybierz plik z notatką lub zdjęciem.', 'danger');
            return;
        }

        const formData = new FormData(uploadForm);
        const xhr = new XMLHttpRequest();

        // Show progress bar
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = 'Wysyłanie: 0%';
        statusMsg.style.display = 'none';

        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = `Wysyłanie: ${percentComplete}%`;
            }
        });

        // Request complete
        xhr.addEventListener('load', () => {
            let res = {};
            try {
                res = JSON.parse(xhr.responseText);
            } catch(err) {
                res = { success: false, message: 'Błąd serwera lub nieprawidłowa odpowiedź.' };
            }

            if (xhr.status === 200 && res.success) {
                showStatus(res.message || 'Materiały zostały opublikowane!', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            } else {
                showStatus(res.message || 'Wystąpił błąd podczas wysyłania.', 'danger');
                progressContainer.style.display = 'none';
            }
        });

        xhr.addEventListener('error', () => {
            showStatus('Błąd połączenia sieciowego. Wysyłanie nieudane.', 'danger');
            progressContainer.style.display = 'none';
        });

        xhr.open('POST', 'upload.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    });

    function showStatus(msg, type) {
        statusMsg.className = `alert alert-${type}`;
        statusMsg.innerHTML = msg;
        statusMsg.style.display = 'block';
    }
});
