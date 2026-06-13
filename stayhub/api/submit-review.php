<?php
// ── api/submit-review.php ─────────────────────────────────
// Handles legacy inline form POSTs from listing.php (kept for backward compat)
// New submissions go through submit-review.php directly
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../my-rentals.php?error=csrf'); exit;
}

$user_id        = (int)$_SESSION['user_id'];
$listing_id     = (int)($_POST['listing_id']     ?? 0);
$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$rating         = (int)($_POST['rating']          ?? 0);
$comment        = trim($_POST['comment']          ?? '');
$title          = trim($_POST['title']            ?? '');

if ($rating < 1 || $rating > 5 || !$listing_id || !$reservation_id) {
    header('Location: ../my-rentals.php?error=invalid_review'); exit;
}
if (strlen($comment) < 10) {
    header("Location: ../listing.php?id=$listing_id&error=review_too_short"); exit;
}

// Verify reservation belongs to user, is confirmed, check_out in past
$checkSql  = "SELECT id FROM reservations WHERE id = ? AND user_id = ? AND status = 'confirmed'
              AND check_out <= CAST(GETDATE() AS DATE)";
$checkStmt = sqlsrv_query($conn, $checkSql, [$reservation_id, $user_id]);
if (!$checkStmt || !sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
    header('Location: ../my-rentals.php?error=not_eligible'); exit;
}

// Duplicate check
$dupSql  = "SELECT id FROM reviews WHERE reservation_id = ? AND user_id = ?";
$dupStmt = sqlsrv_query($conn, $dupSql, [$reservation_id, $user_id]);
if ($dupStmt && sqlsrv_fetch_array($dupStmt, SQLSRV_FETCH_ASSOC)) {
    header("Location: ../listing.php?id=$listing_id&error=already_reviewed"); exit;
}

$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
$title   = $title ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : null;

$sql  = "INSERT INTO reviews (listing_id, user_id, reservation_id, rating, title, comment, photos, status, is_featured, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NULL, 'pending', 0, GETDATE())";
$stmt = sqlsrv_query($conn, $sql, [$listing_id, $user_id, $reservation_id, $rating, $title, $comment]);

if ($stmt) {
    // Recalculate avg from approved reviews
    $avgSql  = "SELECT AVG(CAST(rating AS FLOAT)) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE listing_id = ? AND status = 'approved'";
    $avgStmt = sqlsrv_query($conn, $avgSql, [$listing_id]);
    if ($avgStmt && $avgRow = sqlsrv_fetch_array($avgStmt, SQLSRV_FETCH_ASSOC)) {
        sqlsrv_query($conn, "UPDATE listings SET rating = ?, reviews = ? WHERE id = ?",
                     [round((float)$avgRow['avg_r'], 1), (int)$avgRow['cnt'], $listing_id]);
    }
    header("Location: ../listing.php?id=$listing_id&success=reviewed");
} else {
    error_log('api/submit-review error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
}
exit;
