<!-- Tab 5: School Codes -->
<section id="schoolCodesSection" class="admin-content-section">
    <div class="table-controls">
        <h3 style="color:#fff; font-size:1.1rem; font-weight:500; margin:0;">Zarządzanie kodami rejestracyjnymi szkół</h3>
        <button type="button" class="btn-add" onclick="openAddSchoolCodeModal()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            Dodaj Kod Szkoły
        </button>
    </div>
    <div class="saas-table-wrapper" style="background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow-x: auto;">
        <table class="saas-table" id="schoolCodesTable" style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left;">
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Kod Aktywacyjny</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Nazwa Szkoły / Placówki</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase; width: 120px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schoolCodes)): ?>
                    <tr>
                        <td colspan="3" style="padding: 40px; text-align: center; color: #8e8e93;">Brak zdefiniowanych kodów szkół.</td>
                    </tr>
                <?php else: foreach ($schoolCodes as $sc): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px;"><code><?= htmlspecialchars($sc['code']) ?></code></td>
                        <td style="padding: 14px 16px;"><strong><?= htmlspecialchars($sc['school_name']) ?></strong></td>
                        <td style="padding: 14px 16px;">
                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('Czy na pewno chcesz usunąć ten kod szkoły?')">
                                <?= SecurityEnterprise::csrfField() ?>
                                <input type="hidden" name="action" value="delete_school_code">
                                <input type="hidden" name="code" value="<?= htmlspecialchars($sc['code']) ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 12px; font-size:0.75rem; width:auto; border:none; border-radius:6px; cursor:pointer;">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
