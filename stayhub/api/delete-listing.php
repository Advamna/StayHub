<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// FIX #2: Use POST instead of GET to prevent deletion via malicious links
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../my-listings.php');
    exit;
}

// FIX #2: Validate & cast listing_id from POST
$listing_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id    = $_SESSION['user_id'];

if ($listing_id > 0) {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: ../my-listings.php?error=invalid_request');
        exit;
    }

    $sql    = "DELETE FROM listings WHERE id = ? AND user_id = ?";
    $params = array($listing_id, $user_id);
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header('Location: ../my-listings.php?deleted=1');
    } else {
        // FIX #4: Log error privately, don't expose to browser
        error_log('StayHub delete-listing error: ' . print_r(sqlsrv_errors(), true));
        header('Location: ../my-listings.php?error=server');
    }
} else {
    header('Location: ../my-listings.php');
}
exit;
?>
