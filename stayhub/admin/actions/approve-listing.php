<?php
session_start();
require_once '../guard.php';
require_once '../../config.php';

if (!isset($_GET['id'])) {
    header('Location: ../listings.php?done=error');
    exit;
}

$listing_id = (int)$_GET['id'];

// Get the listing details to know the host and title
$sql_listing = "SELECT user_id, title FROM listings WHERE id = ?";
$stmt_listing = sqlsrv_query($conn, $sql_listing, [$listing_id]);
$listing = sqlsrv_fetch_array($stmt_listing, SQLSRV_FETCH_ASSOC);

if (!$listing) {
    header('Location: ../listings.php?done=error');
    exit;
}

// Update the status to 'active'
$sql_approve = "UPDATE listings SET status = 'active' WHERE id = ?";
$stmt_approve = sqlsrv_query($conn, $sql_approve, [$listing_id]);

if ($stmt_approve) {
    // Notify the host
    $host_id = $listing['user_id'];
    $title = $listing['title'];
    $message = "Good news! Your listing \"$title\" has been approved and is now active on StayHub.";
    $sql_notify = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    sqlsrv_query($conn, $sql_notify, [$host_id, $message]);
    
    header('Location: ../listings.php?done=approved');
    exit;
} else {
    header('Location: ../listings.php?done=error');
    exit;
}
?>
