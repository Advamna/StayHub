<?php
// ── api/flag-listing.php ───────────────────────────────────────────
// Sets listings.is_flagged = 1 when a logged-in user reports a listing.
// Returns JSON {success: bool, message: string}
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

// CSRF check
$postTok   = $_POST['csrf_token']              ?? '';
$headerTok = $_SERVER['HTTP_X_CSRF_TOKEN']     ?? '';
$sessTok   = $_SESSION['csrf_token']           ?? '';
if ($sessTok && $postTok !== $sessTok && $headerTok !== $sessTok) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$listing_id = (int)($_POST['listing_id'] ?? 0);

if (!$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing']);
    exit;
}

// Don't allow hosts to flag their own listings
$ownerSql  = "SELECT user_id, is_flagged FROM listings WHERE id = ?";
$ownerStmt = sqlsrv_query($conn, $ownerSql, [$listing_id]);
if (!$ownerStmt) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$row = sqlsrv_fetch_array($ownerStmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Listing not found']);
    exit;
}
if ((int)$row['user_id'] === $user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot report your own listing']);
    exit;
}
if (!empty($row['is_flagged'])) {
    echo json_encode(['success' => true, 'message' => 'Already reported']);
    exit;
}

// Set is_flagged = 1
$flagSql  = "UPDATE listings SET is_flagged = 1 WHERE id = ?";
$flagStmt = sqlsrv_query($conn, $flagSql, [$listing_id]);

if ($flagStmt && sqlsrv_rows_affected($flagStmt) > 0) {
    // Notify admins via notifications
    $adminSql  = "SELECT id FROM users WHERE is_admin = 1";
    $adminStmt = sqlsrv_query($conn, $adminSql);
    while ($admin = sqlsrv_fetch_array($adminStmt, SQLSRV_FETCH_ASSOC)) {
        $notifSql = "INSERT INTO notifications (user_id, title, message, is_read, created_at)
                     VALUES (?, 'Listing Flagged', ?, 0, GETDATE())";
        $msg = "Listing #$listing_id has been reported by a user and needs review.";
        sqlsrv_query($conn, $notifSql, [(int)$admin['id'], $msg]);
    }
    echo json_encode(['success' => true, 'message' => 'Report submitted']);
} else {
    error_log('flag-listing error: ' . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
