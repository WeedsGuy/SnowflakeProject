<?php

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/warehouse.php';

set_time_limit(0);
ini_set('memory_limit', '-1');

echo "Starting daily sync...\n";

/*
|--------------------------------------------------------------------------
| STEP 1: READ TODAY'S UPDATED RECORDS
|--------------------------------------------------------------------------
*/

datafactory($snowflake);

$sql = "
WITH CUSTOMER_DATA AS (

    SELECT
        CUSTOMER_NUMBER,

        TRIM(
            CONCAT(
                COALESCE(CUSTOMER_FIRST_NAME,''),
                ' ',
                COALESCE(CUSTOMER_LAST_NAME,'')
            )
        ) AS NAME,

        TRIM(
            CONCAT_WS(
                ', ',

                NULLIF(
                    TRIM(
                        CONCAT(
                            COALESCE(CUSTOMER_STREET_NUMBER,''),
                            ' ',
                            COALESCE(CUSTOMER_STREET_NAME,''),
                            CASE
                                WHEN CUSTOMER_SUFFIX IS NOT NULL
                                AND CUSTOMER_SUFFIX <> ''
                                THEN CONCAT(' ', CUSTOMER_SUFFIX)
                                ELSE ''
                            END
                        )
                    ),
                    ''
                ),

                NULLIF(TRIM(CUSTOMER_CITY), ''),
                NULLIF(TRIM(CUSTOMER_STATE), ''),
                NULLIF(TRIM(CUSTOMER_ZIP), '')
            )
        ) AS ADDRESS,

        CUSTOMER_CITY AS CITY,
        CUSTOMER_STATE AS STATE,
        CUSTOMER_ZIP AS ZIP,

        CUSTOMER_LATITUDE AS LAT,
        CUSTOMER_LONGITUDE AS LON,

        CUSTOMER_SIZE AS AREA,

        CUSTOMER_PHONE_PREFERRED AS CUSTOMER_PHONE,
        CUSTOMER_EMAIL,
        BRANCH_CODE,
        CUSTOMER_STATUS,
        CUSTOMER_STATUS_DESCRIPTION,

        CUSTOMER_CREATED,
        CUSTOMER_UPDATED

    FROM DIM_CUSTOMER

    WHERE CUSTOMER_UPDATED >= CURRENT_DATE()

)

SELECT *
FROM (

    SELECT
        *,
        ROW_NUMBER() OVER (
            PARTITION BY UPPER(TRIM(ADDRESS))
            ORDER BY CUSTOMER_UPDATED DESC
        ) AS RN
    FROM CUSTOMER_DATA

)
WHERE RN = 1
AND ADDRESS IS NOT NULL
AND TRIM(ADDRESS) <> ''
";

$result = odbc_exec($snowflake, $sql);

if (!$result) {
    die("DIM_CUSTOMER Error: " . odbc_errormsg($snowflake));
}

/*
|--------------------------------------------------------------------------
| STEP 2: LOAD EXISTING ADDRESSES FROM PROSPECT_AREA
|--------------------------------------------------------------------------
*/

sysadmin($snowflake);

$existingAddresses = [];

$existingSql = "
    SELECT UPPER(TRIM(ADDRESS)) AS ADDRESS_KEY
    FROM PROSPECT_AREA
    WHERE ADDRESS IS NOT NULL
";

$existingResult = odbc_exec($snowflake, $existingSql);

if ($existingResult) {
    while ($existing = odbc_fetch_array($existingResult)) {
        $existingAddresses[$existing['ADDRESS_KEY']] = true;
    }
}

echo "Existing Addresses: " . count($existingAddresses) . "\n";

/*
|--------------------------------------------------------------------------
| STEP 3: INSERT ONLY NEW ADDRESSES
|--------------------------------------------------------------------------
*/

$batchSize = 1000;
$values = [];

$totalInserted = 0;
$totalUpdated  = 0;
$totalSkipped  = 0;

