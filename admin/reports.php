<!-- Tab 3: Reports -->
<section id="reportsSection" class="admin-content-section">
    <div class="table-controls">
        <h3 style="color:#fff; font-size:1.1rem; font-weight:500; margin:0;">Materiały zgłoszone przez społeczność</h3>
    </div>
    <div class="saas-table-wrapper" style="background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow-x: auto;">
        <table class="saas-table" style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left;">
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">ID Raportu</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Zgłoszona lekcja</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Zgłaszający</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Powód zgłoszenia</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Data zgłoszenia</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Akcje moderacji</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #8e8e93;">Brak aktywnych zgłoszeń. System jest czysty!</td>
                    </tr>
                <?php else: foreach ($reports as $r): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px;"><?= $r['report_id'] ?></td>
                        <td style="padding: 14px 16px;">
                            <strong><a href="watch.php?id=<?= $r['note_id'] ?>" style="color:#818cf8; text-decoration:none;" target="_blank"><?= htmlspecialchars($r['title']) ?></a></strong>
                        </td>
                        <td style="padding: 14px 16px;"><?= htmlspecialchars($r['reporter']) ?></td>
                        <td style="padding: 14px 16px; color:#ef4444; font-size:0.9rem;"><?= htmlspecialchars($r['reason']) ?></td>
                        <td style="padding: 14px 16px;"><?= date('d.m.Y H:i', strtotime($r['reported_at'])) ?></td>
                        <td style="padding: 14px 16px; display:flex; gap:8px;">
                            <form action="" method="POST" class="inline-form">
                                <?= SecurityEnterprise::csrfField() ?>
                                <input type="hidden" name="action" value="dismiss_report">
                                <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                <button type="submit" class="btn btn-secondary" style="padding:6px 12px; font-size:0.75rem; width:auto; border:none; border-radius:6px; cursor:pointer;">Odrzuć</button>
                            </form>
                            
                            <form action="" method="POST" class="inline-form" onsubmit="return confirm('UWAGA: Spowoduje to całkowite usunięcie lekcji oraz wszystkich powiązanych zgłoszeń. Kontynuować?')">
                                <?= SecurityEnterprise::csrfField() ?>
                                <input type="hidden" name="action" value="delete_reported_material">
                                <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                <input type="hidden" name="note_id" value="<?= $r['note_id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 12px; font-size:0.75rem; width:auto; border:none; border-radius:6px; cursor:pointer; font-weight:700;">Usuń materiał</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
