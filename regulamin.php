<?php
require_once 'db.php';
$pageTitle = 'Regulamin platformy - Yti School';
require_once 'partials/head.php';
?>
<div class="auth-wrapper auth-shell" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px;">
    <div class="auth-card" style="max-width: 800px; width: 100%; padding: 40px; background: #1c1c1e; border: 1px solid var(--card-border); border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.6);">
        <div class="auth-brand" style="margin-bottom: 24px;">
            <span class="auth-brand-mark" style="background: var(--accent-gradient); color: #fff; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; margin-right: 8px; vertical-align: middle;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                </svg>
            </span>
            <span style="font-weight: 800; font-size: 1.3rem; letter-spacing: -0.03em; vertical-align: middle; color: #fff;">yti School</span>
        </div>

        <h1 style="font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 12px;">Regulamin Platformy Edukacyjnej</h1>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 30px; line-height: 1.5;">Ostatnia aktualizacja: 20 czerwca 2026 r.</p>

        <div class="regulamin-content" style="color: #e5e5ea; font-size: 0.92rem; line-height: 1.6; max-height: 500px; overflow-y: auto; padding-right: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--card-border);">
            <h3 style="color: #fff; font-size: 1.1rem; margin-top: 20px; margin-bottom: 10px;">§ 1. Postanowienia ogólne</h3>
            <p>Niniejszy regulamin określa zasady korzystania z platformy edukacyjnej yti School, zwanej dalej "Platformą". Platforma służy do dzielenia się materiałami dydaktycznymi, robienia notatek oraz interaktywnej nauki.</p>

            <h3 style="color: #fff; font-size: 1.1rem; margin-top: 20px; margin-bottom: 10px;">§ 2. Rejestracja i role użytkowników</h3>
            <p>1. Użytkownikiem Platformy może być Uczeń, Uczeń-twórca lub Nauczyciel.<br>
            2. Rejestracja konta nauczycielskiego wymaga podania szkolnego adresu e-mail w domenie kończącej się na <code>.edu.pl</code> oraz przejścia weryfikacji przez administratora.<br>
            3. Każdy użytkownik jest zobowiązany do zachowania poufności swoich danych logowania.</p>

            <h3 style="color: #fff; font-size: 1.1rem; margin-top: 20px; margin-bottom: 10px;">§ 3. Korzystanie z materiałów i autorskie prawa</h3>
            <p>1. Materiały udostępniane na platformie (prezentacje, pliki PDF, zdjęcia) są chronione prawem autorskim.<br>
            2. Zabrania się rozpowszechniania pobranych materiałów poza platformą bez zgody ich autorów.<br>
            3. Platforma udostępnia darmowe oraz płatne materiały Premium (zakup jednorazowy).</p>

            <h3 style="color: #fff; font-size: 1.1rem; margin-top: 20px; margin-bottom: 10px;">§ 4. Odpowiedzialność i komentarze</h3>
            <p>1. Zabrania się publikowania na Platformie treści o charakterze wulgarnym, obraźliwym lub niezgodnym z polskim prawem.<br>
            2. System automatycznie cenzuruje wulgaryzmy w komentarzach i personalnych notatkach za pomocą dedykowanych filtrów.<br>
            3. Użytkownik ponosi pełną odpowiedzialność za dodawane przez siebie materiały i komentarze.</p>

            <h3 style="color: #fff; font-size: 1.1rem; margin-top: 20px; margin-bottom: 10px;">§ 5. Reklamacje i płatności</h3>
            <p>1. Płatności za materiały Premium są realizowane w bezpieczny sposób za pomocą powiązanych bramek płatniczych.<br>
            2. Reklamacje dotyczące działania Platformy można zgłaszać na adres e-mail wsparcia technicznego.</p>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button class="btn btn-primary" id="btn-back" style="width: auto; padding: 10px 24px;">Zamknij / Wróć</button>
        </div>
    </div>
</div>
<script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
    document.getElementById('btn-back').addEventListener('click', function() {
        if (window.opener || window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = 'register.php';
        }
    });
</script>
</body>
</html>
