<?php
require __DIR__ . '/../vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

use Evervault\Evervault;

header('Content-Type: application/json');

$encryptedCardJson = $_POST['cardData'] ?? null;

if (!$encryptedCardJson) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing card data']);
    exit;
}

$card = json_decode($encryptedCardJson, true);

if (!$card) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid card JSON']);
    exit;
}
    $keysPath = __DIR__ . '/../keys.php'; // should be gitignored
    if (file_exists($keysPath)) {
        $cfg = require $keysPath;
        if (is_array($cfg)) {
            $appId = ($cfg['evervault']['app_id'] ?? null);
            $apiKey =($cfg['evervault']['api_key'] ?? null);
        }
    }
$ev = new Evervault(
    $appId,
    $apiKey
);

/**
 * ⚠️ PAN decryption ONLY if needed for payment processing
 * NEVER send decrypted PAN to frontend
 */
$cardNumber = $ev->decrypt($card['number']);
$cvc = $ev->decrypt($card['cvc']);

echo json_encode([
    'success' => true,
    'card' => [
        'name'=>$card['name'],
        'number' => $cardNumber,
        'cvc'    => $cvc,
        'brand'  => $card['brand'] ?? 'unknown',
        'masked' => '**** **** **** ' . ($card['lastFour'] ?? 'XXXX'),
        'expiry' => $card['expiry'] ?? ['month' => '**', 'year' => '**']
    ]
]);
