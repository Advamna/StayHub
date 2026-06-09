<?php
session_start();
if (empty($_SESSION['is_admin'])) { header('Location: ../../index.php'); exit; }
require_once '../../config.php';

$id = (int)($_POST['user_id'] ?? 0);

// Don't let admins delete themselves
if ($id > 0 && $id !== $_SESSION['user_id']) {
    // ON DELETE CASCADE will handle all related data
    $sql  = "DELETE FROM users WHERE id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    header('Location: ../users.php?done=' . ($stmt ? 'deleted' : 'error'));
} else {
    header('Location: ../users.php?done=error');
}
exit;
