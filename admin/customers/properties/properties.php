<?php

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);   
ini_set('memory_limit', '-1');

require_once(__DIR__.'/../../../database/db.php'); // Creates $snowflake

// ------------------------------------------
// CONFIGURATION
// ------------------------------------------
$file   = __DIR__ . '/uploads/' . basename($_REQUEST['file'] ?? '');
$offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
$limit  = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 1000;

if (!$file || !file_exists($file)) {
    echo json_encode([
        'processed' => 0,
        'all_done'  => false,
        'error'     => 'File not found'
    ]);
    exit;
}

// ------------------------------------------
// CREATE TABLE IF NOT EXISTS
// ------------------------------------------
$createTable = "
CREATE TABLE IF NOT EXISTS prospectArea (
    id NUMBER AUTOINCREMENT,
    name VARCHAR,
    address VARCHAR,
    city VARCHAR,
    state VARCHAR,
    zip VARCHAR,
    lat FLOAT,
    lon FLOAT,
    area FLOAT,
    customer_phone VARCHAR,
    customer_email VARCHAR,
    branch_phone VARCHAR,
    branch_address VARCHAR,
    branch_name VARCHAR,
    type VARCHAR
)
";

odbc_exec($snowflake, $createTable);

// ------------------------------------------
// HELPER
// ------------------------------------------
function getValue($row, $key)
{
    return (isset($row[$key]) && trim($row[$key]) !== '')
        ? trim($row[$key])
        : null;
}

// ------------------------------------------
// MERGE (UPSERT)
// ------------------------------------------
$mergeSql = "
MERGE INTO prospectArea t
USING (
    SELECT
        ? AS name,
        ? AS address,
        ? AS city,
        ? AS state,
        ? AS zip,
        ? AS lat,
        ? AS lon,
        ? AS area,
        ? AS customer_phone,
        ? AS customer_email,
        ? AS branch_phone,
        ? AS branch_address,
        ? AS branch_name,
        ? AS type
) s
ON t.address = s.address

WHEN MATCHED THEN UPDATE SET
    name            = COALESCE(s.name, t.name),
    city            = COALESCE(s.city, t.city),
    state           = COALESCE(s.state, t.state),
    zip             = COALESCE(s.zip, t.zip),
    lat             = COALESCE(s.lat, t.lat),
    lon             = COALESCE(s.lon, t.lon),
    area            = COALESCE(s.area, t.area),
    customer_phone  = COALESCE(s.customer_phone, t.customer_phone),
    customer_email  = COALESCE(s.customer_email, t.customer_email),
    branch_phone    = COALESCE(s.branch_phone, t.branch_phone),
    branch_address  = COALESCE(s.branch_address, t.branch_address),
    branch_name     = COALESCE(s.branch_name, t.branch_name),
    type            = COALESCE(s.type, t.type)

WHEN NOT MATCHED THEN
INSERT (
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
)
VALUES (
    s.name,
    s.address,
    s.city,
    s.state,
    s.zip,
    s.lat,
    s.lon,
    s.area,
    s.customer_phone,
    s.customer_email,
    s.branch_phone,
    s.branch_address,
    s.branch_name,
    s.type
)
";

$stmt = odbc_prepare($snowflake, $mergeSql);

if (!$stmt) {
    echo json_encode([
        'processed' => 0,
        'all_done'  => false,
        'error'     => odbc_errormsg($snowflake)
    ]);
    exit;
}

// ------------------------------------------
// OPEN CSV
// ------------------------------------------
$handle = fopen($file, "r");

if (!$handle) {
    echo json_encode([
        'processed' => 0,
        'all_done'  => false,
        'error'     => 'Cannot open file'
    ]);
    exit;
}

// ------------------------------------------
// HEADERS
// ------------------------------------------
$headers = fgetcsv($handle);

$headers = array_map(function ($h) {
    return str_replace(
        [' ', '-'],
        '_',
        strtolower(trim($h))
    );
}, $headers);

// ------------------------------------------
// SKIP OFFSET
// ------------------------------------------
for ($i = 0; $i < $offset; $i++) {
    fgetcsv($handle);
}

$processed = 0;

// ------------------------------------------
// PROCESS CSV
// ------------------------------------------
while ($processed < $limit && ($data = fgetcsv($handle)) !== false) {

    $data = array_pad($data, count($headers), null);

    $row = array_combine($headers, $data);

    if (!is_array($row)) {
        continue;
    }

    // Skip if no address
    $address = getValue($row, 'address');

    if (!$address) {
        continue;
    }

    $address = trim($address);

    // Area validation
    $area = getValue($row, 'customersize');
    $area = is_numeric($area) ? (float)$area : null;

    // Branch address
    $branchAddress = trim(
        (getValue($row, 'branchaddressline1') ?? '') . ' ' .
        (getValue($row, 'branchaddressline2') ?? '') . ' ' .
        (getValue($row, 'branchaddressline3') ?? '')
    );

    $params = [
        getValue($row, 'customername'),
        $address,
        getValue($row, 'city'),
        getValue($row, 'state'),
        getValue($row, 'zipcode'),
        getValue($row, 'latitude'),
        getValue($row, 'longitude'),
        $area,
        getValue($row, 'preferredphonenumber'),
        getValue($row, 'emailaddress'),
        getValue($row, 'branchcontactphonenumber1'),
        $branchAddress ?: null,
        getValue($row, 'branchnameofcustomer'),
        getValue($row, 'residentialorcommercial')
    ];

    if (!odbc_execute($stmt, $params)) {
        echo json_encode([
            'processed' => $processed,
            'all_done'  => false,
            'error'     => odbc_errormsg($snowflake)
        ]);
        fclose($handle);
        exit;
    }

    $processed++;
}

fclose($handle);

// ------------------------------------------
// RESPONSE
// ------------------------------------------
echo json_encode([
    'processed' => $processed,
    'all_done'  => ($processed < $limit)
]);