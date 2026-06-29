<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/warehouse.php';

try {

    sysadmin($snowflake);

    // Pagination
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 20));
    $offset = ($page - 1) * $limit;

    // Search
    $search = trim($_GET['search'] ?? '');

    $where = '';

    if ($search !== '') {

        // Escape single quotes for Snowflake SQL
        $search = str_replace("'", "''", $search);

        $where = "
            WHERE
                LOWER(COALESCE(customer_id, '')) LIKE LOWER('%{$search}%')
                OR LOWER(COALESCE(name, '')) LIKE LOWER('%{$search}%')
                OR LOWER(COALESCE(email, '')) LIKE LOWER('%{$search}%')
                OR LOWER(COALESCE(phone, '')) LIKE LOWER('%{$search}%')
                OR LOWER(COALESCE(address, '')) LIKE LOWER('%{$search}%')
        ";
    }

    /*
    |--------------------------------------------------------------------------
    | Get Total Records Count
    |--------------------------------------------------------------------------
    */
    $countSql = "
        SELECT COUNT(*) AS TOTAL
        FROM customer_payments
        {$where}
    ";

    $countResult = odbc_exec($snowflake, $countSql);

    if (!$countResult) {
        throw new Exception('Count Query Error: ' . odbc_errormsg($snowflake));
    }

    $countRow = odbc_fetch_array($countResult);

    $totalRecords = (int)($countRow['TOTAL'] ?? 0);
    $totalPages   = max(1, (int)ceil($totalRecords / $limit));

    /*
    |--------------------------------------------------------------------------
    | Get Paginated Records
    |--------------------------------------------------------------------------
    */
    $sql = "
        SELECT
            customer_id,
            name,
            email,
            phone,
            address,
            created_at,
            card_data
        FROM customer_payments
        {$where}
        ORDER BY created_at DESC
        LIMIT {$limit}
        OFFSET {$offset}
    ";

    $result = odbc_exec($snowflake, $sql);

    if (!$result) {
        throw new Exception('Data Query Error: ' . odbc_errormsg($snowflake));
    }

    $items = [];

    while ($row = odbc_fetch_array($result)) {

        $cardData = $row['CARD_DATA'] ?? '';

        $encryptedCardData = '';
        $checkoutDetails   = '';

        if (!empty($cardData)) {

            $decoded = json_decode($cardData, true);

            if (json_last_error() === JSON_ERROR_NONE) {

                if (!empty($decoded['checkoutDetails'])) {
                    $checkoutDetails = json_encode(
                        $decoded['checkoutDetails'],
                        JSON_UNESCAPED_SLASHES
                    );
                }

                if (!empty($decoded['encryptedCardData'])) {
                    $encryptedCardData = json_encode(
                        $decoded['encryptedCardData'],
                        JSON_UNESCAPED_SLASHES
                    );
                }
            }
        }

        $items[] = [
            'customerId'      => (string)($row['CUSTOMER_ID'] ?? ''),
            'name'            => (string)($row['NAME'] ?? ''),
            'email'           => (string)($row['EMAIL'] ?? ''),
            'phone'           => (string)($row['PHONE'] ?? ''),
            'address'         => (string)($row['ADDRESS'] ?? ''),
            'createdAt'       => (string)($row['CREATED_AT'] ?? ''),
            'cardData'        => $encryptedCardData,
            'checkoutDetails' => $checkoutDetails,
        ];
    }

    echo json_encode([
        'success'       => true,
        'search'        => $search,
        'currentPage'   => $page,
        'perPage'       => $limit,
        'totalRecords'  => $totalRecords,
        'totalPages'    => $totalPages,
        'count'         => count($items),
        'data'          => $items
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}