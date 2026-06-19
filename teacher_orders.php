<?php
require_once 'db.php';
requireTeacher();

$user_id = $_SESSION['user_id'];
$error = '';

try {
    $stmt = $pdo->prepare('SELECT p.*, n.title AS note_title, n.subject, u.username AS buyer_name, n.premium_price, n.id AS note_id
                           FROM purchases p
                           JOIN notes n ON p.note_id = n.id
                           JOIN users u ON p.user_id = u.id
                           WHERE n.user_id = ?
                           ORDER BY p.paid_at DESC');
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (\PDOException $e) {
    $orders = [];
    $error = 'Nie udało się pobrać zamówień.';
}

$pageTitle = 'Zamówienia Premium - Yti School';
$activePage = 'teacher_orders.php';
$hideSearch = true;
require APP_ROOT . '/partials/head.php';
require APP_ROOT . '/partials/topbar.php';
require APP_ROOT . '/partials/sidebar.php';
?>
<main class="main-content">
    <div class="glass-card" style="max-width: 980px; margin: 24px auto; padding: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px;">
            <div>
                <h2>Zamówienia premium</h2>
                <p style="color: var(--text-secondary); margin-top: 4px;">Lista zakupu materiałów stworzonych przez Ciebie.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="glass-card" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                <h3>Brak zamówień premium</h3>
                <p style="margin-top: 10px;">Kiedy ktoś kupi Twoje materiały, pojawi się to tutaj.</p>
            </div>
        <?php else: ?>
            <table class="saas-table" style="width: 100%; margin-top: 14px;">
                <thead>
                    <tr>
                        <th>Materiał</th>
                        <th>Klient</th>
                        <th>Kwota</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($order['note_title']) ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($order['subject']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                            <td><?= number_format((float)$order['amount'], 2, ',', ' ') ?> PLN</td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['paid_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
