<?php
// ── api/toggle-listing-status.php ─────────────────────────────────
// Host toggles their own listing between active / inactive.
// Returns JSON {success: bool, message: string, new_status: string}
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$listing_id = (int)($_POST['listing_id'] ?? 0);
$action     = trim($_POST['action'] ?? ''); // 'activate' or 'deactivate'

if (!$listing_id || !in_array($action, ['activate', 'deactivate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Verify this listing belongs to the logged-in user
$checkSql  = "SELECT id, status FROM listings WHERE id = ? AND user_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$listing_id, $user_id]);
if (!$checkStmt) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Listing not found or access denied']);
    exit;
}

$new_status = ($action === 'activate') ? 'active' : 'inactive';

$updateSql  = "UPDATE listings SET status = ? WHERE id = ? AND user_id = ?";
$updateStmt = sqlsrv_query($conn, $updateSql, [$new_status, $listing_id, $user_id]);

if ($updateStmt && sqlsrv_rows_affected($updateStmt) > 0) {
    echo json_encode([
        'success'    => true,
        'message'    => 'Listing ' . $new_status,
        'new_status' => $new_status
    ]);
} else {
    error_log('toggle-listing-status error: ' . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Could not update listing status']);
}
