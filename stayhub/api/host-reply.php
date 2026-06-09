<?php
// ── api/host-reply.php ────────────────────────────────────
// Feature 14: Host replies to a review
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }

if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../index.php?error=csrf'); exit;
}

$host_id   = (int)$_SESSION['user_id'];
$review_id = (int)($_POST['review_id'] ?? 0);
$reply     = trim($_POST['host_reply'] ?? '');
$listing_id = (int)($_POST['listing_id'] ?? 0);

if (!$review_id || !$reply) {
    header("Location: ../listing.php?id=$listing_id&error=empty_reply"); exit;
}

$reply = htmlspecialchars($reply, ENT_QUOTES, 'UTF-8');

// Verify host owns the listing that the review is for
$checkSql  = "SELECT r.id FROM reviews r
              JOIN listings l ON r.listing_id = l.id
              WHERE r.id = ? AND l.user_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$review_id, $host_id]);

if (!$checkStmt || !sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
    header("Location: ../listing.php?id=$listing_id&error=not_authorized"); exit;
}

$sql  = "UPDATE reviews SET host_reply = ?, host_replied_at = GETDATE() WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$reply, $review_id]);

if ($stmt) {
    header("Location: ../listing.php?id=$listing_id&success=replied");
} else {
    error_log('host-reply error: ' . print_r(sqlsrv_errors(), true));
    header("Location: ../listing.php?id=$listing_id&error=server");
}
exit;
