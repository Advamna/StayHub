<?php
// ── api/get-booked-dates.php ──────────────────────────────
// Features 11 & 15: Returns booked date ranges for a listing as JSON
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$listing_id = (int)($_GET['listing_id'] ?? 0);
if (!$listing_id) { echo json_encode([]); exit; }

$sql  = "SELECT check_in, check_out FROM reservations
         WHERE listing_id = ? AND status != 'cancelled'
         AND check_out >= CAST(GETDATE() AS DATE)";
$stmt = sqlsrv_query($conn, $sql, [$listing_id]);

$ranges = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $ranges[] = [
            'start' => ($row['check_in'] instanceof DateTime
                ? $row['check_in']->format('Y-m-d')
                : substr($row['check_in'], 0, 10)),
            'end'   => ($row['check_out'] instanceof DateTime
                ? $row['check_out']->format('Y-m-d')
                : substr($row['check_out'], 0, 10)),
        ];
    }
}

echo json_encode($ranges);
exit;
