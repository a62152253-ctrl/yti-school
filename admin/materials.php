<!-- Tab 2: Materials -->
<section id="materialsSection" class="admin-content-section">
    <div class="table-controls">
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" id="materialsInputSearch" class="search-input" placeholder="Szukaj lekcji..." onkeyup="applyMaterialsFilters()">
            
            <select id="materialTypeFilter" class="filter-select" onchange="applyMaterialsFilters()">
                <option value="all">Wszystkie Pliki</option>
                <option value="presentation">Prezentacje</option>
                <option value="pdf">PDF</option>
                <option value="image">Obrazy</option>
            </select>

            <select id="materialAccessFilter" class="filter-select" onchange="applyMaterialsFilters()">
                <option value="all">Dostęp (Dowolny)</option>
                <option value="free">Darmowe (Free)</option>
                <option value="premium">Płatne (Premium)</option>
            </select>
        </div>
    </div>
    <div class="saas-table-wrapper" style="background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow-x: auto;">
        <table class="saas-table" id="materialsTable" style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left;">
                    <th class="sortable-header" onclick="sortTable('materialsTable', 0, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">ID <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 1, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Tytuł <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 2, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Autor <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 3, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Typ <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 4, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Przedmiot <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 5, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Typ dostępu <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 6, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Cena <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('materialsTable', 7, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Wyświetlenia <span class="sort-icon">↕</span></th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notes as $n): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);" 
                        data-type="<?= htmlspecialchars($n['file_type']) ?>" 
                        data-access="<?= htmlspecialchars($n['access_type']) ?>">
                        <td style="padding: 14px 16px;"><?= $n['id'] ?></td>
                        <td style="padding: 14px 16px;"><strong><?= htmlspecialchars($n['title']) ?></strong></td>
                        <td style="padding: 14px 16px;"><?= htmlspecialchars($n['username']) ?></td>
                        <td style="padding: 14px 16px; text-transform: uppercase; font-size: 0.8rem;"><?= htmlspecialchars($n['file_type']) ?></td>
                        <td style="padding: 14px 16px;"><span class="subject-badge default" style="font-size: 0.75rem; background:#272727; color:#f1f1f1; border-radius:12px; padding:4px 8px;"><?= htmlspecialchars($n['subject']) ?></span></td>
                        <td style="padding: 14px 16px; text-transform: capitalize;"><?= htmlspecialchars($n['access_type']) ?></td>
                        <td style="padding: 14px 16px; font-weight:700; color:#fbbf24;"><?= number_format((float)($n['premium_price'] ?? 0.0), 2, ',', ' ') ?> PLN</td>
                        <td style="padding: 14px 16px;"><?= (int)$n['views'] ?></td>
                        <td style="padding: 14px 16px; display:flex; gap:6px;">
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.75rem; width:auto; border-radius:4px; cursor:pointer;" 
                                    onclick="openEditPriceModal(<?= $n['id'] ?>, '<?= htmlspecialchars($n['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($n['access_type'], ENT_QUOTES) ?>', <?= (float)($n['premium_price'] ?? 0.0) ?>)">
                                Cena
                            </button>
                            
                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('Czy na pewno chcesz usunąć tę lekcję i plik fizyczny?')">
                                <?= SecurityEnterprise::csrfField() ?>
                                <input type="hidden" name="action" value="delete_material">
                                <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:0.75rem; width:auto; border:none; border-radius:4px; cursor:pointer;">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
