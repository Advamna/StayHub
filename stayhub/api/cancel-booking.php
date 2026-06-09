<?php
// ── api/cancel-booking.php (with Feature 10: email notifications) ──
session_start();
require_once '../config.php';

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
$checkSql  = "SELECT r.id, r.status, r.guest_name, r.guest_email, r.check_in, r.check_out,
                     l.title AS listing_title, l.user_id AS host_user_id,
                     u.email AS host_email, u.name AS host_name
              FROM reservations r
              JOIN listings l ON r.listing_id = l.id
              JOIN users u    ON l.user_id = u.id
              WHERE r.id = ? AND r.user_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$reservation_id, $user_id]);

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
$updateStmt = sqlsrv_query($conn, $updateSql, [$reservation_id, $user_id]);

if (!$updateStmt) {
    header('Location: ../my-rentals.php?error=db');
    exit;
}

// ── Feature 10: Email notifications on cancellation ───────
$guestName  = $row['guest_name'];
$guestEmail = $row['guest_email'];
$hostEmail  = $row['host_email'];
$hostName   = $row['host_name'];
$title      = $row['listing_title'];
$ciStr      = ($row['check_in']  instanceof DateTime ? $row['check_in']->format('d M Y')  : date('d M Y', strtotime($row['check_in'])));
$coStr      = ($row['check_out'] instanceof DateTime ? $row['check_out']->format('d M Y') : date('d M Y', strtotime($row['check_out'])));
$siteUrl    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));

// Guest cancellation confirmation
$guestSubj = "Booking Cancelled — $title | StayHub";
$guestBody = "Hi $guestName,\n\n"
    . "Your booking has been cancelled:\n\n"
    . "  Property : $title\n"
    . "  Check-in : $ciStr\n"
    . "  Check-out: $coStr\n\n"
    . "Browse other stays: $siteUrl\n\n— StayHub Team";
@mail($guestEmail, $guestSubj, $guestBody, "From: noreply@stayhub.ma\r\nContent-Type: text/plain; charset=UTF-8");

// Host cancellation alert
$hostSubj = "Booking Cancelled — $title | StayHub";
$hostBody = "Hi $hostName,\n\n"
    . "A booking for \"$title\" has been cancelled by the guest:\n\n"
    . "  Guest    : $guestName\n"
    . "  Check-in : $ciStr\n"
    . "  Check-out: $coStr\n\n"
    . "These dates are now available again.\n"
    . "Manage your listings: $siteUrl/my-listings.php\n\n— StayHub";
@mail($hostEmail, $hostSubj, $hostBody, "From: noreply@stayhub.ma\r\nContent-Type: text/plain; charset=UTF-8");

// DB notification for host
$notifSql = "INSERT INTO notifications (user_id, title, message, is_read, created_at)
             VALUES (?, ?, ?, 0, GETDATE())";
$notifMsg = "Booking cancelled by $guestName for \"$title\" ($ciStr → $coStr).";
sqlsrv_query($conn, $notifSql, [(int)$row['host_user_id'], 'Booking Cancelled', $notifMsg]);

header('Location: ../my-rentals.php?success=cancelled');
exit;
