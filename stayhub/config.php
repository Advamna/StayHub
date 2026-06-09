<?php
// Tell sqlsrv to NOT treat informational warnings as fatal errors
sqlsrv_configure("WarningsReturnAsErrors", 0);

$serverName = "DESKTOP-9LJFEUO\\SQLEXPRESS";
$connectionInfo = array(
    "Database"               => "stayhub",
    "TrustServerCertificate" => true,
    "CharacterSet"           => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    // FIX #4: Log the real error privately — never expose DB details to the browser
    error_log('StayHub DB connection failed: ' . print_r(sqlsrv_errors(), true));
    http_response_code(500);
    die("A server error occurred. Please try again later.");
}
