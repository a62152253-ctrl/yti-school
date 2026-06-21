<?php
require_once 'db.php';
requireLogin();

if (!isTeacher()) {
    redirect('student_dashboard.php');
}

$teacher_id = $_SESSION['user_id'];

// Fetch stats
try {
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as upload_count, SUM(views) as total_views FROM notes WHERE user_id = ?");
    $stmtStats->execute([$teacher_id]);
    $stats = $stmtStats->fetch();
    $uploadCount = $stats['upload_count'] ?? 0;
    $totalViews = $stats['total_views'] ?? 0;

    // Fetch subscriber count
    $stmtSub = $pdo->prepare("SELECT COUNT(*) as sub_count FROM subscriptions WHERE teacher_id = ?");
    $stmtSub->execute([$teacher_id]);
    $subCount = $stmtSub->fetch()['sub_count'] ?? 0;

    // Fetch all uploads (both presentations and notes)
    $stmtUploads = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
    $stmtUploads->execute([$teacher_id]);
    $uploads = $stmtUploads->fetchAll();

    // Fetch teacher profile details
    $stmtProfile = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtProfile->execute([$teacher_id]);
    $teacher = $stmtProfile->fetch();

    // Helper to generate SVG Sparkline
    function generateSparklineSvg($dataPoints, $color = '#6366f1', $width = 110, $height = 40) {
        if (count($dataPoints) < 2) return '';
        $max = max($dataPoints) ?: 1;
        $min = min($dataPoints);
        $range = $max - $min ?: 1;
        
        $coords = [];
        $stepX = $width / (count($dataPoints) - 1);
        $lastY = 0;
        foreach ($dataPoints as $i => $val) {
            $x = $i * $stepX;
            $y = $height - (($val - $min) / $range) * ($height - 8) - 4;
            $coords[] = "$x,$y";
            if ($i === count($dataPoints) - 1) $lastY = $y;
        }
        
        $path = "M " . implode(" L ", $coords);
        $fillPath = "$path L $width,$height L 0,$height Z";
        $gradientId = 'grad_' . uniqid();
        
        return '
        <svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" style="overflow: visible; display: block;">
            <defs>
                <linearGradient id="' . $gradientId . '" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="' . $color . '" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="' . $color . '" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path d="' . $fillPath . '" fill="url(#' . $gradientId . ')" />
            <path d="' . $path . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="' . $width . '" cy="' . $lastY . '" r="3" fill="' . $color . '" />
        </svg>';
    }

    // Generate mock data points representing stats history
    // Subscriptions trend
    $subPoints = [];
    for ($i = 0; $i < 8; $i++) {
        $subPoints[] = max(0, round($subCount * (0.6 + $i * 0.05 + (($i * $i) % 3) * 0.02)));
    }
    $subPoints[7] = $subCount;

    // Upload count trend
    $uploadPoints = [];
    for ($i = 0; $i < 8; $i++) {
        $uploadPoints[] = max(0, round($uploadCount * (0.7 + $i * 0.04)));
    }
    $uploadPoints[7] = $uploadCount;

    // Total views trend
    $viewsPoints = [];
    for ($i = 0; $i < 8; $i++) {
        $viewsPoints[] = max(0, round($totalViews * (0.5 + $i * 0.06 + (($i * 3) % 4) * 0.02)));
    }
    $viewsPoints[7] = $totalViews;

    // Fetch total earnings
    $stmtTotalEarnings = $pdo->prepare("
        SELECT SUM(p.amount) as total_earnings 
        FROM purchases p
        JOIN notes n ON p.note_id = n.id
        WHERE n.user_id = ? AND p.payment_status = 'completed'
    ");
    $stmtTotalEarnings->execute([$teacher_id]);
    $totalEarnings = $stmtTotalEarnings->fetch()['total_earnings'] ?? 0;

    // Fetch earnings grouped by date
    $stmtEarningsTime = $pdo->prepare("
        SELECT strftime('%Y-%m-%d', p.paid_at) as period, SUM(p.amount) as total_earnings
        FROM purchases p
        JOIN notes n ON p.note_id = n.id
        WHERE n.user_id = ? AND p.payment_status = 'completed'
        GROUP BY period
        ORDER BY period ASC
    ");
    $stmtEarningsTime->execute([$teacher_id]);
    $earningsOverTime = $stmtEarningsTime->fetchAll();

    if (empty($earningsOverTime)) {
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $earningsOverTime[] = ['period' => $date, 'total_earnings' => 0];
        }
    }

    $earningsLabels = [];
    $earningsValues = [];
    foreach ($earningsOverTime as $e) {
        $earningsLabels[] = date('d.m', strtotime($e['period']));
        $earningsValues[] = (float)$e['total_earnings'];
    }

    // Fetch top materials by earnings
    $stmtTopMaterials = $pdo->prepare("
        SELECT n.title, COUNT(p.id) as purchases_cnt, SUM(p.amount) as total_earned
        FROM purchases p
        JOIN notes n ON p.note_id = n.id
        WHERE n.user_id = ? AND p.payment_status = 'completed'
        GROUP BY n.id
        ORDER BY total_earned DESC
        LIMIT 5
    ");
    $stmtTopMaterials->execute([$teacher_id]);
    $topMaterials = $stmtTopMaterials->fetchAll();

    $materialsChartLabels = [];
    $materialsChartData = [];
    if (!empty($topMaterials)) {
        foreach ($topMaterials as $tm) {
            $materialsChartLabels[] = mb_strimwidth($tm['title'], 0, 15, '...');
            $materialsChartData[] = (float)$tm['total_earned'];
        }
    } else {
        $materialsChartLabels = ['Brak danych'];
        $materialsChartData = [0];
    }

} catch (\PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}

$pageTitle = 'Mój Kanał - Yti School';
$activePage = 'teacher_channel_manager.php';
require_once 'partials/head.php';
?>
<style>
    .channel-banner {
        width: 100%;
        height: 160px;
        background: linear-gradient(90deg, #2c3e50 0%, #0f0f0f 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        border: 1px solid var(--card-border);
    }
    .channel-header-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding: 0 8px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .channel-left-group {
        display: flex;
        gap: 24px;
        align-items: center;
        flex-wrap: wrap;
    }
    .channel-big-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: #3f3f3f;
        color: #fff;
        font-size: 2.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #272727;
    }
    .channel-meta-group h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
    }
    .channel-handle {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .channel-stats {
        font-size: 0.88rem;
        color: var(--text-secondary);
    }
</style>
<?php
require_once 'partials/topbar.php';
?>
<div class="app-container">
    <?php require_once 'partials/sidebar.php'; ?>

    <!-- Main Workspace -->
    <main class="main-content">
        <!-- Channel Header Banner -->
        <div class="channel-banner"></div>

        <!-- Channel Header Info Row -->
        <div class="channel-header-info">
            <div class="channel-left-group">
                <div class="channel-big-avatar">
                    <?= strtoupper(substr(htmlspecialchars($teacher['username'] ?? 'N'), 0, 1)) ?>
                </div>
                <div class="channel-meta-group">
                    <h1><?= htmlspecialchars($teacher['username'] ?? '') ?></h1>
                    <div class="channel-handle">@<?= htmlspecialchars(strtolower($teacher['username'] ?? '')) ?> &bull; <?= htmlspecialchars($teacher['email'] ?? '') ?></div>
                    <div class="channel-stats"><?= $subCount ?> subskrybentów &bull; <?= $uploadCount ?> materiałów &bull; <?= (int)$totalViews ?> wyświetleń</div>
                </div>
            </div>
            <div>
                <a href="teacher_channel_edit.php" class="btn btn-secondary" style="border-radius: 20px; font-weight: 700; width: auto; padding: 10px 24px; text-decoration: none;">Dostosuj Kanał</a>
            </div>
        </div>

        <!-- Dashboard Stats widgets -->
        <div class="stats-grid" style="margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px;">
                <div class="stat-header">
                    <span class="stat-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary);">Subskrypcje</span>
                </div>
                <div class="stat-value-group" style="margin-top: 8px;">
                    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #fff;"><?= (int)$subCount ?></span>
                </div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px;">
                <div class="stat-header">
                    <span class="stat-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary);">Wszystkie Lekcje</span>
                </div>
                <div class="stat-value-group" style="margin-top: 8px;">
                    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #fff;"><?= (int)$uploadCount ?></span>
                </div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px;">
                <div class="stat-header">
                    <span class="stat-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary);">Łączne Wyświetlenia</span>
                </div>
                <div class="stat-value-group" style="margin-top: 8px;">
                    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #fff;"><?= (int)$totalViews ?></span>
                </div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px;">
                <div class="stat-header">
                    <span class="stat-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary);">Zarobki (PLN)</span>
                </div>
                <div class="stat-value-group" style="margin-top: 8px;">
                    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #fbbf24;"><?= number_format((float)$totalEarnings, 2, ',', ' ') ?> PLN</span>
                </div>
            </div>
        </div>

        <!-- Earnings & Analytics Section -->
        <div class="analytics-section" style="margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            <div class="chart-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column;">
                <h3 style="font-weight: 500; font-size: 1rem; color: #fff; margin: 0 0 15px 0;">Przychody w czasie (PLN)</h3>
                <div style="flex-grow: 1; position: relative; min-height: 220px;">
                    <canvas id="earningsChart"></canvas>
                </div>
            </div>
            <div class="chart-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column;">
                <h3 style="font-weight: 500; font-size: 1rem; color: #fff; margin: 0 0 15px 0;">Najpopularniejsze materiały (PLN)</h3>
                <div style="flex-grow: 1; position: relative; min-height: 220px;">
                    <canvas id="materialsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Published content management table -->
        <div class="saas-table-wrapper" style="background: #121212; border: 1px solid var(--card-border); border-radius: 8px; overflow-x: auto;">
            <div style="padding: 20px; border-bottom: 1px solid var(--card-border);">
                <h3 style="font-weight: 500; font-size: 1rem; color: #fff; margin: 0;">Materiały wideo na Twoim Kanale</h3>
            </div>

            <?php if (empty($uploads)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 40px 0; font-size: 0.9rem;">Twój kanał jest obecnie pusty. Wgraj materiały w panelu dydaktycznym.</p>
            <?php else: ?>
                <table class="saas-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--card-border);">
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Tytuł</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Typ</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Przedmiot</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Klasa</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Dostęp</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Wyświetlenia</th>
                            <th style="padding: 12px 16px; text-align: left; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploads as $note): 
                            $subjClass = strtolower($note['subject']);
                            if (!in_array($subjClass, ['matematyka', 'fizyka', 'biologia', 'chemia'])) {
                                $subjClass = 'default';
                            }
                            $typeLabel = $note['file_type'] === 'presentation' ? 'Prezentacja' : (pathinfo($note['filepath'], PATHINFO_EXTENSION) === 'pdf' ? 'PDF' : 'Zdjęcie');
                        ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 14px 16px;"><strong><?= htmlspecialchars($note['title']) ?></strong></td>
                                <td style="padding: 14px 16px;"><span style="font-size: 0.8rem; opacity: 0.8;"><?= $typeLabel ?></span></td>
                                <td style="padding: 14px 16px;"><span class="subject-badge <?= $subjClass ?>"><?= htmlspecialchars($note['subject']) ?></span></td>
                                <td style="padding: 14px 16px;"><?= htmlspecialchars($note['class_level']) ?></td>
                                <td style="padding: 14px 16px;">
                                    <?php if (($note['access_type'] ?? 'free') === 'premium'): ?>
                                        <span style="color: #f59e0b; font-weight: 700;">Premium <?= number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ') ?> PLN</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px;"><?= (int)$note['views'] ?></td>
                                <td style="padding: 14px 16px;">
                                    <form action="delete_note.php" method="POST" class="delete-note-form" style="display:inline;">
                                        <?= SecurityEnterprise::csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.78rem; width: auto; border: none; cursor: pointer;">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-note-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!confirm('Czy na pewno chcesz usunąć tę lekcję?')) {
                    e.preventDefault();
                }
            });
        });

        // Chart.js implementation
        const initCharts = () => {
            if (typeof Chart === 'undefined') {
                setTimeout(initCharts, 50);
                return;
            }

            const earningsLabels = <?= json_encode($earningsLabels) ?>;
            const earningsData = <?= json_encode($earningsValues) ?>;
            const materialsLabels = <?= json_encode($materialsChartLabels) ?>;
            const materialsData = <?= json_encode($materialsChartData) ?>;

            const ctxEarnings = document.getElementById('earningsChart');
            if (ctxEarnings) {
                new Chart(ctxEarnings, {
                    type: 'line',
                    data: {
                        labels: earningsLabels,
                        datasets: [{
                            label: 'Przychody (PLN)',
                            data: earningsData,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.12)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointBackgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                ticks: { color: '#8e8e93' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#8e8e93' }
                            }
                        }
                    }
                });
            }

            const ctxMaterials = document.getElementById('materialsChart');
            if (ctxMaterials) {
                new Chart(ctxMaterials, {
                    type: 'bar',
                    data: {
                        labels: materialsLabels,
                        datasets: [{
                            label: 'Suma zarobków (PLN)',
                            data: materialsData,
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                ticks: { color: '#8e8e93' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#8e8e93' }
                            }
                        }
                    }
                });
            }
        };

        initCharts();
    });
</script>
</body>
</html>
