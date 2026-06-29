<?php
session_start();
session_destroy();

$baseUrl =
    (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
    $_SERVER['HTTP_HOST'] .
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
header("Location: $baseUrl");
exit;


// header("Location: /weedex_git/admin/");
exit;
