<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../warehouse.php';
require_once __DIR__ . '/validation.php';

$database = "WEEDEXDEV";
$schema   = "PUBLIC";

sysadmin($snowflake);

$migration = "admin";

/**
 * 1. CHECK MIGRATION
 */
    /**
     * 2. CREATE ADMIN TABLE
     */
    $drop = "DROP TABLE ADMIN";
    odbc_exec($snowflake, $drop);

    $tableSql = "
        CREATE TABLE IF NOT EXISTS $database.$schema.ADMIN (
            ID NUMBER AUTOINCREMENT,
            USER_NAME STRING,
            EMAIL STRING,
            PASSWORD STRING,
            SUPER_USER BOOLEAN,
            RESET_OTP STRING,
            RESET_EXPIRES NUMBER,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP()
        )
    ";

    $res = odbc_exec($snowflake, $tableSql);

    if (!$res) {
        die("Admin table creation failed: " . odbc_errormsg($snowflake));
    }

    echo "Admin table created\n";

    /**
     * 3. CHECK IF DEFAULT USER EXISTS
     */
    $checkSql = "
        SELECT 1
        FROM $database.$schema.ADMIN
        WHERE USER_NAME = 'admin-1'
        LIMIT 1
    ";

    $check = odbc_exec($snowflake, $checkSql);

    if (!$check || !odbc_fetch_row($check)) {

        $passwordHash = password_hash("admin123", PASSWORD_DEFAULT);
        $passwordHashEsc = str_replace("'", "''", $passwordHash);

        /**
         * 4. INSERT DEFAULT USER
         */
        $insertSql = "
            INSERT INTO $database.$schema.ADMIN (
                USER_NAME,
                EMAIL,
                PASSWORD
            )
            VALUES (
                'admin-1',
                'weedexbot@yopmail.com',
                '$passwordHashEsc'
            )
        ";

        $insert = odbc_exec($snowflake, $insertSql);

        if (!$insert) {
            die("Default user insert failed: " . odbc_errormsg($snowflake));
        }

        echo "Default admin user created\n";

    } else {
        echo "Default admin already exists\n";
    }

    echo "Migration completed\n";