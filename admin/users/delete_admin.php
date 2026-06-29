<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../vendor/autoload.php';

$cfg = require __DIR__ . '/../../keys.php';

$region = $cfg['aws']['region'];
$table  = $cfg['aws']['admin_table'] ?? 'admin';

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$user_name = trim($data['user_name'] ?? '');
$email     = trim($data['email'] ?? '');

if (!$user_name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {

    if (empty($_SESSION['admin_user'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $client = new Aws\DynamoDb\DynamoDbClient([
        'version' => 'latest',
        'region'  => $region,
        'credentials' => [
            'key'    => $cfg['aws']['key'],
            'secret' => $cfg['aws']['secret'],
        ],
    ]);

    $client->deleteItem([
        'TableName' => $table,
        'Key' => [
            'user_name' => ['S' => $user_name],
            'email'     => ['S' => $email]
        ]
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);

} catch (Aws\Exception\AwsException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getAwsErrorMessage()
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
