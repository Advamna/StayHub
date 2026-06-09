<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id     = (int)($_POST['user_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

// Don't let admins ban themselves
if ($id > 0 && $id !== $_SESSION['user_id'] && $reason !== '') {
    $sql  = "UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$reason, $id]);
    header('Location: ../users.php?done=' . ($stmt ? 'banned' : 'error'));
} else {
    header('Location: ../users.php?done=error');
}
exit;
