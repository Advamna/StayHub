<?php
// ── api/submit-review.php ─────────────────────────────────
// Feature 8: Guest submits a review after their stay
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../my-rentals.php?error=csrf'); exit;
}

$user_id       = (int)$_SESSION['user_id'];
$listing_id    = (int)($_POST['listing_id'] ?? 0);
$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$rating        = (int)($_POST['rating'] ?? 0);
$comment       = trim($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5 || !$listing_id || !$reservation_id) {
    header('Location: ../my-rentals.php?error=invalid_review'); exit;
}

// Verify the reservation belongs to this user and is confirmed/completed
$checkSql  = "SELECT id FROM reservations WHERE id = ? AND user_id = ? AND status IN ('confirmed','pending')";
$checkStmt = sqlsrv_query($conn, $checkSql, [$reservation_id, $user_id]);
if (!$checkStmt || !sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
    header('Location: ../my-rentals.php?error=not_eligible'); exit;
}

// Check not already reviewed
$dupSql  = "SELECT id FROM reviews WHERE reservation_id = ? AND user_id = ?";
$dupStmt = sqlsrv_query($conn, $dupSql, [$reservation_id, $user_id]);
if ($dupStmt && sqlsrv_fetch_array($dupStmt, SQLSRV_FETCH_ASSOC)) {
    header("Location: ../listing.php?id=$listing_id&error=already_reviewed"); exit;
}

$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

// Insert review
$sql  = "INSERT INTO reviews (listing_id, user_id, reservation_id, rating, comment, created_at)
         VALUES (?, ?, ?, ?, ?, GETDATE())";
$stmt = sqlsrv_query($conn, $sql, [$listing_id, $user_id, $reservation_id, $rating, $comment]);

if ($stmt) {
    // Recalculate average rating on the listing
    $avgSql  = "SELECT AVG(CAST(rating AS FLOAT)) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE listing_id = ?";
    $avgStmt = sqlsrv_query($conn, $avgSql, [$listing_id]);
    if ($avgStmt && $avgRow = sqlsrv_fetch_array($avgStmt, SQLSRV_FETCH_ASSOC)) {
        $avg = round((float)$avgRow['avg_r'], 1);
        $cnt = (int)$avgRow['cnt'];
        $updSql  = "UPDATE listings SET rating = ?, reviews = ? WHERE id = ?";
        sqlsrv_query($conn, $updSql, [$avg, $cnt, $listing_id]);
    }
    header("Location: ../listing.php?id=$listing_id&success=reviewed");
} else {
    error_log('submit-review error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
}
exit;
