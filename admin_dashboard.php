<?php
$pageTitle = 'Dashboard - Panel Administratora - Yti School';
require_once 'admin/header.php';

try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalLessons = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
    $totalViews = (int)$pdo->query("SELECT SUM(views) FROM notes")->fetchColumn();
    $totalRevenue = (float)$pdo->query("SELECT SUM(amount) FROM purchases WHERE payment_status = 'completed'")->fetchColumn();
    
    // User registrations statistics (by day)
    $stmtUserStats = $pdo->query("
        SELECT strftime('%Y-%m-%d', created_at) as day, COUNT(*) as cnt 
        FROM users 
        GROUP BY day 
        ORDER BY day ASC 
        LIMIT 10
    ");
    $userStats = $stmtUserStats->fetchAll();
    $userStatsLabels = [];
    $userStatsValues = [];
    foreach ($userStats as $stat) {
        $userStatsLabels[] = date('d.m', strtotime($stat['day']));
        $userStatsValues[] = (int)$stat['cnt'];
    }
    
    // Daily revenue statistics (by day)
    $stmtRevStats = $pdo->query("
        SELECT strftime('%Y-%m-%d', paid_at) as day, SUM(amount) as revenue 
        FROM purchases 
        WHERE payment_status = 'completed'
        GROUP BY day 
        ORDER BY day ASC 
        LIMIT 10
    ");
    $revStats = $stmtRevStats->fetchAll();
    $revStatsLabels = [];
    $revStatsValues = [];
    foreach ($revStats as $stat) {
        $revStatsLabels[] = date('d.m', strtotime($stat['day']));
        $revStatsValues[] = (float)$stat['revenue'];
    }
    
    if (empty($userStatsLabels)) {
        $userStatsLabels = [date('d.m')];
        $userStatsValues = [0];
    }
    if (empty($revStatsLabels)) {
        $revStatsLabels = [date('d.m')];
        $revStatsValues = [0];
    }

    // Fetch audit log (last 8 entries)
    $audit_logs = [];
    $log_file = __DIR__ . DIRECTORY_SEPARATOR . 'security_audit.log';
    if (file_exists($log_file)) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $last_lines = array_slice($lines, -8);
            $last_lines = array_reverse($last_lines);
            foreach ($last_lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded) {
                    $audit_logs[] = $decoded;
                }
            }
        }
    }
} catch (\PDOException $e) {
    die("Błąd pobierania danych statystycznych: " . $e->getMessage());
}
?>

<script>
    // Share data with footer JS charts loader safely
    window.userStatsData = {
        labels: <?= json_encode($userStatsLabels) ?>,
        values: <?= json_encode($userStatsValues) ?>
    };
    window.revStatsData = {
        labels: <?= json_encode($revStatsLabels) ?>,
        values: <?= json_encode($revStatsValues) ?>
    };
</script>

<?php 
include 'admin/dashboard.php';
require_once 'admin/footer.php';
?>
