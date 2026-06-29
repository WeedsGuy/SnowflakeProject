<?php  include __DIR__.'/../layout/header.php'; ?>
<?php  include __DIR__.'/../layout/sidebar.php'; ?>

<div class="main">
<style>
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: #fff;
    width: 400px;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group input {
    width: 100%;
    padding: 8px;
}

.modal-actions {
    text-align: right;
}

.modal-actions button {
    padding: 6px 12px;
    margin-left: 5px;
}
</style>

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Add User</h3>

        <input type="hidden" id="user_name_edit">

        <div class="form-group">
            <label>User Name</label>
            <input type="text" id="user_name">
            <input type="hidden" id="old_user">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" id="email">
            <input type="hidden" id="old_email">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" id="password">
        </div>
        
       <div class="form-group">
            <label>Your Password</label>
            <input type="password" id="your_password">
        </div>
        <div class="modal-actions">
            <button onclick="saveUser()">Save</button>
            <button onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>


    <div class="topbar">
        <h2>Admin Users</h2>
        <div class="admin-info">
            Admin User
        </div>
    </div>

    <button onclick="openAddModal()">+ Add User</button>

    <table border="1" width="100%" cellpadding="10">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Email</th>
                <th>Password</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="adminTable"></tbody>
    </table>
</div>

<script>
function loadUsers() {
    fetch("admin_users.php")
    .then(res => res.json())
    .then(res => {

        let rows = "";

        if (!res.success || !res.data || res.data.length === 0) {
            rows = `<tr><td colspan="4">No records found</td></tr>`;
        } else {

            const currentUser      = "<?= $_SESSION['admin_user'] ?? '' ?>";
            const currentSuperUser = "<?= $_SESSION['super_user'] ?? false ?>";

            res.data.forEach(user => {

                let btnChange = '';
                let btndelete = '';

                const isTargetSuper = !!user.super_user;
                const isLoggedSuper = !!currentSuperUser;

                /* 🔐 CHANGE PASSWORD BUTTON */
                if (!isTargetSuper || isLoggedSuper) {
                    btnChange = `
                        <button onclick="editUser('${user.user_name}')">
                            Change Password
                        </button>
                    `;
                }

                /* 🗑 DELETE BUTTON */
                if (!isTargetSuper && user.user_name !== currentUser) {
                    btndelete = `
                        <button onclick="deleteUser('${user.user_name}','${user.email}')">
                            Delete
                        </button>
                    `;
                }

                rows += `
                    <tr>
                        <td>${escapeHtml(user.user_name)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>********</td>
                        <td>
                            ${btnChange}
                            ${btndelete}
                        </td>
                    </tr>
                `;
            });
        }


        document.getElementById("adminTable").innerHTML = rows;
    });
}

function openAddModal() {
    document.getElementById("modalTitle").innerText = "Add User";

    document.getElementById("user_name").value = "";
    document.getElementById("email").value = "";
    document.getElementById("password").value = "";
    document.getElementById("your_password").value = "";

    document.getElementById("user_name").disabled = false;
    document.getElementById("email").disabled = false;

    document.getElementById("user_name_edit").value = "";

    document.getElementById("userModal").style.display = "block";
}

function closeModal() {
    document.getElementById("userModal").style.display = "none";
}

function editUser(user_name) {
    fetch("get_single_admin.php?user_name=" + encodeURIComponent(user_name))
    .then(res => res.json())
    .then(res => {
        if (res.success) {

            document.getElementById("modalTitle").innerText = "Change Password";

            document.getElementById("user_name").value = res.data.user_name;
            document.getElementById("email").value = res.data.email;
            document.getElementById("old_user").value = res.data.user_name;
            document.getElementById("old_email").value = res.data.email;

            // 🔒 Disable editing
            document.getElementById("user_name").disabled = true;
            document.getElementById("email").disabled = true;

            document.getElementById("password").value = "";
            document.getElementById("your_password").value = "";

            document.getElementById("user_name_edit").value = res.data.user_name;

            document.getElementById("userModal").style.display = "block";
        }
    });
}

function saveUser() {

    const user_name_edit = document.getElementById("user_name_edit").value;
    const user_name      = document.getElementById("user_name").value.trim();
    const oldUser        = document.getElementById("old_user").value.trim();
    const oldEmail       = document.getElementById("old_email").value.trim();
    const email          = document.getElementById("email").value.trim();
    const password       = document.getElementById("password").value.trim();
    const your_password  = document.getElementById("your_password").value.trim();

    // 🚀 EDIT MODE → Change Password Only
    if (user_name_edit) {

        if (!password || !your_password) {
            alert("Please enter new password and your current password");
            return;
        }

    } else {
        // ADD MODE
        if (!user_name || !email || !password) {
            alert("All fields are required");
            return;
        }
    }

    const url = user_name_edit ? "update_admin.php" : "save_admin.php";

    fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            user_name,
            oldUser,
            oldEmail,
            email,
            password,
            your_password
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeModal();
            loadUsers();
        } else {
            alert(res.message || "Error");
        }
    });
}

function deleteUser(email,user_name) {
    if (!confirm("Are you sure you want to delete this user?")) return;

    fetch("delete_admin.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email,user_name })
    }).then(() => loadUsers());
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

loadUsers();
</script>

<?php  include __DIR__.'/../layout/footer.php'; ?>