<?php
session_start();
require_once 'config.php';

sqlsrv_configure("WarningsReturnAsErrors", 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name    = trim($_POST['name'] ?? '');

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {

    // ── Validate: image only, max 5MB ──
    $maxBytes  = 5 * 1024 * 1024; // 5 MB
    $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
    $mime = mime_content_type($_FILES['avatar']['tmp_name']);

    if (!in_array($mime, $allowedMime)) {
        header("Location: profile.php?error=invalid_type");
        exit();
    }
    if ($_FILES['avatar']['size'] > $maxBytes) {
        header("Location: profile.php?error=too_large");
        exit();
    }

    // ── Save file to disk and store the filename in the DB ──
    $phone     = trim($_POST['phone'] ?? '');
    $ext       = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename  = 'avatar_' . $user_id . '_' . time() . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
        header("Location: profile.php?error=upload_failed");
        exit();
    }

    // Store only the relative filename — avoids VARCHAR truncation entirely
    $sql    = "UPDATE users SET name = ?, phone = ?, avatar = ? WHERE id = ?";
    $params = [$name, $phone, $filename, $user_id];

} else {
    // No new image — update name and phone
    $phone  = trim($_POST['phone'] ?? '');
    $sql    = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
    $params = [$name, $phone, $user_id];
}

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    header("Location: profile.php?success=1");
} else {
    // Only show errors in development; swap to a generic redirect in production
    echo "<pre>"; print_r(sqlsrv_errors()); echo "</pre>";
    die("Upload failed. See errors above.");
}
?>