<!-- Tab 4: Payments -->
<section id="paymentsSection" class="admin-content-section">
    <div class="table-controls">
        <h3 style="color:#fff; font-size:1.1rem; font-weight:500; margin:0;">Rejestr zakupów materiałów premium</h3>
    </div>
    <div class="saas-table-wrapper" style="background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow-x: auto;">
        <table class="saas-table" id="paymentsTable" style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left;">
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 0, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">ID <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 1, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Kupujący <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 2, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Lekcja <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 3, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Kwota (PLN) <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 4, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Status <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('paymentsTable', 5, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Data płatności <span class="sort-icon">↕</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #8e8e93;">Brak zarejestrowanych transakcji zakupowych.</td>
                    </tr>
                <?php else: foreach ($purchases as $p): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px;"><?= $p['id'] ?></td>
                        <td style="padding: 14px 16px;"><strong><?= htmlspecialchars($p['username']) ?></strong></td>
                        <td style="padding: 14px 16px;"><?= htmlspecialchars($p['title']) ?></td>
                        <td style="padding: 14px 16px; font-weight:700; color:#fbbf24;"><?= number_format($p['amount'], 2, ',', ' ') ?> PLN</td>
                        <td style="padding: 14px 16px;">
                            <span class="badge-admin <?= $p['payment_status'] === 'completed' ? 'verified' : 'unverified' ?>" style="font-size:0.75rem;">
                                <?= $p['payment_status'] ?>
                            </span>
                        </td>
                        <td style="padding: 14px 16px;"><?= date('d.m.Y H:i', strtotime($p['paid_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
