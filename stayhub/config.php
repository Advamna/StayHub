<?php
// ============================================================
// StayHub -- Database Configuration
// SQL Server / SQLSRV driver
// ============================================================

sqlsrv_configure("WarningsReturnAsErrors", 0);

$serverName = "localhost\SQLEXPRESS";

$connectionInfo = array(
    "Database"               => "new_stayhub",
    "UID"                    => "stayhub_user",
    "PWD"                    => "Adamnaime@2006",
    "TrustServerCertificate" => true,
    "CharacterSet"           => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    error_log('StayHub DB connection failed: ' . print_r(sqlsrv_errors(), true));
    http_response_code(500);
    die("A server error occurred. Please try again later.");
}
