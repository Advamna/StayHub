<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: ../become-host.php');
    exit;
}

// ── CSRF check ──
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    error_log('StayHub CSRF mismatch on add-listing');
    header('Location: ../become-host.php?error=csrf');
    exit;
}

$user_id   = (int)$_SESSION['user_id'];

// ── Sanitize & validate text inputs ──
$title     = trim($_POST['title']    ?? '');
$location  = trim($_POST['location'] ?? '');
$desc      = trim($_POST['description'] ?? '');
$price     = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
$voyageurs = isset($_POST['voyageur_count']) ? (int)$_POST['voyageur_count'] : 1;
$beds      = isset($_POST['bed_count'])      ? (int)$_POST['bed_count']      : 1;

if (!$title || !$location || $price === false || $price <= 0) {
    header('Location: ../become-host.php?error=invalid_input');
    exit;
}
if (mb_strlen($title) > 200)    $title    = mb_substr($title, 0, 200);
if (mb_strlen($location) > 100) $location = mb_substr($location, 0, 100);
if (mb_strlen($desc) > 5000)    $desc     = mb_substr($desc, 0, 5000);

$sql = "INSERT INTO listings (
            [user_id], 
            [title], 
            [description], 
            [location], 
            [price], 
            [voyageur_count], 
            [bed_count]
        ) VALUES (?, ?, ?, ?, ?, ?, ?);
        SELECT SCOPE_IDENTITY() AS last_id;";

$params = array($user_id, $title, $desc, $location, $price, $voyageurs, $beds);
$stmt   = sqlsrv_query($conn, $sql, $params);

if (!$stmt) {
    error_log('StayHub add-listing DB error: ' . print_r(sqlsrv_errors(), true));
    header('Location: ../become-host.php?error=db');
    exit;
}

// Get the new listing ID
sqlsrv_next_result($stmt);
sqlsrv_fetch($stmt);
$new_id = (int)sqlsrv_get_field($stmt, 0);

// ── Handle Amenities ──
if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
    foreach ($_POST['amenities'] as $amenity_name) {
        $amenity_name = trim(htmlspecialchars($amenity_name));
        if ($amenity_name) {
            $am_sql = "INSERT INTO amenities (listing_id, name) VALUES (?, ?)";
            sqlsrv_query($conn, $am_sql, array($new_id, $amenity_name));
        }
    }
}

// ── Handle Images — with MIME type validation ──
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxBytes    = 5 * 1024 * 1024; // 5 MB per image

if (!empty($_FILES['property_images']['name'][0])) {
    foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
        // Skip files with upload errors
        if ($_FILES['property_images']['error'][$key] !== UPLOAD_ERR_OK) continue;

        // Check file size
        if ($_FILES['property_images']['size'][$key] > $maxBytes) continue;

        // Validate MIME type via file content — not extension or client header
        $mime = mime_content_type($tmp_name);
        if (!in_array($mime, $allowedMime)) {
            error_log('StayHub blocked non-image upload: ' . $mime);
            continue; // Skip this file silently
        }

        // Safe filename: timestamp + sanitized original name
        $original  = basename($_FILES['property_images']['name'][$key]);
        $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
        $dest      = "../img/uploads/" . $safe_name;

        if (move_uploaded_file($tmp_name, $dest)) {
            $img_sql = "INSERT INTO images (listing_id, image_url, is_primary) VALUES (?, ?, ?)";
            sqlsrv_query($conn, $img_sql, array($new_id, "img/uploads/" . $safe_name, ($key === 0 ? 1 : 0)));
        }
    }
}

header("Location: ../my-listings.php?success=1");
exit;
