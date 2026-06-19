<?php
require_once 'db.php';
requireLogin();
if (isTeacher()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$successMsg = '';

$action = $_GET['action'] ?? '';
$note_id = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;

if ($action === 'add' && $note_id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT n.id, n.access_type, n.premium_price FROM notes n WHERE n.id = ?');
        $stmt->execute([$note_id]);
        $note = $stmt->fetch();
        if (!$note || ($note['access_type'] ?? 'free') !== 'premium') {
            $error = 'Nie można dodać tego materiału do koszyka.';
        } else {
            $check = $pdo->prepare('SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?');
            $check->execute([$user_id, $note_id]);
            if ($check->fetch()) {
                $error = 'Materiał został już zakupiony.';
            } else {
                $insert = $pdo->prepare('INSERT OR IGNORE INTO cart_items (user_id, note_id) VALUES (?, ?)');
                $insert->execute([$user_id, $note_id]);
                $successMsg = 'Dodano materiał do koszyka.';
            }
        }
    } catch (\PDOException $e) {
        $error = 'Błąd dodawania do koszyka.';
    }
}

if ($action === 'remove' && $note_id > 0) {
    try {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND note_id = ?');
        $stmt->execute([$user_id, $note_id]);
        $successMsg = 'Usunięto materiał z koszyka.';
    } catch (\PDOException $e) {
        $error = 'Błąd usuwania z koszyka.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    SecurityEnterprise::requireCsrf($_POST['csrf_token'] ?? '');
    $code = trim($_POST['promo_code'] ?? '');
    if (strcasecmp($code, 'YTI2026') === 0) {
        $_SESSION['applied_promo'] = 'YTI2026';
        $successMsg = 'Kod rabatowy YTI2026 (20% zniżki) został pomyślnie naliczony!';
    } else {
        $error = 'Nieprawidłowy lub nieaktywny kod rabatowy.';
    }
}

if ($action === 'clear_promo') {
    unset($_SESSION['applied_promo']);
    $successMsg = 'Kod rabatowy został usunięty z zamówienia.';
}


if ($action === 'checkout') {
    try {
        $stmt = $pdo->prepare('SELECT ci.note_id, n.title, n.premium_price, n.user_id FROM cart_items ci JOIN notes n ON ci.note_id = n.id WHERE ci.user_id = ?');
        $stmt->execute([$user_id]);
        $cartItems = $stmt->fetchAll();
        if (empty($cartItems)) {
            $error = 'Koszyk jest pusty.';
        } else {
            $discount = (isset($_SESSION['applied_promo']) && $_SESSION['applied_promo'] === 'YTI2026') ? 0.8 : 1.0;
            $pdo->beginTransaction();
            $total = 0;
            foreach ($cartItems as $item) {
                $check = $pdo->prepare('SELECT 1 FROM purchases WHERE user_id = ? AND note_id = ?');
                $check->execute([$user_id, $item['note_id']]);
                if ($check->fetch()) {
                    continue;
                }
                $itemPrice = (float)$item['premium_price'] * $discount;
                $purchase = $pdo->prepare('INSERT OR REPLACE INTO purchases (user_id, note_id, amount) VALUES (?, ?, ?)');
                $purchase->execute([$user_id, $item['note_id'], $itemPrice]);
                $total += $itemPrice;
                $notify = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $message = 'Ktoś kupił Twój materiał: ' . $item['title'];
                $notify->execute([$item['user_id'], 'Nowe zamówienie', $message]);
            }
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$user_id]);
            unset($_SESSION['applied_promo']);
            $pdo->commit();
            $successMsg = 'Zakupy zostały zakończone. Łącznie: ' . number_format($total, 2, ',', ' ') . ' PLN.';
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Błąd finalizacji zakupów: ' . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare('SELECT ci.note_id, n.title, n.subject, n.premium_price, n.user_id, u.username AS teacher_name FROM cart_items ci JOIN notes n ON ci.note_id = n.id JOIN users u ON n.user_id = u.id WHERE ci.user_id = ? ORDER BY ci.created_at DESC');
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll();
} catch (\PDOException $e) {
    $cartItems = [];
    $error = 'Nie udało się pobrać koszyka.';
}

$pageTitle = 'Koszyk - Yti School';
$activePage = 'cart.php';
$hideSearch = true;
require APP_ROOT . '/partials/head.php';
require APP_ROOT . '/partials/topbar.php';
require APP_ROOT . '/partials/sidebar.php';
?>
<main class="main-content">
    <div class="glass-card" style="max-width: 980px; margin: 24px auto; padding: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px;">
            <div>
                <h2>Koszyk</h2>
                <p style="color: var(--text-secondary); margin-top: 4px;">Zamówienia premium gotowe do zakupu.</p>
            </div>
            <?php if (!empty($cartItems)): ?>
                <a href="cart.php?action=checkout" class="btn btn-primary">Zapłać wszystkie</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="glass-card" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                <h3>Twój koszyk jest pusty</h3>
                <p style="margin-top: 10px;">Dodaj materiały premium do koszyka, aby szybko je opłacić.</p>
            </div>
        <?php else: ?>
            <table class="saas-table" style="width: 100%; margin-top: 14px;">
                <thead>
                    <tr>
                        <th>Materiał</th>
                        <th>Nauczyciel</th>
                        <th>Kwota</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total = 0; foreach ($cartItems as $item): $total += (float)$item['premium_price']; ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($item['subject']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($item['teacher_name']) ?></td>
                            <td><?= number_format((float)$item['premium_price'], 2, ',', ' ') ?> PLN</td>
                            <td>
                                <a href="cart.php?action=remove&note_id=<?= $item['note_id'] ?>" class="btn btn-secondary" style="padding: 6px 12px;">Usuń</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php 
                        $hasPromo = isset($_SESSION['applied_promo']) && $_SESSION['applied_promo'] === 'YTI2026';
                        $finalTotal = $hasPromo ? ($total * 0.8) : $total;
                    ?>
                    
                    <?php if ($hasPromo): ?>
                        <tr>
                            <td colspan="2" style="text-align:right; color: var(--success-color);">Rabat YTI2026 (-20%):</td>
                            <td colspan="2" style="color: var(--success-color); font-weight: 500;">-<?= number_format($total * 0.2, 2, ',', ' ') ?> PLN</td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td colspan="2" style="text-align:right; font-weight:700;">Razem:</td>
                        <td colspan="2" style="font-weight:700;"><?= number_format($finalTotal, 2, ',', ' ') ?> PLN</td>
                    </tr>
                </tbody>
            </table>

            <!-- Promo code input block -->
            <div class="promo-code-container">
                <form action="cart.php" method="POST" style="display: flex; gap: 10px; width: 100%; max-width: 400px; align-items: center;">
                    <?= SecurityEnterprise::csrfField() ?>
                    <input type="hidden" name="apply_promo" value="1">
                    <input type="text" name="promo_code" class="form-control" placeholder="Wpisz kod rabatowy (np. YTI2026)" value="<?= $hasPromo ? 'YTI2026' : '' ?>" style="margin: 0; padding: 8px 12px; font-size: 0.85rem;" <?= $hasPromo ? 'disabled' : '' ?>>
                    <?php if ($hasPromo): ?>
                        <a href="cart.php?action=clear_promo" class="btn btn-secondary" style="width: auto; padding: 8px 14px; font-size: 0.85rem; white-space: nowrap;">Usuń kod</a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary" style="width: auto; padding: 8px 14px; font-size: 0.85rem; white-space: nowrap;">Zastosuj</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
