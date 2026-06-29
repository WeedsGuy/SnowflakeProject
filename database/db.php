<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '-1');

$username = "DEV.VIRTUALOPLOSSING";

$snowflake = odbc_connect("SnowflakeProd", $username, "");

if (!$snowflake) {
    die("Connection failed: " . odbc_errormsg());
}


