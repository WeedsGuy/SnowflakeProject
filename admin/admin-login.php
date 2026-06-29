<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/warehouse.php';
$baseUrl =
    (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
    $_SERVER['HTTP_HOST'] .
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: $baseUrl");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {

        $error = "Email and password required";

    } else {

        try {

            $emailEsc = str_replace("'", "''", $email);

            $sql = "
                SELECT
                    email,
                    password,
                    user_name,
                    super_user
                FROM admin
                WHERE email = '$emailEsc'
                LIMIT 1
            ";
            sysadmin($snowflake);
            $result = odbc_exec($snowflake, $sql);

            if (!$result) {
                throw new Exception(
                    odbc_errormsg($snowflake)
                );
            }

            $row = odbc_fetch_array($result);

            if (!$row) {

                $error = "Invalid email or password";

            } else {

                $hash = $row['PASSWORD'];

                if (password_verify($password, $hash)) {

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user'] = $row['USER_NAME'] ?? '';
                    $_SESSION['admin_email'] = $row['EMAIL'] ?? '';

                    $_SESSION['super_user'] =
                        filter_var(
                            $row['SUPER_USER'] ?? false,
                            FILTER_VALIDATE_BOOLEAN
                        );

                    header("Location: $baseUrl");
                    exit;

                } else {

                    $error = "Invalid email or password";
                }
            }

        } catch (Throwable $e) {

            $error = "Login error: " . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__.'/../config.php' ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
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

.form-group input:focus {
    border-color: #020617;
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

.error {
    margin-top: 12px;
    color: red;
    text-align: center;
    font-size: 14px;
}
</style>
</head>

<body>

<div class="login-box">
    <h2>Admin Login</h2>

    <form method="post">
        <div class="form-group">
            <label>email</label>
            <input type="text" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button class="login-btn" type="submit">Login</button>
        <a href="<?= $baseUrl ?>/reset"
        style="
            text-align:center;
            display:inline-block;
            padding:10px 20px;
            text-decoration:none;
            border-radius:6px;
            width:100%;
        ">
        Reset Password
        </a>
    </form>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
</div>

</body>
</html>
