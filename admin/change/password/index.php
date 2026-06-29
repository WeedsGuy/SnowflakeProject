<?php  include __DIR__.'/../../layout/header.php'; ?>
<?php  include __DIR__.'/../../layout/sidebar.php'; ?>

<div class="main">
 <div class="topbar">
        <h2>Change Password</h2>
        <div class="admin-info">
           <?= $_SESSION['admin_user'] ?? 'Admin' ?>
        </div>
    </div>


<style>
.password-container {
    max-width: 420px;
    margin: 60px auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    font-family: Arial, sans-serif;
}

.password-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    color: #555;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: 0.2s;
}

.form-group input:focus {
    border-color: #4CAF50;
    outline: none;
    box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #4CAF50;
    border: none;
    border-radius: 6px;
    color: white;
    font-size: 15px;
    cursor: pointer;
    transition: 0.2s;
}

.btn-primary:hover {
    background: #43a047;
}

.message {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.success {
    color: green;
}

.error {
    color: red;
}
</style>


<div class="password-container">
    <h2>Change Password</h2>

    <div class="form-group">
        <label>Current Password</label>
        <input type="password" id="current_password" placeholder="Enter current password">
    </div>

    <div class="form-group">
        <label>New Password</label>
        <input type="password" id="new_password" placeholder="Enter new password">
    </div>

    <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" id="confirm_password" placeholder="Confirm new password">
    </div>

    <button class="btn-primary" onclick="changePassword()">
        Update Password
    </button>

    <div id="responseMessage" class="message"></div>
</div>


</div>
<script>
function changePassword() {

    const current_password = document.getElementById("current_password").value.trim();
    const new_password     = document.getElementById("new_password").value.trim();
    const confirm_password = document.getElementById("confirm_password").value.trim();
    const messageBox       = document.getElementById("responseMessage");

    messageBox.innerHTML = "";
    messageBox.className = "message";

    if (!current_password || !new_password || !confirm_password) {
        messageBox.innerHTML = "All fields are required";
        messageBox.classList.add("error");
        return;
    }

    if (new_password.length < 6) {
        messageBox.innerHTML = "Password must be at least 6 characters";
        messageBox.classList.add("error");
        return;
    }

    if (new_password !== confirm_password) {
        messageBox.innerHTML = "New passwords do not match";
        messageBox.classList.add("error");
        return;
    }

    fetch("<?=$baseUrl?>/change/password/change_my_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            current_password,
            new_password
        })
    })
    .then(res => res.json())
    .then(res => {

        messageBox.innerHTML = res.message;

        if (res.success) {
            messageBox.classList.add("success");

            document.getElementById("current_password").value = "";
            document.getElementById("new_password").value = "";
            document.getElementById("confirm_password").value = "";
        } else {
            messageBox.classList.add("error");
        }
    })
    .catch(() => {
        messageBox.innerHTML = "Something went wrong";
        messageBox.classList.add("error");
    });
}
</script>

<?php  include __DIR__.'/../../layout/footer.php'; ?>