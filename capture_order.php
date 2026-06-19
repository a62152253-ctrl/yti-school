<?php
require_once 'db.php';
$cfg = require __DIR__ . '/config/paypal.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Method not allowed']);
	exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$orderID = $input['orderID'] ?? null;
$note_id = isset($input['note_id']) ? (int)$input['note_id'] : 0;
if (!$orderID || !$note_id) {
	http_response_code(400);
	echo json_encode(['error' => 'orderID and note_id required']);
	exit;
}

// Get access token
$tokenUrl = rtrim($cfg['api_base'], '/') . '/v1/oauth2/token';
$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $cfg['client_id'] . ':' . $cfg['client_secret']);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);

$tokenRes = curl_exec($ch);
if ($tokenRes === false) { http_response_code(500); echo json_encode(['error'=>'paypal token error']); exit; }
$tokenData = json_decode($tokenRes, true);
curl_close($ch);

$accessToken = $tokenData['access_token'] ?? null;
if (!$accessToken) { http_response_code(500); echo json_encode(['error'=>'paypal token missing']); exit; }

// Capture order
$captureUrl = rtrim($cfg['api_base'], '/') . '/v2/checkout/orders/' . urlencode($orderID) . '/capture';
$ch = curl_init($captureUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Authorization: Bearer ' . $accessToken
]);

$captureRes = curl_exec($ch);
if ($captureRes === false) { http_response_code(500); echo json_encode(['error'=>'paypal capture error']); exit; }
$captureData = json_decode($captureRes, true);
curl_close($ch);

// Verify capture status
$status = $captureData['status'] ?? null;
if (strtoupper($status) !== 'COMPLETED' && strtoupper($captureData['status'] ?? '') !== 'COMPLETED') {
	// Some responses contain purchase_units[0].payments.captures[0].status
	$captureStatus = $captureData['purchase_units'][0]['payments']['captures'][0]['status'] ?? null;
	if (strtoupper($captureStatus) !== 'COMPLETED') {
		http_response_code(400);
		echo json_encode(['error' => 'capture not completed', 'detail' => $captureData]);
		exit;
	}
}

// get payer and amount
$amount = $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null;

// save purchase
try {
	$stmt = $pdo->prepare("INSERT OR REPLACE INTO purchases (user_id, note_id, amount) VALUES (?, ?, ?)");
	$stmt->execute([$_SESSION['user_id'], $note_id, $amount]);
	echo json_encode(['success' => true]);
} catch (\PDOException $e) {
	http_response_code(500);
	echo json_encode(['error' => 'db error', 'detail' => $e->getMessage()]);
}
