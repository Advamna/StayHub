<?php
session_start();
// FIX: Point to config in the parent folder
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['id'])) {
    $listing_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $sql = "DELETE FROM listings WHERE id = ? AND user_id = ?";
    $params = array($listing_id, $user_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        // FIX: Go back up to the dashboard
        header('Location: ../my-listings.php?deleted=1');
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
} else {
    header('Location: ../my-listings.php');
}
?>