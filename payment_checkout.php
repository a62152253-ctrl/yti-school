<?php
require_once 'db.php';
require_once 'includes/stripe_handler.php';

requireLogin();

$note_id = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (!$note_id) {
    redirect('student_dashboard.php');
}

try {
    $stmt = $pdo->prepare("SELECT id, title, access_type, premium_price, user_id FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    if (!$note) redirect('student_dashboard.php');
    if (($note['access_type'] ?? 'free') !== 'premium') redirect('watch.php?id=' . $note_id);
} catch (\PDOException $e) {
    die('Błąd systemu');
}

// Check if already purchased
try {
    $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND note_id = ?");
    $stmt->execute([$user_id, $note_id]);
    if ($stmt->fetch()) {
        redirect('watch.php?id=' . $note_id);
    }
} catch (\PDOException $e) {
    //
}

// Handle successful payment return from Stripe
if (isset($_GET['session_id'])) {
    try {
        $handler = new StripePaymentHandler($pdo);
        $result = $handler->processSuccessfulPayment($_GET['session_id']);
        $success = $result['message'];
        // Redirect to watch page after 3 seconds
        echo "<script>setTimeout(() => { window.location.href = 'watch.php?id=" . $note_id . "'; }, 3000);</script>";
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

// Create checkout session on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success && !$error) {
    try {
        $handler = new StripePaymentHandler($pdo);
        $returnUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payment_checkout.php?note_id=' . $note_id;
        $session = $handler->createCheckoutSession($note_id, $user_id, $returnUrl);
        
        // Redirect to Stripe checkout
        header('Location: ' . $session->url);
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Stripe - Bezpieczna Płatność</title>
    <link rel="stylesheet" href="styleapp.css">
    <style>
        :root {
            --bg-color: #080808;
            --card-bg: #121212;
            --accent-color: #005eff;
        }
        body {
            background-color: var(--bg-color) !important;
            color: #ffffff !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .payment-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 0 20px;
        }
        .payment-card {
            background: var(--card-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px !important;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .payment-header h2 {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .payment-header p {
            color: #8e8e93;
            font-size: 0.9em;
        }
        .payment-details {
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .detail-row:last-child {
            margin-bottom: 0;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .detail-label {
            color: #8e8e93;
            font-size: 0.9em;
        }
        .detail-value {
            font-weight: 500;
            color: #ffffff;
        }
        .detail-value.amount {
            font-size: 1.4em;
            color: var(--accent-color);
            font-weight: 600;
        }
        .btn-pay {
            width: 100%;
            background: var(--accent-color) !important;
            color: #ffffff !important;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 12px;
            transition: background-color 0.2s;
        }
        .btn-pay:hover {
            background: #0048cc !important;
        }
        .btn-cancel {
            width: 100%;
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px;
            padding: 14px;
            font-size: 1em;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background-color 0.2s;
        }
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.12) !important;
        }
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            color: #8e8e93;
            font-size: 0.85em;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .alert-error {
            background: rgba(255, 69, 58, 0.1) !important;
            border: 1px solid rgba(255, 69, 58, 0.2) !important;
            color: #ff453a;
        }
        .alert-success {
            background: rgba(52, 199, 89, 0.1) !important;
            border: 1px solid rgba(52, 199, 89, 0.2) !important;
            color: #34c759;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h2>🔒 Bezpieczna Płatność</h2>
                <p>Stripe Payment Gateway</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
                <p style="color: #8e8e93; text-align: center;">Przekierowywanie na materiał...</p>
            <?php else: ?>
                <div class="payment-details">
                    <div class="detail-row">
                        <span class="detail-label">Materiał:</span>
                        <span class="detail-value"><?= htmlspecialchars($note['title']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kwota:</span>
                        <span class="detail-value amount"><?= number_format((float)$note['premium_price'], 2, ',', ' ') ?> PLN</span>
                    </div>
                </div>

                <form method="POST">
                    <button type="submit" class="btn-pay">💳 Zapłać teraz</button>
                </form>
                <a href="watch.php?id=<?= $note_id ?>" class="btn-cancel">Anuluj</a>

                <div class="security-badge">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 5v7c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-10-3z"/>
                    </svg>
                    Transakcja zabezpieczona przez Stripe
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
