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
$note_id = isset($input['note_id']) ? (int)$input['note_id'] : 0;
if (!$note_id) {
	http_response_code(400);
	echo json_encode(['error' => 'note_id required']);
	exit;
}

// fetch note
try {
	$stmt = $pdo->prepare("SELECT id, title, premium_price FROM notes WHERE id = ?");
	$stmt->execute([$note_id]);
	$note = $stmt->fetch();
	if (!$note) {
		http_response_code(404);
		echo json_encode(['error' => 'note not found']);
		exit;
	}
} catch (\PDOException $e) {
	http_response_code(500);
	echo json_encode(['error' => 'db error']);
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

// create order
$orderUrl = rtrim($cfg['api_base'], '/') . '/v2/checkout/orders';
$payload = [
	'intent' => 'CAPTURE',
	'purchase_units' => [[
		'amount' => [
			'currency_code' => 'PLN',
			'value' => number_format((float)$note['premium_price'], 2, '.', '')
		],
		'description' => 'Purchase note #' . $note['id'] . ' - ' . $note['title']
	]]
];

$ch = curl_init($orderUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Authorization: Bearer ' . $accessToken
]);

$orderRes = curl_exec($ch);
if ($orderRes === false) { http_response_code(500); echo json_encode(['error'=>'paypal order error']); exit; }
$orderData = json_decode($orderRes, true);
curl_close($ch);

if (isset($orderData['id'])) {
	echo json_encode(['orderID' => $orderData['id']]);
} else {
	http_response_code(500);
	echo json_encode(['error' => 'order creation failed', 'detail' => $orderData]);
}
