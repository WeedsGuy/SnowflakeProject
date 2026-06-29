<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;

set_time_limit(0);

$file = __DIR__ . '/uploads/' . basename($_POST['file']); // safer path
$offset = (int)$_POST['offset'];
$limit = (int)$_POST['limit'];

$cfg = require __DIR__ . '/../../../keys.php';

$dynamodb = new DynamoDbClient([
    'region' => $cfg['aws']['region'],
    'version' => 'latest',
    'credentials' => [
        'key' => $cfg['aws']['key'],
        'secret' => $cfg['aws']['secret']
    ]
]);

if (!file_exists($file)) {
    echo json_encode(['processed' => 0]);
    exit;
}

$handle = fopen($file, "r");
$header = fgetcsv($handle);

$currentRow = 0;
$processed = 0;

while (($row = fgetcsv($handle)) !== FALSE) {

    if ($currentRow < $offset) {
        $currentRow++;
        continue;
    }

    if ($processed >= $limit) break;

    $data = array_combine($header, $row);

    try {
        $processed++;
        $dynamodb->putItem([
            'TableName' => 'customerProperties',
            'Item' => [
                'CustomerNumber' => ['S' => (string)$data['CustomerNumber']],
                'Address' => ['S' => $data['Address']],
                'PreferredPhoneNumber' => ['S' => $data['PreferredPhoneNumber']],
                'EmailAddress' => ['S' => $data['Email Address']],
                'ProgramSize' => ['N' => (string)$data['ProgramSize']]
            ],
            'ConditionExpression' => 'attribute_not_exists(Address)'
        ]);

        

    } catch (AwsException $e) {
        if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
            error_log($e->getMessage());
        }
    }

    $currentRow++;
}

fclose($handle);

echo json_encode(['processed' => $processed]);
