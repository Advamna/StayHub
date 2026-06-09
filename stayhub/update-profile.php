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

    // ── Read raw bytes ──
    $imageData = file_get_contents($_FILES['avatar']['tmp_name']);

    // ── Parameterised binary insert ──
    $sql = "UPDATE users SET name = ?, avatar = ? WHERE id = ?";
    $params = [
        $name,
        [ $imageData, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max') ],
        $user_id
    ];

} else {
    // No new image — only update name
    $sql    = "UPDATE users SET name = ? WHERE id = ?";
    $params = [$name, $user_id];
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