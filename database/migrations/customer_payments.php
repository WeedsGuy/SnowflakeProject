<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../warehouse.php';
require_once __DIR__.'/validation.php';
$database = "WEEDEXDEV";
$schema   = "PUBLIC";
$table    = "CUSTOMER_PAYMENTS";

/**
 * Ensure correct warehouse is used
 */
sysadmin($snowflake);

/**
 * 1. CREATE TABLE
 */
if (!tableExists($snowflake, $database, $schema, $table)) {

    $sql = "
        CREATE TABLE IF NOT EXISTS $database.$schema.$table (
            CUSTOMER_ID STRING,
            NAME STRING,
            EMAIL STRING,
            PHONE STRING,
            ADDRESS STRING,
            CARD_TOKEN STRING,
            CARD_DATA STRING,
            CREATED_AT TIMESTAMP
        )
    ";

    $res = odbc_exec($snowflake, $sql);

    if (!$res) {
        die("Table creation failed: " . odbc_errormsg($snowflake));
    }

    echo "Table created\n";
} else {
    echo "Table already exists\n";
}

/**
 * 2. ADD COLUMN SAFELY
 */
if (!columnExists($snowflake, $database, $schema, $table, "STATUS")) {

    $sql = "
        ALTER TABLE $database.$schema.$table
        ADD COLUMN STATUS STRING
    ";

    $res = odbc_exec($snowflake, $sql);

    if (!$res) {
        die("Column add failed: " . odbc_errormsg($snowflake));
    }

    echo "Column STATUS added\n";
} else {
    echo "Column STATUS already exists\n";
}