<?php
// ── api/toggle-wishlist.php ───────────────────────────────
// Feature 13: Toggle save/unsave a listing
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required', 'redirect' => '../index.php']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$listing_id = (int)($_POST['listing_id'] ?? 0);

if (!$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing']);
    exit;
}

// Check if already saved
$checkSql  = "SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$user_id, $listing_id]);
$existing  = $checkStmt ? sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) : null;

if ($existing) {
    // Remove from wishlist
    $delSql  = "DELETE FROM wishlists WHERE user_id = ? AND listing_id = ?";
    $delStmt = sqlsrv_query($conn, $delSql, [$user_id, $listing_id]);
    echo json_encode(['success' => true, 'saved' => false]);
} else {
    // Add to wishlist
    $insSql  = "INSERT INTO wishlists (user_id, listing_id, created_at) VALUES (?, ?, GETDATE())";
    $insStmt = sqlsrv_query($conn, $insSql, [$user_id, $listing_id]);
    echo json_encode(['success' => true, 'saved' => true]);
}
exit;
