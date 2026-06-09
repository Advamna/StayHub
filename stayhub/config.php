<?php
// Tell sqlsrv to NOT treat informational warnings as fatal errors
sqlsrv_configure("WarningsReturnAsErrors", 0);

$serverName = "DESKTOP-9LJFEUO\\SQLEXPRESS"; 
$connectionInfo = array(
    "Database" => "stayhub",
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    echo "Connection Failure:<br>";
    die(print_r(sqlsrv_errors(), true));
}