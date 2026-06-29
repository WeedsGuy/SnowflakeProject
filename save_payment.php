<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


function respond(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

try {

    require_once(__DIR__ . '/database/db.php');
    require_once __DIR__ . '/database/warehouse.php';
    // Read JSON body
    $rawBody = file_get_contents('php://input') ?: '';
    $data = json_decode($rawBody, true);

    if (!is_array($data)) {
        respond(400, [
            'success' => false,
            'error' => 'Invalid JSON body'
        ]);
    }

    $name = trim((string)($data['name'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));

    $cardToken = $data['cardToken'] ?? null;
    $cardData = $data['cardData'] ?? null;

    
    if ($address === '' || !is_string($cardToken) || trim($cardToken) === '') {
        respond(400, [
            'success' => false,
            'error' => 'Address and card token are required'
        ]);
    }

    $customerId = 'CUST-' . bin2hex(random_bytes(8));
    $createdAt = gmdate('Y-m-d H:i:s');

    // Escape values
    $nameEsc = str_replace("'", "''", $name);
    $emailEsc = str_replace("'", "''", $email);
    $phoneEsc = str_replace("'", "''", $phone);
    $addressEsc = str_replace("'", "''", $address);
    $cardTokenEsc = str_replace("'", "''", (string)$cardToken);
    $customerIdEsc = str_replace("'", "''", $customerId);

    $cardDataEsc = json_encode($cardData);
  

    $sql = "
    INSERT INTO CUSTOMER_PAYMENTS (
        CUSTOMER_ID,
        NAME,
        EMAIL,
        PHONE,
        ADDRESS,
        CARD_TOKEN,
        CARD_DATA,
        CREATED_AT
    )
    VALUES (
        '$customerIdEsc',
        '$nameEsc',
        '$emailEsc',
        '$phoneEsc',
        '$addressEsc',
        '$cardTokenEsc',
        '$cardDataEsc',
        '$createdAt'
    )";
    sysadmin($snowflake);
    $result = odbc_exec($snowflake, $sql);
   
    if (!$result) {
        throw new Exception(
            'Snowflake Insert Error: ' . odbc_errormsg($snowflake)
        );
    }

    respond(200, [
        'success' => true,
        'message' => 'Payment method saved',
        'customerId' => $customerId,
        'address' => $address
    ]);

} catch (Throwable $e) {

    error_log("Error: " . $e->getMessage());

    respond(500, [
        'success' => false,
        'error' => $e->getMessage()
    ]);
}