<?php
header('Content-Type: application/json');
require_once '../config.php'; 

// Get data from the JavaScript Fetch request
$listing_id  = $_POST['listing_id'] ?? null;
$guest_name  = $_POST['guest_name'] ?? null;
$guest_email = $_POST['guest_email'] ?? null;
$guest_phone = $_POST['guest_phone'] ?? null;
$check_in    = $_POST['check_in'] ?? null;
$check_out   = $_POST['check_out'] ?? null;
$guests_count = $_POST['guests'] ?? 1;
$total_price = $_POST['total_price'] ?? 0;

// Validate required fields
if (!$listing_id || !$guest_name || !$guest_email || !$guest_phone) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit;
}

// SQL Server Insert for the 'reservations' table
$sql = "INSERT INTO reservations (
            listing_id, 
            guest_name, 
            guest_email, 
            guest_phone, 
            check_in, 
            check_out, 
            guests, 
            total_price, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$params = array(
    $listing_id, 
    $guest_name, 
    $guest_email, 
    $guest_phone, 
    $check_in, 
    $check_out, 
    $guests_count, 
    $total_price
);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo json_encode(['success' => true]);
} else {
    // If it fails, send the SQL error back to the console for debugging
    $errors = sqlsrv_errors();
    echo json_encode(['success' => false, 'message' => $errors[0]['message']]);
}
?>