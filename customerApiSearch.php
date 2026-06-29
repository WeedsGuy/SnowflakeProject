<?php

header('Content-Type: application/json');

if (!isset($_GET['address']) || empty($_GET['address'])) {
    echo json_encode([
        "exists" => false,
        "error" => "address is required"
    ]);
    exit;
}

$customerAddress = $_GET['address'];
$address = explode(",", $customerAddress);

if (count($address) > 0) {
    $customerAddress = urlencode($address[0]);
}

$url = "https://saapi.realgreen.com/Customer/Search?CustomerStreetAddress=" . $customerAddress;

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "accept: text/plain",
    "apiKey: 3b6b0186-c36b-4120-8ff7-209ddafa2518"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Case 1: API error or not found
if ($httpCode !== 200 || !$response) {
    echo json_encode([
        "exists" => false,
        "data" => null
    ]);
    exit;
}

// Try to decode JSON (if API returns JSON)
$data = json_decode($response, true);
// If JSON decoding failed, fallback to raw text
if (json_last_error() !== JSON_ERROR_NONE) {
    $exists = trim($response) !== "";

    echo json_encode([
        "exists" => $exists,
        "data" => $exists ? $response : null
    ]);
    exit;
}

// If valid JSON
$exists = count($data) > 0 && isset($data[0]['statusCharacter']) && $data[0]['statusCharacter'] == "9";

echo json_encode([
    "exists" => $exists,
    "area" => $data[0]["size"] ?? null,
    "data" => $data
]);