<?php
$currentFile = $_SERVER['PHP_SELF'];
?>

<aside class="sidebar">
    <h1>ADMIN</h1>
    <ul>
        <!-- Customers (admin/index.php) -->
        <li class="<?= (strpos($currentFile, '/admin/customers/index.php') !== false) ? 'active' : '' ?>">
            <a href="<?= $baseUrl ?>/customers">Customers</a>
        </li>

        <!-- Users (admin/users/) -->
        <li class="<?php (strpos($currentFile, '/admin/users/index.php') !== false) ? 'active' : '' ?>" style="display:none;">
            <a href="<?php $baseUrl ?>/users/">Admin Users</a>
        </li>
        <li class="<?= (strpos($currentFile, '/admin/customers/properties/index.php') !== false) ? 'active' : '' ?>" style="display:none;">
            <a href="<?php $baseUrl ?>/customers/properties/">Customer Properties</a>
        </li>
        <li class="<?= (strpos($currentFile, '/admin/customers/data/index.php') !== false) ? 'active' : '' ?>" style="display: none;">
            <a href="<?= $baseUrl ?>/customers/data/">Customers</a>
        </li>

        <li class="<?= (strpos($currentFile, '/admin/change/password/index.php') !== false) ? 'active' : '' ?>">
            <a href="<?= $baseUrl ?>/change/password/">Change Password</a>
        </li>

        <li class="logout">
            <a href="<?= $baseUrl ?>/logout.php">Logout</a>
        </li>
    </ul>
</aside>
