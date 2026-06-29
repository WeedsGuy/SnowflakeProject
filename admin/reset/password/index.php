<?php
session_start();

if (empty($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../database/warehouse.php';
sysadmin($snowflake);
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (!$otp || !$password || !$confirmPassword) {

        $error = "All fields are required.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $password)) {

        $error = "Password must contain at least one uppercase letter.";

    } elseif (!preg_match('/[0-9]/', $password)) {

        $error = "Password must contain at least one number.";

    } elseif (!preg_match('/[\W]/', $password)) {

        $error = "Password must contain at least one special character.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        try {

            $email = $_SESSION['reset_email'];
            $emailEsc = str_replace("'", "''", $email);

            $sql = "
                SELECT
                    email,
                    user_name,
                    reset_otp,
                    reset_expires
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

                $error = "Invalid request.";

            } else {

                $savedOtp = $row['RESET_OTP'] ?? null;
                $expires = (int)($row['RESET_EXPIRES'] ?? 0);

                if (!$savedOtp) {

                    $error =
                        "No OTP requested. Please go back and request again.";

                } elseif (time() > $expires) {

                    $error =
                        "OTP has expired. Please request a new one.";

                    header("Location: /admin/reset/");
                    exit;

                } elseif ((string)$otp !== (string)$savedOtp) {

                    $error =
                        "Invalid OTP. Please check and try again.";

                } else {

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    );

                    $hashedPasswordEsc = str_replace(
                        "'",
                        "''",
                        $hashedPassword
                    );

                    $updateSql = "
                        UPDATE admin
                        SET
                            password = '$hashedPasswordEsc',
                            reset_otp = NULL,
                            reset_expires = NULL
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

                    unset($_SESSION['reset_email']);

                    $success = true;
                }
            }

        } catch (Throwable $e) {

            error_log(
                "Reset Password Error: " .
                $e->getMessage()
            );

            $error =
                "Something went wrong. Please try again.";
        }
    }
}

require_once __DIR__ . '/../../../config.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    min-height: 100vh; margin: 0;
    background: linear-gradient(135deg, #020617, #1e293b);
    display: flex; justify-content: center; align-items: center;
    font-family: Arial, sans-serif;
}
.login-box {
    background: #fff; padding: 35px; width: 360px;
    border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}
