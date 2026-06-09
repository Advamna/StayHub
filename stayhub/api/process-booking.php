<?php
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
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    error_log('StayHub CSRF mismatch on process-booking');
    header("Location: ../listing.php?id=$listing_id&error=csrf");
    exit;
}

if (!$listing_id) {
    header('Location: ../index.php');
    exit;
}

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

// ── Fetch listing to validate guest count & price ──
$listingSql  = "SELECT price, voyageur_count FROM listings WHERE id = ?";
$listingStmt = sqlsrv_query($conn, $listingSql, array($listing_id));
if (!$listingStmt) {
    error_log('StayHub process-booking listing fetch error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
    exit;
}
$listingRow = sqlsrv_fetch_array($listingStmt, SQLSRV_FETCH_ASSOC);
if (!$listingRow) {
    header('Location: ../index.php');
    exit;
}

// ── Validate guest count against listing capacity ──
$max_guests = (int)$listingRow['voyageur_count'];
if ($guests_req < 1) $guests_req = 1;
if ($max_guests > 0 && $guests_req > $max_guests) {
    header("Location: ../listing.php?id=$listing_id&error=too_many_guests");
    exit;
}

// ── Availability check ──
$checkSql  = "SELECT COUNT(*) AS cnt FROM reservations 
              WHERE listing_id = ? 
              AND (CAST(? AS DATE) < check_out AND CAST(? AS DATE) > check_in)
              AND status != 'cancelled'";
$checkStmt = sqlsrv_query($conn, $checkSql, array($listing_id, $check_in, $check_out));
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

// ── Calculate total price server-side (never trust client) ──
$daily_price = (float)$listingRow['price'];
$days        = $d1->diff($d2)->days;
if ($days < 1) $days = 1;
$total_price = $daily_price * $days;

// ── Insert reservation ──
$insertSql = "INSERT INTO reservations 
              (listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', GETDATE())";
$params = array($listing_id, $user_id, $name, $email, $phone, $check_in, $check_out, $guests_req, $total_price);
$stmt   = sqlsrv_query($conn, $insertSql, $params);

if ($stmt) {
    header("Location: ../my-rentals.php?success=booked");
} else {
    error_log('StayHub insert reservation error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
}
exit;
