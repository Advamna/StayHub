<?php
// ============================================================
// StayHub — Database Configuration
// ============================================================
// SETUP INSTRUCTIONS:
//   1. Copy this file and rename it config.php
//   2. Fill in your actual SQL Server credentials below
//   3. Make sure config.php is listed in .gitignore
//      so your password is never pushed to GitHub
// ============================================================

sqlsrv_configure("WarningsReturnAsErrors", 0);

$serverName = "YOUR_SERVER_NAME\\SQLEXPRESS"; // e.g. DESKTOP-XXXXXX\SQLEXPRESS

$connectionInfo = array(
    "Database"               => "stayhub",
    "UID"                    => "stayhub_user",       // SQL Server login name
    "PWD"                    => "YOUR_PASSWORD_HERE", // SQL Server login password
    "TrustServerCertificate" => true,
    "CharacterSet"           => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    // Log privately — never expose DB details to the browser
    error_log('StayHub DB connection failed: ' . print_r(sqlsrv_errors(), true));
    http_response_code(500);
    die("A server error occurred. Please try again later.");
}
