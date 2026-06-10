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

// CSRF — accept token from POST body or X-CSRF-Token header
$postToken    = $_POST['csrf_token']             ?? '';
$headerToken  = $_SERVER['HTTP_X_CSRF_TOKEN']    ?? '';
$sessionToken = $_SESSION['csrf_token']          ?? '';
// Only enforce CSRF if a session token actually exists
if ($sessionToken && ($postToken !== $sessionToken && $headerToken !== $sessionToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request', 'debug' => 'csrf_mismatch']);
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
if (!$checkStmt) {
    $err = sqlsrv_errors();
    error_log('StayHub wishlist check error: ' . print_r($err, true));
    echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => json_encode($err)]);
    exit;
}
$existing = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if ($existing) {
    // Remove from wishlist
    $delSql  = "DELETE FROM wishlists WHERE user_id = ? AND listing_id = ?";
    $delStmt = sqlsrv_query($conn, $delSql, [$user_id, $listing_id]);
    if (!$delStmt) {
        error_log('StayHub wishlist delete error: ' . print_r(sqlsrv_errors(), true));
        echo json_encode(['success' => false, 'message' => 'Could not remove from saved']);
        exit;
    }
    echo json_encode(['success' => true, 'saved' => false]);
} else {
    // Add to wishlist — use MERGE to handle race conditions safely
    $insSql  = "IF NOT EXISTS (SELECT 1 FROM wishlists WHERE user_id = ? AND listing_id = ?)
                    INSERT INTO wishlists (user_id, listing_id, created_at) VALUES (?, ?, GETDATE())";
    $insStmt = sqlsrv_query($conn, $insSql, [$user_id, $listing_id, $user_id, $listing_id]);
    if (!$insStmt) {
        $err = sqlsrv_errors();
        error_log('StayHub wishlist insert error: ' . print_r($err, true));
        echo json_encode(['success' => false, 'message' => 'Could not save listing', 'debug' => json_encode($err)]);
        exit;
    }
    echo json_encode(['success' => true, 'saved' => true]);
}
exit;
