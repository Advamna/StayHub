<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id   = $_SESSION['user_id'];
    $title     = $_POST['title'];
    $location  = $_POST['location'];
    $price     = $_POST['price'];
    $voyageurs = $_POST['voyageur_count'];
    $beds      = $_POST['bed_count'];
    $desc      = $_POST['description'];
    
    $sql = "INSERT INTO listings (user_id, title, description, location, price, voyageur_count, bed_count, status) 
            OUTPUT INSERTED.id
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params = array($user_id, $title, $desc, $location, $price, $voyageurs, $beds, 'pending');
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $new_id = $row['id'];

        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenity_name) {
                $am_sql = "INSERT INTO amenities (listing_id, name) VALUES (?, ?)";
                sqlsrv_query($conn, $am_sql, array($new_id, $amenity_name));
            }
        }

        if (!empty($_FILES['property_images']['name'][0])) {
            if (!is_dir('../img/uploads')) {
                mkdir('../img/uploads', 0755, true);
            }
            foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . "_" . $_FILES['property_images']['name'][$key];
                move_uploaded_file($tmp_name, "../img/uploads/" . $file_name);
                $img_sql = "INSERT INTO images (listing_id, image_url, is_primary) VALUES (?, ?, ?)";
                sqlsrv_query($conn, $img_sql, array($new_id, "img/uploads/" . $file_name, ($key == 0 ? 1 : 0)));
            }
        }
        header("Location: ../host-dashboard.php?success=1");
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Listing</title>
</head>
<body>
    <h2>Add a New Property</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Property Title:</label><br>
        <input type="text" name="title" required><br><br>
        <label>Location:</label><br>
        <input type="text" name="location" required><br><br>
        <label>Price per Night:</label><br>
        <input type="number" step="0.01" name="price" required><br><br>
        <label>Number of Guests:</label><br>
        <input type="number" name="voyageur_count" required><br><br>
        <label>Number of Beds:</label><br>
        <input type="number" name="bed_count" required><br><br>
        <label>Description:</label><br>
        <textarea name="description" rows="4"></textarea><br><br>
        <label>Amenities:</label><br>
        <input type="checkbox" name="amenities[]" value="WiFi"> WiFi
        <input type="checkbox" name="amenities[]" value="Kitchen"> Kitchen
        <input type="checkbox" name="amenities[]" value="Pool"> Pool
        <input type="checkbox" name="amenities[]" value="AC"> Air Conditioning<br><br>
        <label>Upload Images:</label><br>
        <input type="file" name="property_images[]" multiple required><br><br>
        <button type="submit">Publish Listing</button>
    </form>
</body>
</html>