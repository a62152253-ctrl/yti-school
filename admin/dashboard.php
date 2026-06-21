<!-- Tab 0: Kokpit -->
<section id="dashboardSection" class="admin-content-section active">
    <!-- Stats Grid -->
    <div class="stats-grid" style="margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #8e8e93;">Zarejestrowani Użytkownicy</span>
            <div style="font-size: 2rem; font-weight: 700; color: #fff; margin-top: 8px;"><?= $totalUsers ?></div>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #8e8e93;">Materiały dydaktyczne</span>
            <div style="font-size: 2rem; font-weight: 700; color: #fff; margin-top: 8px;"><?= $totalLessons ?></div>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #8e8e93;">Łączne Wyświetlenia</span>
            <div style="font-size: 2rem; font-weight: 700; color: #fff; margin-top: 8px;"><?= formatNumber($totalViews) ?></div>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #8e8e93;">Aktywne Zgłoszenia</span>
            <div style="font-size: 2rem; font-weight: 700; color: <?= $totalReports > 0 ? '#ef4444' : '#fff' ?>; margin-top: 8px;"><?= $totalReports ?></div>
        </div>
        <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #8e8e93;">Przychód SaaS (PLN)</span>
            <div style="font-size: 2rem; font-weight: 700; color: #fbbf24; margin-top: 8px;"><?= number_format($totalRevenue, 2, ',', ' ') ?> PLN</div>
        </div>
    </div>

    <!-- Charts and Logs Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="chart-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 20px; min-height: 300px; display: flex; flex-direction: column;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #fff; margin: 0 0 15px 0;">Nowe Rejestracje (Trend)</h3>
            <div style="flex-grow:1; position:relative;">
                <canvas id="userRegChart"></canvas>
            </div>
        </div>
        <div class="chart-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 20px; min-height: 300px; display: flex; flex-direction: column;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #fbbf24; margin: 0 0 15px 0;">Dzienny Przychód (PLN)</h3>
            <div style="flex-grow:1; position:relative;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Security Audit Log -->
    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 20px;">
        <h3 style="font-size: 1rem; font-weight: 600; color: #fff; margin: 0 0 15px 0; display:flex; align-items:center; gap:8px;">
            🛡️ Dziennik Zdarzeń Bezpieczeństwa (Audit Log)
        </h3>
        <div class="audit-log-box">
            <?php if (empty($audit_logs)): ?>
                <div class="audit-log-line" style="color:#8e8e93;">Brak zarejestrowanych zdarzeń w logu bezpieczeństwa.</div>
            <?php else: foreach ($audit_logs as $log): ?>
                <div class="audit-log-line">
                    <span style="color:#8e8e93;">[<?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($log['ts']))) ?>]</span>
                    <span style="color:#ef4444; font-weight:700;"><?= htmlspecialchars($log['event']) ?></span>
                    - IP: <?= htmlspecialchars($log['ip']) ?>
                    <?php if (!empty($log['context'])): ?>
                        <span style="color:#3ea6ff;">(<?= htmlspecialchars(json_encode($log['context'], JSON_UNESCAPED_UNICODE)) ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>
