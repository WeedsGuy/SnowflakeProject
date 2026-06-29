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

$password      = trim($data['password'] ?? '');
$your_password = trim($data['your_password'] ?? '');
$oldUser       = trim($data['oldUser'] ?? '');
$oldEmail      = trim($data['oldEmail'] ?? '');

if (!$password || !$your_password || !$oldUser || !$oldEmail) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {

    $client = new Aws\DynamoDb\DynamoDbClient([
        'version' => 'latest',
        'region'  => $region,
        'credentials' => [
            'key'    => $cfg['aws']['key'],
            'secret' => $cfg['aws']['secret'],
        ],
    ]);

    if (empty($_SESSION['admin_user'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    /* 🔎 Get existing user */
    $result = $client->query([
        'TableName' => $table,
        'KeyConditionExpression' => 'user_name = :u',
        'ExpressionAttributeValues' => [
            ':u' => ['S' => $_SESSION['admin_user']]
        ],
        'Limit' => 1
    ]);

    if (empty($result['Items'][0])) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $storedHash = $result['Items'][0]['password']['S'] ?? '';

    /* 🔐 Verify old password */
    if (!password_verify($your_password, $storedHash)) {
        echo json_encode(['success' => false, 'message' => 'Your password is incorrect']);
        exit;
    }

    /* 🔒 Hash new password */
    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

    /* 🚀 Update password only */
    $client->updateItem([
        'TableName' => $table,
        'Key' => [
            'user_name' => ['S' => $oldUser],   // Partition key
            'email'     => ['S' => $oldEmail]   // Sort key
        ],
        'UpdateExpression' => 'SET password = :p',
        'ExpressionAttributeValues' => [
            ':p' => ['S' => $newPasswordHash]
        ]
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
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
