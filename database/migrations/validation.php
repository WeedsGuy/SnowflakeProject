<?php
/**
 * CHECK IF TABLE EXISTS
 */
function tableExists($conn, string $database, string $schema, string $table): bool
{
    $sql = "
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_CATALOG = UPPER('$database')
        AND TABLE_SCHEMA = UPPER('$schema')
        AND TABLE_NAME = UPPER('$table')
        LIMIT 1
    ";

    $res = odbc_exec($conn, $sql);
    return $res && odbc_fetch_row($res);
}

/**
 * CHECK IF COLUMN EXISTS
 */
function columnExists($conn, string $database, string $schema, string $table, string $column): bool
{
    $sql = "
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_CATALOG = UPPER('$database')
        AND TABLE_SCHEMA = UPPER('$schema')
        AND TABLE_NAME = UPPER('$table')
        AND COLUMN_NAME = UPPER('$column')
        LIMIT 1
    ";

    $res = odbc_exec($conn, $sql);
    return $res && odbc_fetch_row($res);
}

function hasMigration($conn, string $database, string $schema, string $migration): bool
{
    $sql = "
        SELECT 1
        FROM $database.$schema.MIGRATIONS
        WHERE MIGRATION_NAME = '$migration'
        LIMIT 1
    ";

    $res = odbc_exec($conn, $sql);

    return $res && odbc_fetch_row($res);
}
