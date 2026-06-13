<?php
require_once __DIR__ . '/../config.php';

$email = 'adamnaime200@gmail.com';
$password = 'adamnaime@2006';

$sql  = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";
$stmt = sqlsrv_query($conn, $sql, array($email));

if ($stmt === false) {
    echo "QUERY FAILED: " . print_r(sqlsrv_errors(), true);
    exit;
}

$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$user) {
    echo "USER NOT FOUND in DB for email: $email<br>";
    // List all users
    $all = sqlsrv_query($conn, "SELECT id, name, email, LEFT(password,10) as pw_prefix FROM users");
    echo "<br>All users in DB:<br>";
    while ($u = sqlsrv_fetch_array($all, SQLSRV_FETCH_ASSOC)) {
        echo "- ID: {$u['id']} | {$u['name']} | {$u['email']} | hash_prefix: {$u['pw_prefix']}<br>";
    }
} else {
    echo "USER FOUND: {$user['name']} ({$user['email']})<br>";
    echo "Hash prefix: " . substr($user['password'], 0, 7) . "<br>";
    $verify = password_verify($password, $user['password']);
    echo "password_verify result: " . ($verify ? "TRUE ✅" : "FALSE ❌") . "<br>";
}
?>
