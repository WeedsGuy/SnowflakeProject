<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db.php';

try {

    $sql = "
        SELECT
            user_name,
            email,
            super_user
        FROM admin
        ORDER BY user_name
    ";

    $result = odbc_exec($snowflake, $sql);

    if (!$result) {
        throw new Exception(
            odbc_errormsg($snowflake)
        );
    }

    $items = [];

    while ($row = odbc_fetch_array($result)) {

        $items[] = [
            'user_name' => $row['USER_NAME'] ?? '',
            'email' => $row['EMAIL'] ?? '',
            'super_user' => filter_var(
                $row['SUPER_USER'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($items),
        'data' => $items
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}