<?php
session_start();
require_once '../config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id        = $_SESSION['user_id'];
$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;

if ($reservation_id <= 0) {
    header('Location: ../my-rentals.php?error=invalid');
    exit;
}

// Verify this reservation belongs to the logged-in user
$checkSql  = "SELECT id, status FROM reservations WHERE id = ? AND user_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, array($reservation_id, $user_id));

if ($checkStmt === false) {
    header('Location: ../my-rentals.php?error=db');
    exit;
}

$row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    header('Location: ../my-rentals.php?error=notfound');
    exit;
}

if ($row['status'] === 'cancelled') {
    header('Location: ../my-rentals.php?error=already_cancelled');
    exit;
}

// Perform the cancellation
$updateSql  = "UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?";
$updateStmt = sqlsrv_query($conn, $updateSql, array($reservation_id, $user_id));

if ($updateStmt) {
    header('Location: ../my-rentals.php?success=cancelled');
} else {
    header('Location: ../my-rentals.php?error=db');
}
exit;
?>
