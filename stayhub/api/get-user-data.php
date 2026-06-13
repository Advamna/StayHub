<?php
// ── api/get-user-data.php ─────────────────────────────────────────────────────
// Returns logged-in user's name, email, phone for pre-filling reservation form
// ─────────────────────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

require_once __DIR__ . '/../config.php';

$sql  = "SELECT name, email, phone FROM users WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [(int)$_SESSION['user_id']]);

if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    echo json_encode(['logged_in' => false]);
    exit;
}

echo json_encode([
    'logged_in' => true,
    'name'      => $row['name']  ?? '',
    'email'     => $row['email'] ?? '',
    'phone'     => $row['phone'] ?? '',
]);
exit;
