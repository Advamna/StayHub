<?php
// ── api/process-booking.php (with Feature 10: email notifications) ──
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

// ── Auth check ──
if (!isset($_SESSION['user_id'])) {
    header("Location: ../listing.php?id=$listing_id&error=not_logged_in");
    exit;
}

// ── CSRF check ──
$postCsrf = $_POST['csrf_token'] ?? '';
$sessCsrf = $_SESSION['csrf_token'] ?? '';
if ($sessCsrf && $postCsrf !== $sessCsrf) {
    error_log('StayHub CSRF mismatch on process-booking — post:' . $postCsrf . ' sess:' . $sessCsrf);
    header("Location: ../listing.php?id=$listing_id&error=csrf");
    exit;
}

if (!$listing_id) { header('Location: ../index.php'); exit; }

$user_id    = (int)$_SESSION['user_id'];
$name       = trim($_POST['guest_name']  ?? '');
$email      = trim($_POST['guest_email'] ?? '');
$phone      = trim($_POST['guest_phone'] ?? '');
$check_in   = trim($_POST['check_in']    ?? '');
$check_out  = trim($_POST['check_out']   ?? '');
$guests_req = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;

// ── Validate required fields ──
if (!$name || !$email || !$phone || !$check_in || !$check_out) {
    header("Location: ../listing.php?id=$listing_id&error=missing_fields");
    exit;
}

// ── Validate date format ──
$d1 = DateTime::createFromFormat('Y-m-d', $check_in);
$d2 = DateTime::createFromFormat('Y-m-d', $check_out);
if (!$d1 || !$d2 || $d2 <= $d1) {
    header("Location: ../listing.php?id=$listing_id&error=invalid_dates");
    exit;
}

// ── Fetch listing + host email ──
$listingSql  = "SELECT l.price, l.max_guests, l.title, l.location, u.email AS HostEmail, u.name AS HostName
                FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?";
$listingStmt = sqlsrv_query($conn, $listingSql, [$listing_id]);
if (!$listingStmt) {
    error_log('StayHub process-booking listing fetch error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
    exit;
}
$listingRow = sqlsrv_fetch_array($listingStmt, SQLSRV_FETCH_ASSOC);
if (!$listingRow) { header('Location: ../index.php'); exit; }

// ── Validate guest count ──
$max_guests = (int)$listingRow['max_guests'];
if ($guests_req < 1) $guests_req = 1;
if ($max_guests > 0 && $guests_req > $max_guests) {
    header("Location: ../listing.php?id=$listing_id&error=too_many_guests");
    exit;
}

// ── Availability check ──
// ── Addon 3: Auto-cancel expired holds before checking availability ──
$expireSql = "UPDATE reservations SET status='cancelled'
              WHERE status='pending' AND expires_at IS NOT NULL AND expires_at < GETDATE()";
sqlsrv_query($conn, $expireSql);

$checkSql  = "SELECT COUNT(*) AS cnt FROM reservations
              WHERE listing_id = ?
              AND (CAST(? AS DATE) < check_out AND CAST(? AS DATE) > check_in)
              AND status != 'cancelled'";
$checkStmt = sqlsrv_query($conn, $checkSql, [$listing_id, $check_in, $check_out]);
if (!$checkStmt) {
    error_log('StayHub availability check error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
    exit;
}
$checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
if ((int)$checkRow['cnt'] > 0) {
    header("Location: ../listing.php?id=$listing_id&error=already_booked");
    exit;
}

// ── Server-side price calculation ──
$daily_price = (float)$listingRow['price'];
$days        = $d1->diff($d2)->days;
if ($days < 1) $days = 1;
$total_price = $daily_price * $days;

// ── Insert reservation ──
// Addon 3: set 48-hour payment deadline
$insertSql = "INSERT INTO reservations
              (listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, created_at, expires_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', GETDATE(), DATEADD(HOUR, 48, GETDATE()))";
$params    = [$listing_id, $user_id, $name, $email, $phone, $check_in, $check_out, $guests_req, $total_price];
$stmt      = sqlsrv_query($conn, $insertSql, $params);

if (!$stmt) {
    error_log('StayHub insert reservation error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
    exit;
}

// Get new reservation id
$resId = 0;
$idStmt = sqlsrv_query($conn, "SELECT @@IDENTITY AS id");
if ($idStmt && $idRow = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC)) {
    $resId = (int)$idRow['id'];
}

// ── Feature 10: Send email notifications ──────────────────
$listingTitle = $listingRow['title'];
$listingLoc   = $listingRow['location'];
$hostEmail    = $listingRow['HostEmail'];
$hostName     = $listingRow['HostName'];
$siteUrl      = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
$ciStr        = $d1->format('D, d M Y');
$coStr        = $d2->format('D, d M Y');

// Send guest confirmation email
$guestSubject = "Booking Confirmed — $listingTitle | StayHub";
$guestBody    = "Hi $name,\n\n"
    . "Your booking at StayHub is confirmed! Here are your details:\n\n"
    . "  Property : $listingTitle\n"
    . "  Location : $listingLoc\n"
    . "  Check-in : $ciStr\n"
    . "  Check-out: $coStr\n"
    . "  Duration : $days night" . ($days > 1 ? 's' : '') . "\n"
    . "  Guests   : $guests_req\n"
    . "  Total    : " . number_format($total_price, 0) . " MAD\n\n"
    . "View your stay: $siteUrl/my-rentals.php\n\n"
    . "See you soon!\n— The StayHub Team";

$headers_guest = "From: noreply@stayhub.ma\r\nContent-Type: text/plain; charset=UTF-8";
@mail($email, $guestSubject, $guestBody, $headers_guest);

// Send host notification email
$hostSubject = "New Booking — $listingTitle | StayHub";
$hostBody    = "Hi $hostName,\n\n"
    . "Great news! You have a new booking:\n\n"
    . "  Guest    : $name\n"
    . "  Email    : $email\n"
    . "  Phone    : $phone\n"
    . "  Check-in : $ciStr\n"
    . "  Check-out: $coStr\n"
    . "  Guests   : $guests_req\n"
    . "  Revenue  : " . number_format($total_price, 0) . " MAD\n\n"
    . "View in your dashboard: $siteUrl/my-listings.php\n\n"
    . "— StayHub";

$headers_host = "From: noreply@stayhub.ma\r\nContent-Type: text/plain; charset=UTF-8";
@mail($hostEmail, $hostSubject, $hostBody, $headers_host);

// Also insert a DB notification for the host
$hostIdSql  = "SELECT user_id FROM listings WHERE id = ?";
$hostIdStmt = sqlsrv_query($conn, $hostIdSql, [$listing_id]);
if ($hostIdStmt && $hRow = sqlsrv_fetch_array($hostIdStmt, SQLSRV_FETCH_ASSOC)) {
    $notifSql = "INSERT INTO notifications (user_id, title, message, is_read, created_at)
                 VALUES (?, ?, ?, 0, GETDATE())";
    $notifMsg = "New booking from $name for \"$listingTitle\" ($ciStr → $coStr).";
    sqlsrv_query($conn, $notifSql, [(int)$hRow['user_id'], 'New Booking', $notifMsg]);
}

header("Location: ../payment.php?id=" . $resId);
exit;
