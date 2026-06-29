<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;  // ← aliased to avoid conflict with \Exception

require __DIR__ . '/../../vendor/autoload.php';

$baseUrl =
    (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
    $_SERVER['HTTP_HOST'] .
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$error = "";
$success = "";
require_once __DIR__.'/../../config.php';
function forgotPassword($otp, $email = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'weedexbot@gmail.com';
        $mail->Password   = 'phbk drep ngxv tjon';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        $mail->Port       = 587;

        $mail->setFrom('weedexbot@gmail.com', 'Weedex Lawn Care');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Forgot Password - OTP Verification';

        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Your OTP is:</p>
            <h1 style='color:#020617;'>$otp</h1>
            <p>This OTP is valid for 10 minutes.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!$email) {

        $error = "Email required";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address";

    } else {

        try {

            require_once __DIR__ . '/../../database/db.php';
            require_once __DIR__ . '/../../database/warehouse.php';
            sysadmin($snowflake);
            $emailEsc = str_replace("'", "''", $email);

            $sql = "
                SELECT
                    email,
                    user_name
                FROM admin
                WHERE email = '$emailEsc'
                LIMIT 1
            ";

            $result = odbc_exec($snowflake, $sql);

            if (!$result) {
                throw new Exception(
                    odbc_errormsg($snowflake)
                );
            }

            $row = odbc_fetch_array($result);

            if (!$row) {

                $error = "Email not found";

            } else {

                $otp = random_int(100000, 999999);
                $expires = time() + 600;

                $updateSql = "
                    UPDATE admin
                    SET
                        reset_otp = '$otp',
                        reset_expires = $expires
                    WHERE email = '$emailEsc'
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

                $sent = forgotPassword(
                    $otp,
                    $email
                );

                if ($sent) {

                    $_SESSION['reset_email'] = $email;

                    header(
                        "Location: $baseUrl/reset/password"
                    );
                    exit;

                } else {

                    $error =
                        "Failed to send OTP. Please try again.";
                }
            }

        } catch (Throwable $e) {

            error_log(
                "Forgot Password Error: " .
                $e->getMessage()
            );

            $error =
                "Something went wrong. Please try again later.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    height: 100vh;
    margin: 0;
    background: linear-gradient(135deg, #020617, #1e293b);
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
}

.login-box {
    background: #fff;
    padding: 35px;
    width: 360px;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}

.login-box h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #020617;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-size: 14px;
    display: block;
    margin-bottom: 5px;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    outline: none;
}

.login-btn {
    width: 100%;
    padding: 10px;
    background: #020617;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

.login-btn:hover {
    background: #1e293b;
}

.message {
    margin-top: 12px;
    text-align: center;
    font-size: 14px;
}
.success { color: green; }
.error { color: red; }
</style>
</head>

<body>

<div class="login-box">
    <h2>Forgot Password</h2>

    <form method="post">
        <div class="form-group">
            <label>Email</label>
            <input type="text" name="email" required>
        </div>

        <button class="login-btn" type="submit">Send Reset OTP</button>
    </form>

    <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
</div>

</body>
</html>