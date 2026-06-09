<?php
session_start();
require_once '../config.php';

// Auth check
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_host'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../my-listings.php');
    exit;
}

$user_id    = $_SESSION['user_id'];
$listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

if (!$listing_id) {
    header('Location: ../my-listings.php');
    exit;
}

// Make sure this listing belongs to the current host
$check_sql  = "SELECT id FROM listings WHERE id = ? AND user_id = ?";
$check_stmt = sqlsrv_query($conn, $check_sql, [$listing_id, $user_id]);
if (!$check_stmt || !sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC)) {
    header('Location: ../my-listings.php');
    exit;
}

// Sanitise inputs
$title          = htmlspecialchars(trim($_POST['title']          ?? ''));
$location       = htmlspecialchars(trim($_POST['location']       ?? ''));
$price          = (float)($_POST['price']          ?? 0);
$voyageur_count = (int)($_POST['voyageur_count']   ?? 1);
$bed_count      = (int)($_POST['bed_count']        ?? 1);
$description    = htmlspecialchars(trim($_POST['description']    ?? ''));

// Update listing row
$sql_update = "
    UPDATE listings
    SET title = ?, location = ?, price = ?, voyageur_count = ?, bed_count = ?, description = ?
    WHERE id = ? AND user_id = ?
";
$params = [$title, $location, $price, $voyageur_count, $bed_count, $description, $listing_id, $user_id];
$stmt   = sqlsrv_query($conn, $sql_update, $params);

if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

// Replace amenities
$del_am = "DELETE FROM amenities WHERE listing_id = ?";
sqlsrv_query($conn, $del_am, [$listing_id]);

if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
    $ins_am = "INSERT INTO amenities (listing_id, name) VALUES (?, ?)";
    foreach ($_POST['amenities'] as $amenity) {
        $amenity = htmlspecialchars(trim($amenity));
        if ($amenity !== '') {
            sqlsrv_query($conn, $ins_am, [$listing_id, $amenity]);
        }
    }
}

// Handle new photos (optional — only if files were uploaded)
if (!empty($_FILES['property_images']['name'][0])) {
    // Remove old images first
    $del_img = "DELETE FROM images WHERE listing_id = ?";
    sqlsrv_query($conn, $del_img, [$listing_id]);

    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $first = true;
    foreach ($_FILES['property_images']['tmp_name'] as $idx => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $ext      = strtolower(pathinfo($_FILES['property_images']['name'][$idx], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) continue;

        $filename = 'listing_' . $listing_id . '_' . time() . '_' . $idx . '.' . $ext;
        if (move_uploaded_file($tmp, $upload_dir . $filename)) {
            $img_sql = "INSERT INTO images (listing_id, image_url, is_primary) VALUES (?, ?, ?)";
            sqlsrv_query($conn, $img_sql, [$listing_id, $filename, $first ? 1 : 0]);
            $first = false;
        }
    }
}

header('Location: ../my-listings.php?updated=1');
exit;
?>
