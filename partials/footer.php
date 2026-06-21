    <div class="toast-container" id="toastContainer"></div>
    <footer class="platform-footer">
        <span>© <?= date('Y') ?> Yti School Hub</span> • 
        <span>Powered with 💜 by <a href="#">Yti Team</a></span>
    </footer>
    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
    // Global Toast System
    window.showToast = function(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const icons = { success: '✅', error: '❌', info: 'ℹ️' };
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<span class="toast-icon">' + (icons[type] || '✅') + '</span><span>' + message + '</span>';
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };
    </script>
    <script src="app.js"></script>
</body>
</html>
