<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $voyageurs = (int)$_POST['voyageur_count']; // Cast to int for safety
    $beds = (int)$_POST['bed_count'];
    $desc = $_POST['description'];
    
    // Using brackets [] in the query helps avoid 'Invalid Column' errors 
    // caused by reserved keywords or case sensitivity.
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

    // EXACT order: user_id, title, desc, location, price, voyageurs, beds
    $params = array($user_id, $title, $desc, $location, $price, $voyageurs, $beds);
    
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        // Get the new listing ID
        sqlsrv_next_result($stmt); 
        sqlsrv_fetch($stmt);
        $new_id = sqlsrv_get_field($stmt, 0);

        // Handle Amenities (Separate Table)
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenity_name) {
                $am_sql = "INSERT INTO amenities (listing_id, name) VALUES (?, ?)";
                sqlsrv_query($conn, $am_sql, array($new_id, $amenity_name));
            }
        }

        // Handle Images
        if (!empty($_FILES['property_images']['name'][0])) {
            foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . "_" . $_FILES['property_images']['name'][$key];
                if(move_uploaded_file($tmp_name, "../img/uploads/" . $file_name)) {
                    $img_sql = "INSERT INTO images (listing_id, image_url, is_primary) VALUES (?, ?, ?)";
                    sqlsrv_query($conn, $img_sql, array($new_id, "img/uploads/" . $file_name, ($key == 0 ? 1 : 0)));
                }
            }
        }
        
        header("Location: ../my-listings.php?success=1");
        exit();
    } else {
        // This will tell you EXACTLY which column name is failing
        echo "Database Error:<br/>";
        die(print_r(sqlsrv_errors(), true));
    }
}