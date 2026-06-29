<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db.php';

try {

    $data = json_decode(file_get_contents("php://input"), true);

    $user_name = trim($data['user_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $yourPassword = trim($data['your_password'] ?? '');

    if (!$user_name || !$email || !$password) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }

    $userNameEsc = str_replace("'", "''", $user_name);
    $emailEsc = str_replace("'", "''", $email);

    /*
     * Check if user already exists
     */
    $checkSql = "
        SELECT user_name
        FROM admin
        WHERE user_name = '$userNameEsc'
        LIMIT 1
    ";

    $checkResult = odbc_exec($snowflake, $checkSql);

    if (!$checkResult) {
        throw new Exception(odbc_errormsg($snowflake));
    }

    if (odbc_fetch_row($checkResult)) {

        echo json_encode([
            'success' => false,
            'message' => 'User already exists'
        ]);
        exit;
    }

    /*
     * Insert admin
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $passwordHashEsc = str_replace(
        "'",
        "''",
        $passwordHash
    );

    $insertSql = "
        INSERT INTO admin (
            email,
            user_name,
            password,
            created_at
        )
        VALUES (
            '$emailEsc',
            '$userNameEsc',
            '$passwordHashEsc',
            CURRENT_TIMESTAMP()
        )
    ";

    $insertResult = odbc_exec(
        $snowflake,
        $insertSql
    );

    if (!$insertResult) {
        throw new Exception(
            odbc_errormsg($snowflake)
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Admin created successfully'
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}