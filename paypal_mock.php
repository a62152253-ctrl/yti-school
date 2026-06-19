<?php
require_once 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$note_id = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;

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

// Simulate payment when POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// create purchase record
	try {
		$stmt = $pdo->prepare("INSERT OR REPLACE INTO purchases (user_id, note_id, amount) VALUES (?, ?, ?)");
		$stmt->execute([$user_id, $note_id, $note['premium_price']]);
		// redirect to watch page
		redirect('watch.php?id=' . $note_id);
	} catch (\PDOException $e) {
		$error = 'Błąd podczas zapisu transakcji.';
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Mock PayPal - Płatność</title>
	<link rel="stylesheet" href="/styleapp.css">
</head>
<body>
	<div class="app-container" style="padding: 40px;">
		<div class="glass-card" style="max-width: 600px; margin: 0 auto; padding: 24px;">
			<h2>Płatność (mock)</h2>
			<p>Materiał: <strong><?= htmlspecialchars($note['title']) ?></strong></p>
			<p>Kwota: <strong><?= number_format((float)$note['premium_price'], 2, ',', ' ') ?> PLN</strong></p>
			<?php if (!empty($error)): ?>
				<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
			<?php endif; ?>
			<form method="POST">
				<p>Symulowana strona PayPal — bez realnych płatności. Kliknij "Zapłać" aby uzyskać dostęp.</p>
				<button type="submit" class="btn btn-primary">Zapłać (mock)</button>
				<a href="watch.php?id=<?= $note_id ?>" class="btn" style="margin-left: 8px;">Anuluj</a>
			</form>
		</div>
	</div>
</body>
</html>
