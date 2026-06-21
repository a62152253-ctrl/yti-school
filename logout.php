<?php
require_once 'db.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

$pageTitle = 'Wylogowywanie...';
require_once 'partials/head.php';
?>
<div class="auth-wrapper" style="background: radial-gradient(circle at center, #0e0e1a 0%, #05050a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999;">
    <div class="logout-card" style="text-align: center; padding: 40px; border-radius: 20px; background: rgba(18, 18, 28, 0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; align-items: center; max-width: 400px; width: 90%; animation: logoutFadeIn 0.6s ease-out;">
        
        <!-- Animated Brand Logo -->
        <div class="logout-logo-wrapper" style="margin-bottom: 24px; display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; border-radius: 16px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3); animation: pulseLogo 2s infinite ease-in-out;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
            </svg>
        </div>
        
        <h2 style="font-weight: 800; font-size: 1.6rem; color: #fff; margin-bottom: 8px; letter-spacing: -0.02em;">Bezpieczne wylogowywanie</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 30px;">Zamykamy Twoją sesję w Yti School...</p>
        
        <!-- Premium Loader Spinner -->
        <div class="logout-spinner" style="width: 36px; height: 36px; border: 3px solid rgba(255, 255, 255, 0.05); border-top-color: #6366f1; border-radius: 50%; animation: spinLoader 1s linear infinite; margin-bottom: 10px;"></div>
    </div>
</div>

<style>
@keyframes logoutFadeIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes pulseLogo {
    0%, 100% { transform: scale(1); box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5); }
}
@keyframes spinLoader {
    to { transform: rotate(360deg); }
}
</style>

<script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 1500);
</script>
</body>
</html>
