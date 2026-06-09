<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $sql  = "UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    header('Location: ../users.php?done=' . ($stmt ? 'unbanned' : 'error'));
} else {
    header('Location: ../users.php?done=error');
}
exit;
