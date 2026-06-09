<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listing_id = $_POST['listing_id'];
    
    // Ensure the user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../listing.php?id=$listing_id&error=not_logged_in");
        exit();
    }
    
    $user_id    = $_SESSION['user_id'];
    $name       = $_POST['guest_name'];
    $email      = $_POST['guest_email'];
    $phone      = $_POST['guest_phone'];
    $check_in   = $_POST['check_in'];
    $check_out  = $_POST['check_out'];

    // 1. Availability Check - Added CAST to ensure DATE compatibility
    $checkSql = "SELECT COUNT(*) as count FROM reservations 
                 WHERE listing_id = ? 
                 AND (CAST(? AS DATE) < check_out AND CAST(? AS DATE) > check_in)
                 AND status != 'cancelled'";
    
    $checkStmt = sqlsrv_query($conn, $checkSql, array($listing_id, $check_in, $check_out));

    // SAFETY CATCH: If the query fails, show the actual SQL error
    if ($checkStmt === false) {
        die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
    }

    $checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

    if ($checkRow['count'] > 0) {
        header("Location: ../listing.php?id=$listing_id&error=already_booked");
        exit();
    }

    // 2. Fetch price and calculate stay duration
    $price_sql = "SELECT price FROM listings WHERE id = ?";
    $price_stmt = sqlsrv_query($conn, $price_sql, array($listing_id));
    $price_row = sqlsrv_fetch_array($price_stmt, SQLSRV_FETCH_ASSOC);
    
    $daily_price = $price_row['price'];

    // Calculate total price based on days
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $interval = $date1->diff($date2);
    $days = $interval->days > 0 ? $interval->days : 1;
    $total_price = $daily_price * $days;

    // 3. Insert Reservation (Matches your CREATE TABLE columns exactly)
    $insertSql = "INSERT INTO reservations (listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'pending', GETDATE())";
    
    $params = array($listing_id, $user_id, $name, $email, $phone, $check_in, $check_out, $total_price);
    $stmt = sqlsrv_query($conn, $insertSql, $params);

    if ($stmt) {
        header("Location: ../my-rentals.php");
    } else {
        // SAFETY CATCH: Show error if the insert fails
        die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
    }
}