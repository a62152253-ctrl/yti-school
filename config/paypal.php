<?php
// PayPal configuration
// Uwaga: Umieść tutaj swoje dane sandbox. Nie dodawaj secret do publicznego repo.
return [
	// "mode" => "sandbox" lub "live"
	'mode' => 'sandbox',
	// Wstaw tutaj swój sandbox client id i secret
	'client_id' => 'REPLACE_WITH_SANDBOX_CLIENT_ID',
	'client_secret' => 'REPLACE_WITH_SANDBOX_CLIENT_SECRET',
	// opcjonalnie base URL
	'api_base' => 'https://api-m.sandbox.paypal.com'
];
