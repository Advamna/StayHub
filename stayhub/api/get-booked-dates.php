<?php
// ── api/get-booked-dates.php ──────────────────────────────
// Addon 2 + 3: Returns booked AND pending-held date ranges for a listing
// Pending reservations within their 48h window count as blocked
require_once '../config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

$listing_id = (int)($_GET['listing_id'] ?? 0);
if (!$listing_id) { echo json_encode([]); exit; }

// First: auto-cancel any expired pending reservations
$expireSql = "UPDATE reservations SET status='cancelled'
              WHERE status='pending' AND expires_at IS NOT NULL AND expires_at < GETDATE()";
sqlsrv_query($conn, $expireSql);

// Then: return confirmed + active pending ranges
$sql  = "SELECT check_in, check_out, status
         FROM reservations
         WHERE listing_id = ?
           AND status IN ('confirmed', 'pending')
           AND check_out >= CAST(GETDATE() AS DATE)";
$stmt = sqlsrv_query($conn, $sql, [$listing_id]);

$ranges = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $ranges[] = [
            'start'  => ($row['check_in'] instanceof DateTime
                ? $row['check_in']->format('Y-m-d')
                : substr($row['check_in'], 0, 10)),
            'end'    => ($row['check_out'] instanceof DateTime
                ? $row['check_out']->format('Y-m-d')
                : substr($row['check_out'], 0, 10)),
            'status' => $row['status'],
        ];
    }
}

echo json_encode($ranges);
exit;
