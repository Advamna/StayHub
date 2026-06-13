<?php
// ── api/auto-cancel-expired.php ──────────────────────────────────────────────
// Called by cron every 5 minutes OR on demand (before booking overlap check)
// Auto-cancels reservations that are still 'pending' and past their expires_at
// ─────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Allow cron (CLI) or internal server calls only — block public access
$isCli = (php_sapi_name() === 'cli');
$isInternal = isset($_SERVER['HTTP_X_CRON_SECRET'])
           && $_SERVER['HTTP_X_CRON_SECRET'] === (defined('CRON_SECRET') ? CRON_SECRET : 'stayhub_cron_2024');

if (!$isCli && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Cancel all pending reservations past their expires_at
$sql  = "UPDATE reservations
         SET status = 'cancelled'
         WHERE status = 'pending'
           AND expires_at IS NOT NULL
           AND expires_at < GETDATE()";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    $err = sqlsrv_errors();
    error_log('AutoCancel error: ' . print_r($err, true));
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}

$affected = sqlsrv_rows_affected($stmt);
error_log("AutoCancel: cancelled $affected expired reservations at " . date('Y-m-d H:i:s'));
echo json_encode(['success' => true, 'cancelled' => $affected, 'ran_at' => date('Y-m-d H:i:s')]);
exit;
