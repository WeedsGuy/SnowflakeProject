<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../warehouse.php';
require_once __DIR__ . '/validation.php';

$database = "WEEDEXDEV";
$schema   = "PUBLIC";
$table    = "PROSPECT_AREA";

sysadmin($snowflake);

if (!tableExists($snowflake, $database, $schema, $table)) {

    $sql = "
        CREATE TABLE IF NOT EXISTS $database.$schema.$table (
            ID NUMBER AUTOINCREMENT,
            CUSTOMER_NUMBER NUMBER,

            NAME STRING,
            ADDRESS STRING,

            CITY STRING,
            STATE STRING,
            ZIP STRING,

            LAT FLOAT,
            LON FLOAT,
            AREA FLOAT,

            CUSTOMER_PHONE STRING,
            CUSTOMER_EMAIL STRING,

            BRANCH_CODE STRING,

            CUSTOMER_STATUS STRING,
            CUSTOMER_STATUS_DESCRIPTION STRING,

            CUSTOMER_CREATED TIMESTAMP,
            CUSTOMER_UPDATED TIMESTAMP,

            LAST_SYNCED TIMESTAMP DEFAULT CURRENT_TIMESTAMP()
        )
    ";

    $res = odbc_exec($snowflake, $sql);

    if (!$res) {
        die("Table creation failed: " . odbc_errormsg($snowflake));
    }

    echo "PROSPECT_AREA table created\n";
} else {
    echo "PROSPECT_AREA table already exists\n";
}