while ($row = odbc_fetch_array($result)) {

    $customerNumber = str_replace("'", "''", $row['CUSTOMER_NUMBER'] ?? '');
    $name           = str_replace("'", "''", $row['NAME'] ?? '');
    $address        = str_replace("'", "''", trim($row['ADDRESS'] ?? ''));

    if ($address == '') {
        continue;
    }

    $addressKey = strtoupper($address);

    $city = str_replace("'", "''", $row['CITY'] ?? '');
    $state = str_replace("'", "''", $row['STATE'] ?? '');
    $zip = str_replace("'", "''", $row['ZIP'] ?? '');

    $lat = is_numeric($row['LAT']) ? $row['LAT'] : 0;
    $lon = is_numeric($row['LON']) ? $row['LON'] : 0;
    $area = is_numeric($row['AREA']) ? $row['AREA'] : 0;

    $phone = str_replace("'", "''", $row['CUSTOMER_PHONE'] ?? '');
    $email = str_replace("'", "''", $row['CUSTOMER_EMAIL'] ?? '');

    $status = str_replace("'", "''", $row['CUSTOMER_STATUS'] ?? '');
    $branchCode = str_replace("'", "''", $row['BRANCH_CODE'] ?? '');
    $statusDescription = str_replace("'", "''", $row['CUSTOMER_STATUS_DESCRIPTION'] ?? '');

    $updated = !empty($row['CUSTOMER_UPDATED'])
        ? "'" . $row['CUSTOMER_UPDATED'] . "'"
        : "NULL";

    $created = !empty($row['CUSTOMER_CREATED'])
        ? "'" . $row['CUSTOMER_CREATED'] . "'"
        : "NULL";

    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING ADDRESS
    |--------------------------------------------------------------------------
    */

    if (isset($existingAddresses[$addressKey])) {

        $updateSql = "
            UPDATE PROSPECT_AREA
            SET
                CUSTOMER_NUMBER = '$customerNumber',
                NAME = '$name',
                CITY = '$city',
                STATE = '$state',
                ZIP = '$zip',
                LAT = $lat,
                LON = $lon,
                AREA = $area,
                CUSTOMER_PHONE = '$phone',
                CUSTOMER_EMAIL = '$email',
                BRANCH_CODE = '$branchCode',
                CUSTOMER_STATUS = '$status',
                CUSTOMER_STATUS_DESCRIPTION = '$statusDescription',
                CUSTOMER_UPDATED = $updated,
                CUSTOMER_CREATED = $created,
                LAST_SYNCED = CURRENT_TIMESTAMP()
            WHERE UPPER(TRIM(ADDRESS)) = '$addressKey'
        ";
        echo "Data updated $customerNumber.\n";
        if (!odbc_exec($snowflake, $updateSql)) {
            die("Update Error: " . odbc_errormsg($snowflake));
        }

        $totalUpdated++;
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT NEW ADDRESS
    |--------------------------------------------------------------------------
    */

    $existingAddresses[$addressKey] = true;

    $values[] = "(
        '$customerNumber',
        '$name',
        '$address',
        '$city',
        '$state',
        '$zip',
        $lat,
        $lon,
        $area,
        '$phone',
        '$email',
        '$branchCode',
        '$status',
        '$statusDescription',
        $updated,
        $created,
        CURRENT_TIMESTAMP()
    )";

    if (count($values) >= $batchSize) {

        $insertSql = "
            INSERT INTO PROSPECT_AREA (
                CUSTOMER_NUMBER,
                NAME,
                ADDRESS,
                CITY,
                STATE,
                ZIP,
                LAT,
                LON,
                AREA,
                CUSTOMER_PHONE,
                CUSTOMER_EMAIL,
                BRANCH_CODE,
                CUSTOMER_STATUS,
                CUSTOMER_STATUS_DESCRIPTION,
                CUSTOMER_UPDATED,
                CUSTOMER_CREATED,
                LAST_SYNCED
            )
            VALUES
            " . implode(',', $values);

        if (!odbc_exec($snowflake, $insertSql)) {
            die("Insert Error: " . odbc_errormsg($snowflake));
        }

        $totalInserted += count($values);

        echo "Inserted: {$totalInserted}, Updated: {$totalUpdated}\n";

        $values = [];
    }
}

/*
|--------------------------------------------------------------------------
| INSERT REMAINING RECORDS
|--------------------------------------------------------------------------
*/

if (!empty($values)) {

    $insertSql = "
        INSERT INTO PROSPECT_AREA (
            CUSTOMER_NUMBER,
            NAME,
            ADDRESS,
            CITY,
            STATE,
            ZIP,
            LAT,
            LON,
            AREA,
            CUSTOMER_PHONE,
            CUSTOMER_EMAIL,
            BRANCH_CODE,
            CUSTOMER_STATUS,
            CUSTOMER_STATUS_DESCRIPTION,
            CUSTOMER_UPDATED,
            CUSTOMER_CREATED,
            LAST_SYNCED
        )
        VALUES
        " . implode(',', $values);

    if (!odbc_exec($snowflake, $insertSql)) {
        die("Insert Error: " . odbc_errormsg($snowflake));
    }

    $totalInserted += count($values);

    echo "Inserted: {$totalInserted}, Updated: {$totalUpdated}\n";
}

echo "\n";
echo "=====================================\n";
echo "Total Inserted : {$totalInserted}\n";
echo "Total Updated  : {$totalUpdated}\n";
echo "Total Skipped  : {$totalSkipped}\n";
echo "Sync Completed Successfully\n";
echo "=====================================\n";