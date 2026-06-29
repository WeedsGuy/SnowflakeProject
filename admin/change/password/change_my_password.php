<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../database/warehouse.php';
if (empty($_SESSION['admin_user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$currentPassword = trim(
    $data['current_password'] ?? ''
);

$newPassword = trim(
    $data['new_password'] ?? ''
);

if (!$currentPassword || !$newPassword) {

    echo json_encode([
        'success' => false,
        'message' => 'Missing fields'
    ]);
    exit;
}

try {

    $username = $_SESSION['admin_user'];
    $email = $_SESSION['admin_email'];

    $usernameEsc = str_replace(
        "'",
        "''",
        $username
    );

    $emailEsc = str_replace(
        "'",
        "''",
        $email
    );

    /*
     * Get user
     */
    $sql = "
        SELECT
            password
        FROM admin
        WHERE user_name = '$usernameEsc'
        AND email = '$emailEsc'
        LIMIT 1
    ";
    sysadmin($snowflake);
    $result = odbc_exec(
        $snowflake,
        $sql
    );

    if (!$result) {
        throw new Exception(
            odbc_errormsg($snowflake)
        );
    }

    $row = odbc_fetch_array($result);

    if (!$row) {

        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    $storedHash = $row['PASSWORD'];

    if (!password_verify(
        $currentPassword,
        $storedHash
    )) {

        echo json_encode([
            'success' => false,
            'message' => 'Current password incorrect'
        ]);
        exit;
    }

    /*
     * Update password
     */
    $newHash = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    $newHashEsc = str_replace(
        "'",
        "''",
        $newHash
    );

    $updateSql = "
        UPDATE admin
        SET password = '$newHashEsc'
        WHERE user_name = '$usernameEsc'
        AND email = '$emailEsc'
    ";

    $updateResult = odbc_exec(
        $snowflake,
        $updateSql
    );

    if (!$updateResult) {
        throw new Exception(
            odbc_errormsg($snowflake)
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}