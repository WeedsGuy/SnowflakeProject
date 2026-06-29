<?php

header('Content-Type: application/json');

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/warehouse.php';

$search = trim($_GET['address'] ?? '');

if (strlen($search) < 3) {
    echo json_encode([]);
    exit;
}

try {

    $words = preg_split('/[\s,]+/', strtolower($search));
    $where = [];

    foreach ($words as $word) {

        $word = trim($word);

        if ($word === '') {
            continue;
        }

        $word = str_replace("'", "''", $word);

        $where[] = "LOWER(ADDRESS) LIKE '%{$word}%'";
    }

    if (empty($where)) {
        echo json_encode([]);
        exit;
    }

    datafactory($snowflake);

    $sql = "
        SELECT
            CUSTOMER_NUMBER,
            AREA,
            CUSTOMER_EMAIL,
            ADDRESS,
            CITY
        FROM (
            SELECT
                CUSTOMER_NUMBER,
                CUSTOMER_SIZE AS AREA,
                CUSTOMER_EMAIL,
                CUSTOMER_CITY AS CITY,

                CONCAT(
                    CUSTOMER_STREET_NUMBER, ' ',
                    CUSTOMER_STREET_NAME, ' ',
                    CUSTOMER_SUFFIX, ', ',
                    CUSTOMER_CITY, ', ',
                    CUSTOMER_STATE, ' ',
                    CUSTOMER_ZIP
                ) AS ADDRESS,

                CUSTOMER_CREATED

            FROM DIM_CUSTOMER
        ) X

        WHERE " . implode(' AND ', $where) . "

        ORDER BY CUSTOMER_CREATED DESC
        LIMIT 5
    ";

    $result = odbc_exec($snowflake, $sql);

    if (!$result) {
        throw new Exception(odbc_errormsg($snowflake));
    }

    $data = [];

    while ($row = odbc_fetch_array($result)) {
        $data[] = array_change_key_case($row, CASE_LOWER);
    }

    echo json_encode($data);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}