.login-box h2 { text-align: center; margin-bottom: 5px; color: #020617; }
.subtitle { text-align: center; font-size: 13px; color: #64748b; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; position: relative; }
.form-group label { font-size: 14px; display: block; margin-bottom: 5px; color: #334155; }
.form-group input {
    width: 100%; padding: 10px 40px 10px 10px;
    border-radius: 6px; border: 1px solid #cbd5e1;
    outline: none; box-sizing: border-box; font-size: 14px;
}
.form-group input:focus { border-color: #020617; }

/* OTP input style */
.otp-input {
    letter-spacing: 8px; font-size: 20px !important;
    text-align: center; font-weight: bold;
}

.toggle-eye {
    position: absolute; right: 10px; top: 34px;
    cursor: pointer; font-size: 18px; user-select: none;
}

/* Strength bar */
.strength-bar { display: flex; gap: 5px; margin-top: 8px; }
.strength-bar span {
    height: 5px; flex: 1; border-radius: 3px;
    background: #e2e8f0; transition: background 0.3s;
}
.strength-label { font-size: 12px; margin-top: 4px; font-weight: bold; }

/* Rules */
.rules { margin: 8px 0 0; padding: 0; list-style: none; }
.rules li { font-size: 12px; color: #94a3b8; margin-bottom: 3px; }
.rules li.valid { color: #16a34a; }
.rules li::before { content: '✗ '; color: #ef4444; }
.rules li.valid::before { content: '✓ '; color: #16a34a; }

.match-label { font-size: 12px; margin-top: 6px; font-weight: bold; }

.divider {
    border: none; border-top: 1px solid #e2e8f0;
    margin: 18px 0;
}

.login-btn {
    width: 100%; padding: 10px; background: #020617;
    color: #fff; border: none; border-radius: 6px;
    font-size: 16px; cursor: pointer; margin-top: 5px;
}
.login-btn:hover { background: #1e293b; }

.error   { color: red;    text-align: center; font-size: 14px; margin-top: 10px; }

/* Success */
.success-box { text-align: center; padding: 10px 0; }
.success-box .checkmark { font-size: 55px; color: #16a34a; }
.success-box p { color: #020617; font-size: 15px; margin: 10px 0; }
.success-box a {
    display: inline-block; margin-top: 10px;
    padding: 10px 30px; background: #020617;
    color: #fff; border-radius: 6px; text-decoration: none; font-size: 14px;
}
.success-box a:hover { background: #1e293b; }
</style>
</head>
<body>
<div class="login-box">
    
    <?php if ($success): ?>
        <div class="success-box">
            <div class="checkmark">✓</div>
            <p>Password reset successfully!</p>
            <a href="<?= $baseUrl ?>">Back to Login</a>
        </div>

    <?php else: ?>
        <h2>Reset Password</h2>
        <p class="subtitle">Enter the OTP sent to your email and set a new password.</p>

        <form method="post" id="resetForm">

            <!-- OTP -->
            <div class="form-group">
                <label>Enter OTP</label>
                <input type="text" name="otp" class="otp-input" maxlength="6" required placeholder="••••••">
            </div>

            <hr class="divider">

            <!-- New Password -->
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter new password">
                <span class="toggle-eye" onclick="toggleVisibility('password', this)">👁️</span>

                <div class="strength-bar">
                    <span id="bar1"></span><span id="bar2"></span>
                    <span id="bar3"></span><span id="bar4"></span>
                </div>
                <div class="strength-label" id="strengthLabel"></div>

                <ul class="rules">
                    <li id="rule-length">At least 8 characters</li>
                    <li id="rule-upper">One uppercase letter</li>
                    <li id="rule-number">One number</li>
                    <li id="rule-special">One special character</li>
                </ul>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                <span class="toggle-eye" onclick="toggleVisibility('confirm_password', this)">👁️</span>
                <div class="match-label" id="matchLabel"></div>
            </div>

            <button class="login-btn" type="submit">Reset Password</button>
        </form>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function toggleVisibility(fieldId, icon) {
    const input = document.getElementById(fieldId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.textContent = input.type === 'password' ? '👁️' : '🙈';
}

const passwordInput = document.getElementById('password');
const confirmInput  = document.getElementById('confirm_password');
const bars          = ['bar1','bar2','bar3','bar4'].map(id => document.getElementById(id));
const strengthLabel = document.getElementById('strengthLabel');
const matchLabel    = document.getElementById('matchLabel');

const colors = { 1: '#ef4444', 2: '#f97316', 3: '#eab308', 4: '#16a34a' };
const labels = { 1: 'Weak',    2: 'Fair',    3: 'Good',    4: 'Strong'  };

passwordInput.addEventListener('input', function () {
    const val        = this.value;
    const hasLength  = val.length >= 8;
    const hasUpper   = /[A-Z]/.test(val);
    const hasNumber  = /[0-9]/.test(val);
    const hasSpecial = /[\W]/.test(val);

    document.getElementById('rule-length') .classList.toggle('valid', hasLength);
    document.getElementById('rule-upper')  .classList.toggle('valid', hasUpper);
    document.getElementById('rule-number') .classList.toggle('valid', hasNumber);
    document.getElementById('rule-special').classList.toggle('valid', hasSpecial);

    const score = [hasLength, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
    bars.forEach((bar, i) => bar.style.background = i < score ? colors[score] : '#e2e8f0');

    strengthLabel.textContent = val.length ? labels[score] : '';
    strengthLabel.style.color = colors[score] || '#94a3b8';

    checkMatch();
});

confirmInput.addEventListener('input', checkMatch);

function checkMatch() {
    const p = passwordInput.value;
    const c = confirmInput.value;
    if (!c) { matchLabel.textContent = ''; return; }
    matchLabel.textContent = p === c ? '✓ Passwords match'      : '✗ Passwords do not match';
    matchLabel.style.color = p === c ? '#16a34a'                : '#ef4444';
}
</script>
</body>
</html>

