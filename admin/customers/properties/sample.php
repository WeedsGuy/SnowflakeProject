<?php

set_time_limit(0);
ob_start(); // start buffering

// ------------------------------------------
// CONFIGURATION
// ------------------------------------------
$offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
$limit  = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 1000;
$filename = 'prospectArea_sample.csv';

// ------------------------------------------
// FORCE CSV DOWNLOAD
// ------------------------------------------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// ------------------------------------------
// OPEN OUTPUT STREAM
// ------------------------------------------
$output = fopen('php://output', 'w');
if (!$output) {
    echo json_encode(['processed' => 0, 'all_done' => false, 'error' => 'Cannot open output stream']);
    exit;
}

// ------------------------------------------
// COLUMN HEADERS MATCHING DB TABLE
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
// SAMPLE DATA MATCHING DB COLUMNS
// ------------------------------------------
$sampleData = [
    [
        'John Doe',
        '123 Main Street',
        'Los Angeles',
        'CA',
        '90001',
        '34.052235',
        '-118.243683',
        '0.11',
        '555-1234',
        'john@example.com',
        '555-5678',
        '1 Branch St Suite 100',
        'LA Branch',
        'Residential'
    ],
    [
        'Jane Smith',
        '456 Market Street',
        'San Francisco',
        'CA',
        '94105',
        '37.774929',
        '-122.419418',
        '0.18',
        '555-9876',
        'jane@example.com',
        '555-4321',
        '200 Market St',
        'SF Branch',
        'Commercial'
    ]
];

// ------------------------------------------
// SKIP OFFSET
// ------------------------------------------
for ($i = 0; $i < $offset; $i++) {
    array_shift($sampleData); // remove rows before offset
}

// ------------------------------------------
// PROCESS SAMPLE DATA
// ------------------------------------------
$processed = 0;

foreach ($sampleData as $row) {
    if ($processed >= $limit) break;

    // Normalize each value
    $row = array_map(function($v) {
        return trim((string)$v) ?: null;
    }, $row);

    fputcsv($output, $row);
    $processed++;
}

// ------------------------------------------
// FLUSH OUTPUT
// ------------------------------------------
fclose($output);
ob_end_flush();
exit;