<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id     = (int)($_POST['listing_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($id > 0 && $reason !== '') {
    $sql  = "UPDATE listings SET is_flagged = 1, flag_reason = ? WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$reason, $id]);
    header('Location: ../listings.php?done=' . ($stmt ? 'flagged' : 'error'));
} else {
    header('Location: ../listings.php?done=error');
}
exit;
