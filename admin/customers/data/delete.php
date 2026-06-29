<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

set_time_limit(0);

$cfg = require __DIR__ . '/../../../keys.php';

$dynamodb = new DynamoDbClient([
    'region' => $cfg['aws']['region'],
    'version' => 'latest',
    'credentials' => [
        'key' => $cfg['aws']['key'],
        'secret' => $cfg['aws']['secret']
    ]
]);

$tableName = 'customer_properties';
$lastKey = null;
$totalDeleted = 0;

do {

    $params = [
        'TableName' => $tableName,
        'ProjectionExpression' => 'CustomerNumber, Address'
    ];

    if ($lastKey) {
        $params['ExclusiveStartKey'] = $lastKey;
    }

    $result = $dynamodb->scan($params);

    if (!empty($result['Items'])) {

        $requests = [];

        foreach ($result['Items'] as $item) {

            if (
                empty($item['CustomerNumber']['S']) ||
                empty($item['Address']['S'])
            ) {
                continue;
            }

            $requests[] = [
                'DeleteRequest' => [
                    'Key' => [
                        'CustomerNumber' => [
                            'S' => $item['CustomerNumber']['S']
                        ],
                        'Address' => [
                            'S' => $item['Address']['S']
                        ]
                    ]
                ]
            ];

            // Process in batches of 25
            if (count($requests) === 25) {
                deleteBatch($dynamodb, $tableName, $requests, $totalDeleted);
                $requests = [];
            }
        }

        // Delete remaining
        if (!empty($requests)) {
            deleteBatch($dynamodb, $tableName, $requests, $totalDeleted);
        }
    }

    $lastKey = $result['LastEvaluatedKey'] ?? null;

} while ($lastKey);

echo "Total Deleted: " . $totalDeleted;


/**
 * Batch delete with retry for UnprocessedItems
 */
function deleteBatch($dynamodb, $tableName, $requests, &$totalDeleted)
{
    $response = $dynamodb->batchWriteItem([
        'RequestItems' => [
            $tableName => $requests
        ]
    ]);

    $totalDeleted += count($requests);

    // Retry unprocessed items
    while (!empty($response['UnprocessedItems'])) {
        $response = $dynamodb->batchWriteItem([
            'RequestItems' => $response['UnprocessedItems']
        ]);
        sleep(1);
    }
}
