<?php 

function sysadmin($snowflake){
    odbc_exec($snowflake, "USE ROLE SYSADMIN");
    odbc_exec($snowflake, "USE WAREHOUSE WORKWAVE_APP_WH");
    odbc_exec($snowflake, "USE DATABASE WEEDEXDEV");
    odbc_exec($snowflake, "USE SCHEMA PUBLIC");
}

function datafactory($snowflake){
    odbc_exec($snowflake, "USE ROLE WORKWAVE_DATAFACTORY_ROLE");
    odbc_exec($snowflake, "USE WAREHOUSE WORKWAVE_DATAFACTORY_WH");
    odbc_exec($snowflake, "USE DATABASE WORKWAVE_DATAFACTORY_DB");
    odbc_exec($snowflake, "USE SCHEMA WAREHOUSE");
}