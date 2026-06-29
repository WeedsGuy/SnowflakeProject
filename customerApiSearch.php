<?php

header('Content-Type: application/json');

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/warehouse.php';

$search = trim($_GET['address'] ?? '');

if (empty($search)) {
    echo json_encode([
        "exists" => false,
        "error" => "address is required"
    ]);
    exit;
}

try {

    datafactory($snowflake);

    $search = strtolower($search);

    $sql = "
        SELECT
            CUSTOMER_NUMBER,
            CUSTOMER_SIZE AS AREA,
            CUSTOMER_EMAIL,
            CUSTOMER_STATUS,
            CUSTOMER_CITY AS CITY,
            CONCAT(
                CUSTOMER_STREET_NUMBER, ' ',
                CUSTOMER_STREET_NAME, ' ',
                CUSTOMER_SUFFIX, ', ',
                CUSTOMER_CITY, ', ',
                CUSTOMER_STATE, ' ',
                CUSTOMER_ZIP
            ) AS ADDRESS
        FROM DIM_CUSTOMER
        WHERE LOWER(CONCAT(
            CUSTOMER_STREET_NUMBER, ' ',
            CUSTOMER_STREET_NAME, ' ',
            CUSTOMER_SUFFIX, ', ',
            CUSTOMER_CITY, ', ',
            CUSTOMER_STATE, ' ',
            CUSTOMER_ZIP
        )) LIKE '%{$search}%'
        LIMIT 1
    ";

    $result = odbc_exec($snowflake, $sql);

    if (!$result) {
        throw new Exception(odbc_errormsg($snowflake));
    }

    $data = odbc_fetch_array($result);

    if (!$data) {
        echo json_encode([
            "exists" => false,
            "data" => null
        ]);
        exit;
    }

   $data = array_change_key_case($data, CASE_LOWER);

    // check status == 9
    $exists = isset($data['customer_status']) && $data['customer_status'] == 9;

    echo json_encode([
        "exists" => $exists,
        "area" => $data["area"] ?? null,
        "city" => $data["city"] ?? null,
        "address" => $data["address"] ?? null,
        //"data" => $data
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "exists" => false,
        "error" => $e->getMessage()
    ]);
}