<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../vendor/autoload.php';

$cfg = require __DIR__ . '/../../keys.php';

$region = $cfg['aws']['region'];

$user_name = $_GET['user_name'] ?? '';

if (!$user_name) {
    echo json_encode(['success' => false, 'message' => 'user name required']);
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
    // echo $user_name; die;
    // $result = $client->getItem([
    //     'TableName' => 'admin',
    //     'Key' => [
    //        'user_name' => ['S' => $user_name]
    //     ]
    // ]);
     $result = $client->query([
                'TableName' => 'admin',
                'KeyConditionExpression' => 'user_name = :u',
                'ExpressionAttributeValues' => [
                    ':u' => ['S' => $user_name]
                ],
                'Limit' => 1
            ]);
    if (!isset($result['Items'][0])) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    echo json_encode([
        'success' => true,
        'data' => [
            'user_name' => $result['Items'][0]['user_name']['S'] ?? '',
            'email'     => $result['Items'][0]['email']['S'] ?? ''
        ]
    ]);
} catch (Aws\Exception\AwsException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getAwsErrorMessage()
    ]);
}
