<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $sql  = "UPDATE listings SET is_flagged = 0, flag_reason = NULL WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    header('Location: ../listings.php?done=' . ($stmt ? 'unflagged' : 'error'));
} else {
    header('Location: ../listings.php?done=error');
}
exit;
