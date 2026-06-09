<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

// ── Auth check: must be logged in ──
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to make a reservation.',
        'redirect' => '../index.php'
    ]);
    exit;
}

// ── Only accept POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id      = (int)$_SESSION['user_id'];
$listing_id   = isset($_POST['listing_id'])   ? (int)$_POST['listing_id']   : 0;
$guest_name   = $_POST['guest_name']   ?? '';
$guest_email  = $_POST['guest_email']  ?? '';
$guest_phone  = $_POST['guest_phone']  ?? '';
$check_in     = $_POST['check_in']     ?? null;
$check_out    = $_POST['check_out']    ?? null;
$guests_count = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;
$total_price  = isset($_POST['total_price']) ? (float)$_POST['total_price'] : 0;

// ── Validate required fields ──
if (!$listing_id || !$guest_name || !$guest_email || !$guest_phone || !$check_in || !$check_out) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// ── Insert with user_id so the reservation is owned by the logged-in user ──
$sql = "INSERT INTO reservations (
            user_id,
            listing_id,
            guest_name,
            guest_email,
            guest_phone,
            check_in,
            check_out,
            guests,
            total_price,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$params = array(
    $user_id,
    $listing_id,
    $guest_name,
    $guest_email,
    $guest_phone,
    $check_in,
    $check_out,
    $guests_count,
    $total_price
);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo json_encode(['success' => true]);
} else {
    // Log privately — never expose DB errors to the client
    error_log('StayHub make-reservation error: ' . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
}
?>
