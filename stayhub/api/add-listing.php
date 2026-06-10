<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id   = $_SESSION['user_id'];
    $title     = trim($_POST['title']);
    $location  = trim($_POST['location']);
    $price     = (float)$_POST['price'];
    $voyageurs = (int)$_POST['voyageur_count'];
    $beds      = (int)$_POST['bed_count'];
    $bedrooms  = (int)($_POST['bedrooms']  ?? 1);
    $bathrooms = (int)($_POST['bathrooms'] ?? 1);
    $desc      = trim($_POST['description']);

    // Sync both guests + voyageur_count so booking checks work correctly
    $sql = "INSERT INTO listings (user_id, title, description, location, price, voyageur_count, guests, bed_count, bedrooms, bathrooms, status) 
            OUTPUT INSERTED.id
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

    $params = array($user_id, $title, $desc, $location, $price, $voyageurs, $voyageurs, $beds, $bedrooms, $bathrooms);
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $row    = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $new_id = $row['id'];

        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenity_name) {
                $am_sql = "INSERT INTO amenities (listing_id, name) VALUES (?, ?)";
                sqlsrv_query($conn, $am_sql, array($new_id, $amenity_name));
            }
        }

        // FIX #3: Validate file type before uploading
        if (!empty($_FILES['property_images']['name'][0])) {
            if (!is_dir('../img/uploads')) {
                mkdir('../img/uploads', 0755, true);
            }

            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

            foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                // Check MIME type using finfo (reliable, not spoofable)
                $finfo     = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);

                if (!in_array($mime_type, $allowed_mime_types)) {
                    // FIX #4: Log the rejection privately
                    error_log('StayHub upload blocked — invalid MIME type: ' . $mime_type);
                    continue; // Skip this file silently
                }

                // Sanitize filename and force a safe extension
                $ext       = match($mime_type) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                    default      => 'jpg'
                };
                $file_name = time() . '_' . $key . '.' . $ext;

                move_uploaded_file($tmp_name, '../img/uploads/' . $file_name);

                $img_sql = "INSERT INTO images (listing_id, image_url, is_primary) VALUES (?, ?, ?)";
                sqlsrv_query($conn, $img_sql, array($new_id, 'img/uploads/' . $file_name, ($key == 0 ? 1 : 0)));
            }
        }

        header("Location: ../host-dashboard.php?success=1");
        exit();
    } else {
        // FIX #4: Log error privately
        error_log('StayHub add-listing error: ' . print_r(sqlsrv_errors(), true));
        header("Location: ../host-dashboard.php?error=server");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Listing | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Inter',sans-serif; background:#f7f7f8; margin:0; color:#222; }
        .al-nav { background:#fff; border-bottom:1px solid #ebebeb; padding:16px 8%; display:flex; justify-content:space-between; align-items:center; }
        .al-nav .logo { font-size:22px; font-weight:800; color:#ff385c; text-decoration:none; }
        .al-nav .back { font-size:14px; color:#717171; text-decoration:none; display:flex; align-items:center; gap:6px; }
        .al-nav .back:hover { color:#222; }
        .al-wrap { max-width:720px; margin:40px auto; padding:0 20px 60px; }
        .al-wrap h1 { font-size:26px; font-weight:800; margin-bottom:6px; }
        .al-wrap .sub { color:#717171; font-size:15px; margin-bottom:32px; }
        .al-card { background:#fff; border-radius:16px; border:1px solid #e8e8e8; box-shadow:0 2px 12px rgba(0,0,0,0.05); padding:28px 32px; margin-bottom:20px; }
        .al-card h3 { font-size:16px; font-weight:700; margin:0 0 20px; display:flex; align-items:center; gap:8px; }
        .al-card h3 i { color:#ff385c; }
        .fg { margin-bottom:16px; }
        .fg label { display:block; font-size:12px; font-weight:600; color:#555; letter-spacing:.5px; text-transform:uppercase; margin-bottom:6px; }
        .fg input, .fg textarea, .fg select {
            width:100%; padding:11px 14px; border:1.5px solid #e0e0e0; border-radius:10px;
            font-size:14px; font-family:'Inter',sans-serif; box-sizing:border-box;
            transition:border-color .2s, box-shadow .2s;
        }
        .fg input:focus, .fg textarea:focus { outline:none; border-color:#ff385c; box-shadow:0 0 0 3px rgba(255,56,92,.09); }
        .fg textarea { resize:vertical; min-height:100px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
        /* Amenities checkboxes */
        .amenity-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:10px; }
        .amenity-item { display:flex; align-items:center; gap:8px; padding:10px 12px;
            border:1.5px solid #e0e0e0; border-radius:10px; cursor:pointer; transition:.15s; }
        .amenity-item:has(input:checked) { border-color:#ff385c; background:#fff5f7; }
        .amenity-item input { accent-color:#ff385c; width:16px; height:16px; cursor:pointer; }
        .amenity-item span { font-size:13px; font-weight:500; }
        /* Image upload */
        .upload-zone { border:2px dashed #ddd; border-radius:12px; padding:30px; text-align:center; cursor:pointer; transition:.2s; }
        .upload-zone:hover { border-color:#ff385c; background:#fff5f7; }
        .upload-zone i { font-size:32px; color:#ff385c; margin-bottom:8px; }
        .upload-zone p { margin:6px 0 0; color:#717171; font-size:14px; }
        #imgPreview { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
        #imgPreview img { width:80px; height:80px; object-fit:cover; border-radius:8px; }
        /* Submit */
        .btn-publish { width:100%; padding:15px; background:linear-gradient(135deg,#ff385c,#e31c5f);
            color:#fff; border:none; border-radius:12px; font-size:16px; font-weight:700;
            cursor:pointer; box-shadow:0 4px 14px rgba(255,56,92,.35); transition:.15s; }
        .btn-publish:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(255,56,92,.45); }
    </style>
</head>
<body>
<nav class="al-nav">
    <a href="index.php" class="logo">StayHub</a>
    <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to listings</a>
</nav>
<div class="al-wrap">
    <h1>List your property</h1>
    <p class="sub">Share your space with travellers from around the world.</p>

    <form action="" method="POST" enctype="multipart/form-data">

        <!-- Basic info -->
        <div class="al-card">
            <h3><i class="fas fa-home"></i> Basic Information</h3>
            <div class="fg"><label>Property Title</label>
                <input type="text" name="title" placeholder="e.g. Modern Apartment in Casablanca" required></div>
            <div class="grid-2">
                <div class="fg"><label>Location</label>
                    <input type="text" name="location" placeholder="City, Country" required></div>
                <div class="fg"><label>Price per Night (MAD)</label>
                    <input type="number" name="price" min="1" step="0.01" placeholder="450" required></div>
            </div>
            <div class="fg"><label>Description</label>
                <textarea name="description" placeholder="Describe your space, the neighbourhood, what makes it special..."></textarea></div>
        </div>

        <!-- Property specs -->
        <div class="al-card">
            <h3><i class="fas fa-sliders-h"></i> Property Details</h3>
            <div class="grid-3">
                <div class="fg"><label><i class="fas fa-door-open"></i> Bedrooms</label>
                    <input type="number" name="bedrooms" min="0" value="1" required></div>
                <div class="fg"><label><i class="fas fa-bath"></i> Bathrooms</label>
                    <input type="number" name="bathrooms" min="0" value="1" required></div>
                <div class="fg"><label><i class="fas fa-bed"></i> Beds</label>
                    <input type="number" name="bed_count" min="1" value="1" required></div>
            </div>
            <div class="fg"><label><i class="fas fa-users"></i> Max Guests</label>
                <input type="number" name="voyageur_count" min="1" value="2" required></div>
        </div>

        <!-- Amenities -->
        <div class="al-card">
            <h3><i class="fas fa-star"></i> Amenities</h3>
            <div class="amenity-grid">
                <?php
                $amenity_list = ['WiFi','Kitchen','Pool','Air Conditioning','Parking','Washer',
                                 'Dryer','TV','Gym','Elevator','Balcony','Garden','Fireplace','BBQ'];
                foreach ($amenity_list as $a):
                ?>
                <label class="amenity-item">
                    <input type="checkbox" name="amenities[]" value="<?php echo htmlspecialchars($a); ?>">
                    <span><?php echo htmlspecialchars($a); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Images -->
        <div class="al-card">
            <h3><i class="fas fa-images"></i> Photos</h3>
            <div class="upload-zone" onclick="document.getElementById('imgInput').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload photos <br><small style="color:#aaa;">JPG, PNG, WEBP up to 10MB each</small></p>
                <input type="file" id="imgInput" name="property_images[]" multiple accept="image/*" required style="display:none">
            </div>
            <div id="imgPreview"></div>
        </div>

        <button type="submit" class="btn-publish"><i class="fas fa-paper-plane"></i> Publish Listing</button>
    </form>
</div>
<script>
document.getElementById('imgInput').addEventListener('change', function() {
    var prev = document.getElementById('imgPreview');
    prev.innerHTML = '';
    Array.from(this.files).forEach(function(f) {
        var img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        prev.appendChild(img);
    });
});
</script>
</body>
</html>
