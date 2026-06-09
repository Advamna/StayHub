<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $reservation_id = $_POST['reservation_id'] ?? null;

    if (!$reservation_id) {
        header('Location: ../my-rentals.php');
        exit;
    }

    // Verify reservation belongs to user and is pending
    $verifySql = "SELECT id FROM reservations WHERE id = ? AND user_id = ? AND status = 'pending'";
    $verifyStmt = sqlsrv_query($conn, $verifySql, array($reservation_id, $user_id));
    $reservation = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC);

    if (!$reservation) {
        // Either doesn't exist, not theirs, or already paid/cancelled
        header("Location: ../my-rentals.php?error=invalid");
        exit;
    }

    // Process Mock Payment (We just assume success)
    
    // Update status to confirmed
    $updateSql = "UPDATE reservations SET status = 'confirmed' WHERE id = ?";
    $updateStmt = sqlsrv_query($conn, $updateSql, array($reservation_id));

    if ($updateStmt) {
        header("Location: ../receipt.php?id=$reservation_id");
    } else {
        die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
    }
}
