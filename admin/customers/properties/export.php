<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__.'/../../../database/db.php'); // $snowflake
set_time_limit(0);
ini_set('memory_limit', '-1');

// ------------------------------------------
// CONFIGURATION
// ------------------------------------------
$offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
$limit  = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 1000;

// ------------------------------------------
// FORCE CSV DOWNLOAD
// ------------------------------------------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="prospectArea_export.csv"');

$output = fopen('php://output', 'w');

// ------------------------------------------
// CSV HEADERS
// ------------------------------------------
$columns = [
    'name',
    'address',
    'city',
    'state',
    'zip',
    'lat',
    'lon',
    'area',
    'customer_phone',
    'customer_email',
    'branch_phone',
    'branch_address',
    'branch_name',
    'type'
];

fputcsv($output, $columns);

// ------------------------------------------
// QUERY (NO BINDING FOR LIMIT/OFFSET IN SNOWFLAKE)
// ------------------------------------------
$sql = "
    SELECT
        name,
        address,
        city,
        state,
        zip,
        lat,
        lon,
        area,
        customer_phone,
        customer_email,
        branch_phone,
        branch_address,
        branch_name,
        type
    FROM prospectArea
    ORDER BY id ASC
    LIMIT $limit OFFSET $offset
";

$stmt = odbc_exec($snowflake, $sql);

if (!$stmt) {
    fwrite($output, "ERROR: " . odbc_errormsg($snowflake));
    fclose($output);
    exit;
}

// ------------------------------------------
// WRITE ROWS
// ------------------------------------------
while ($row = odbc_fetch_array($stmt)) {

    $row = array_map(function ($v) {
        return isset($v) ? trim((string)$v) : '';
    }, $row);

    fputcsv($output, $row);
}

// ------------------------------------------
// CLOSE OUTPUT
// ------------------------------------------
fclose($output);
exit;