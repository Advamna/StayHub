<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id = (int)($_POST['listing_id'] ?? 0);

if ($id > 0) {
    // ON DELETE CASCADE should handle reservations, amenities, images
    $sql  = "DELETE FROM listings WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    header('Location: ../listings.php?done=' . ($stmt ? 'deleted' : 'error'));
} else {
    header('Location: ../listings.php?done=error');
}
exit;
