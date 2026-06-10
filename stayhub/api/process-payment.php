<?php
// ── api/process-payment.php ────────────────────────────────────────────
// Full pipeline: validate → confirm reservation → insert payment → insert invoice → redirect to receipt
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../my-rentals.php?error=csrf');
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$card_name      = trim($_POST['card_name']   ?? '');
$card_number    = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
$card_exp       = trim($_POST['card_exp']    ?? '');
$card_cvv       = trim($_POST['card_cvv']    ?? '');

if (!$reservation_id) {
    header('Location: ../my-rentals.php?error=invalid');
    exit;
}

// ── Server-side card validation ───────────────────────────────────────
$errors = [];
if (!preg_match('/^[a-zA-Z\s]+$/', $card_name) || strlen($card_name) < 2) {
    $errors[] = 'Invalid cardholder name';
}
if (strlen($card_number) !== 16 || !ctype_digit($card_number)) {
    $errors[] = 'Card number must be 16 digits';
}
if (!preg_match('/^\d{2}\/\d{2}$/', $card_exp)) {
    $errors[] = 'Expiry must be MM/YY';
} else {
    [$mm, $yy] = explode('/', $card_exp);
    $expYear  = 2000 + (int)$yy;
    $expMonth = (int)$mm;
    if ($expMonth < 1 || $expMonth > 12 || $expYear < (int)date('Y') ||
        ($expYear === (int)date('Y') && $expMonth < (int)date('n'))) {
        $errors[] = 'Card has expired';
    }
}
if (!preg_match('/^\d{3}$/', $card_cvv)) {
    $errors[] = 'CVV must be 3 digits';
}
if ($errors) {
    $msg = urlencode(implode(', ', $errors));
    header("Location: ../payment.php?id=$reservation_id&error=$msg");
    exit;
}

// ── Fetch reservation (must be pending and belong to this user) ───────
$resSql  = "SELECT r.*, l.price AS nightly_price, l.title, l.location
            FROM reservations r
            JOIN listings l ON r.listing_id = l.id
            WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'";
$resStmt = sqlsrv_query($conn, $resSql, [$reservation_id, $user_id]);
if (!$resStmt) {
    error_log('process-payment fetch error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../payment.php?id=$reservation_id&error=server");
    exit;
}
$res = sqlsrv_fetch_array($resStmt, SQLSRV_FETCH_ASSOC);
if (!$res) {
    header('Location: ../my-rentals.php?error=invalid');
    exit;
}

$total_amount   = (float)$res['total_price'];
$cleaning_fee   = 150.00;
$service_fee    = round($total_amount * 0.10, 2);
$tax_rate       = 0.20;  // 20% VAT
$tax_amount     = round($total_amount * $tax_rate, 2);

// Determine payment method label from card number prefix (BIN detection)
$bin = substr($card_number, 0, 1);
$payment_method = match($bin) {
    '4'     => 'Visa',
    '5'     => 'Mastercard',
    '3'     => 'Amex',
    default => 'Card'
};
// Mask card number for storage — keep only last 4 digits
$card_last4       = substr($card_number, -4);
$payment_method_label = $payment_method . ' ending in ' . $card_last4;

// ── BEGIN: confirm reservation ────────────────────────────────────────
$updateSql  = "UPDATE reservations SET status = 'confirmed' WHERE id = ? AND user_id = ? AND status = 'pending'";
$updateStmt = sqlsrv_query($conn, $updateSql, [$reservation_id, $user_id]);
if (!$updateStmt || sqlsrv_rows_affected($updateStmt) === 0) {
    error_log('process-payment confirm error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../payment.php?id=$reservation_id&error=server");
    exit;
}

// ── INSERT into payments ──────────────────────────────────────────────
$paySql  = "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, created_at)
            VALUES (?, ?, ?, 'completed', GETDATE())";
$payStmt = sqlsrv_query($conn, $paySql, [$reservation_id, $total_amount, $payment_method_label]);
if (!$payStmt) {
    error_log('process-payment insert payment error: ' . print_r(sqlsrv_errors(), true));
    // Reservation is already confirmed — still redirect to receipt, just log the error
    header("Location: ../receipt.php?id=$reservation_id&warn=payment_log");
    exit;
}

// Get the new payment ID
$payment_id = 0;
$idStmt     = sqlsrv_query($conn, "SELECT @@IDENTITY AS new_id");
if ($idStmt && $idRow = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC)) {
    $payment_id = (int)$idRow['new_id'];
}

// ── INSERT into invoices ──────────────────────────────────────────────
if ($payment_id > 0) {
    // Generate unique invoice number: INV-YYYY-{reservation_id}-{payment_id}
    $invoice_number = 'INV-' . date('Y') . '-' . str_pad($reservation_id, 5, '0', STR_PAD_LEFT) . '-' . str_pad($payment_id, 4, '0', STR_PAD_LEFT);

    $invSql  = "INSERT INTO invoices (payment_id, invoice_number, tax_amount, total_amount, issued_at)
                VALUES (?, ?, ?, ?, GETDATE())";
    $invStmt = sqlsrv_query($conn, $invSql, [$payment_id, $invoice_number, $tax_amount, $total_amount]);
    if (!$invStmt) {
        error_log('process-payment insert invoice error: ' . print_r(sqlsrv_errors(), true));
    }
}

// ── Notify host via DB notification ──────────────────────────────────
$hostSql  = "SELECT l.user_id, l.title FROM listings l JOIN reservations r ON l.id = r.listing_id WHERE r.id = ?";
$hostStmt = sqlsrv_query($conn, $hostSql, [$reservation_id]);
if ($hostStmt && $hostRow = sqlsrv_fetch_array($hostStmt, SQLSRV_FETCH_ASSOC)) {
    $notifSql = "INSERT INTO notifications (user_id, title, message, is_read, created_at)
                 VALUES (?, ?, ?, 0, GETDATE())";
    $notifMsg = "Payment of " . number_format($total_amount, 0) . " MAD received for \"" . $hostRow['title'] . "\" via " . $payment_method_label . ".";
    sqlsrv_query($conn, $notifSql, [(int)$hostRow['user_id'], 'Payment Received', $notifMsg]);
}

header("Location: ../receipt.php?id=$reservation_id");
exit;
