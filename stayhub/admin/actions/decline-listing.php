<?php
session_start();
require_once '../guard.php';
require_once '../../config.php';

$id = (int)($_POST['listing_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($id > 0 && !empty($reason)) {
    // Get the listing details to know the host and title
    $sql_listing = "SELECT user_id, title FROM listings WHERE id = ?";
    $stmt_listing = sqlsrv_query($conn, $sql_listing, [$id]);
    $listing = sqlsrv_fetch_array($stmt_listing, SQLSRV_FETCH_ASSOC);

    if ($listing) {
        // Delete the listing
        $sql  = "DELETE FROM listings WHERE id = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if ($stmt) {
            // Notify the host
            $host_id = $listing['user_id'];
            $title = $listing['title'];
            $message = "Your listing \"$title\" has been declined and deleted by an administrator. Reason: $reason";
            $sql_notify = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            sqlsrv_query($conn, $sql_notify, [$host_id, $message]);
            
            header('Location: ../listings.php?done=declined');
            exit;
        }
    }
}

header('Location: ../listings.php?done=error');
exit;
?>
