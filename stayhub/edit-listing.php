<?php
session_start();
require_once 'config.php';

// Only logged-in hosts can edit their own listings
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_host'])) {
    header('Location: index.php');
    exit;
}

$user_id    = $_SESSION['user_id'];
$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$listing_id) {
    header('Location: my-listings.php');
    exit;
}

// Fetch the listing — must belong to this host
$sql  = "SELECT * FROM listings WHERE id = ? AND user_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$listing_id, $user_id]);
$data = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$data) {
    header('Location: my-listings.php');
    exit;
}

// Fetch current amenities
$sql_am = "SELECT name FROM amenities WHERE listing_id = ?";
$stmt_am = sqlsrv_query($conn, $sql_am, [$listing_id]);
$current_amenities = [];
if ($stmt_am) {
    while ($a = sqlsrv_fetch_array($stmt_am, SQLSRV_FETCH_ASSOC)) {
        $current_amenities[] = $a['name'];
    }
}

// Fetch main image
$sql_img = "SELECT image_url FROM images WHERE listing_id = ? AND is_primary = 1";
$stmt_img = sqlsrv_query($conn, $sql_img, [$listing_id]);
$main_photo = '';
if ($stmt_img && $img_row = sqlsrv_fetch_array($stmt_img, SQLSRV_FETCH_ASSOC)) {
    $main_photo = $img_row['image_url'];
}

$allAmenities = [
    'WiFi'    => 'fa-wifi',
    'Kitchen' => 'fa-utensils',
    'AC'      => 'fa-snowflake',
    'Parking' => 'fa-car',
    'Pool'    => 'fa-swimmer',
    'TV'      => 'fa-tv',
    'Washer'  => 'fa-tshirt',
    'Gym'     => 'fa-dumbbell',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Listing | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f7f8;
            color: #222;
            min-height: 100vh;
        }

        /* Navbar */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #ebebeb;
            padding: 0 8%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 1px 8px rgba(0,0,0,0.05);
        }
        .nav-logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .back-link {
            font-size: 14px;
            font-weight: 500;
            color: #717171;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }
        .back-link:hover { background: #f5f5f5; color: #222; }

        /* Form container */
        .edit-container {
            max-width: 820px;
            margin: 48px auto;
            padding: 0 20px 60px;
        }
        .page-title { font-size: 28px; font-weight: 800; margin-bottom: 6px; }
        .page-subtitle { color: #717171; font-size: 15px; margin-bottom: 32px; }

        .form-card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: #ff385c; }

        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }
        .full { grid-column: span 2; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #484848;
            margin-bottom: 7px;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            color: #222;
            transition: border 0.25s, box-shadow 0.25s;
            background: #fff;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #ff385c;
            box-shadow: 0 0 0 3px rgba(255, 56, 92, 0.1);
        }
        textarea { resize: vertical; min-height: 110px; }

        /* Amenities */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 24px;
        }
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .amenity-item input { display: none; }
        .amenity-item:has(input:checked) {
            border-color: #ff385c;
            background: #fff0f2;
            color: #ff385c;
            font-weight: 600;
        }
        .amenity-item i { font-size: 15px; }

        /* Current photo preview */
        .photo-preview-wrap {
            margin-bottom: 16px;
        }
        .current-photo {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 10px;
            border: 1.5px solid #ddd;
        }
        .photo-hint { font-size: 12px; color: #999; margin-top: 6px; }

        /* File upload zone */
        .file-zone {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.25s, background 0.25s;
            margin-bottom: 24px;
        }
        .file-zone:hover { border-color: #ff385c; background: #fff8f9; }
        .file-zone i { font-size: 28px; color: #ff385c; margin-bottom: 8px; display: block; }
        .file-zone p { font-size: 14px; color: #717171; margin: 0; }

        #imagePreview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }
        #imagePreview img {
            width: 90px; height: 90px;
            object-fit: cover;
            border-radius: 8px;
            border: 1.5px solid #ddd;
        }

        /* Submit */
        .form-footer {
            display: flex;
            gap: 14px;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .btn-cancel-edit {
            padding: 13px 28px;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            background: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            color: #444;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: background 0.2s;
        }
        .btn-cancel-edit:hover { background: #f5f5f5; }
        .btn-save {
            padding: 13px 32px;
            border: none;
            border-radius: 12px;
            background: #ff385c;
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-save:hover { background: #e31c5f; transform: translateY(-1px); }

        @media (max-width: 640px) {
            .input-grid { grid-template-columns: 1fr; }
            .full { grid-column: span 1; }
            .form-footer { flex-direction: column-reverse; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="top-nav">
    <a href="index.php" class="nav-logo">StayHub</a>
    <a href="my-listings.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Listings</a>
</nav>

<div class="edit-container">
    <div class="page-title">Edit Listing</div>
    <p class="page-subtitle">Update the details of <strong><?php echo htmlspecialchars($data['title']); ?></strong></p>

    <div class="form-card">
        <form action="api/update-listing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">

            <!-- Basic Info -->
            <div class="section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
            <div class="input-grid">
                <div class="full">
                    <label>Property Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($data['title']); ?>" required>
                </div>
                <div>
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($data['location']); ?>" required>
                </div>
                <div>
                    <label>Price (MAD / night)</label>
                    <input type="number" name="price" value="<?php echo (int)$data['price']; ?>" min="1" required>
                </div>
                <div>
                    <label>Max Guests</label>
                    <input type="number" name="voyageur_count" value="<?php echo (int)$data['voyageur_count']; ?>" min="1" required>
                </div>
                <div>
                    <label>Beds</label>
                    <input type="number" name="bed_count" value="<?php echo (int)$data['bed_count']; ?>" min="1" required>
                </div>
                <div class="full">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Amenities -->
            <div class="section-title"><i class="fas fa-star"></i> Amenities</div>
            <div class="amenities-grid">
                <?php foreach ($allAmenities as $name => $icon): ?>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="<?php echo $name; ?>"
                            <?php echo in_array($name, $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas <?php echo $icon; ?>"></i>
                        <?php echo $name === 'AC' ? 'Air Conditioning' : $name; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Photo -->
            <div class="section-title"><i class="fas fa-images"></i> Photos</div>
            <?php if (!empty($main_photo)): ?>
                <div class="photo-preview-wrap">
                    <p style="font-size:13px; font-weight:600; color:#484848; margin-bottom:8px;">Current main photo:</p>
                    <img class="current-photo"
                         src="<?php echo htmlspecialchars(strpos($main_photo, 'http') === 0 ? $main_photo : 'uploads/' . $main_photo); ?>"
                         alt="Current photo">
                    <p class="photo-hint">Upload new photos below to replace existing ones.</p>
                </div>
            <?php endif; ?>

            <div class="file-zone" onclick="document.getElementById('newImages').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload new photos (optional)</p>
            </div>
            <input type="file" id="newImages" name="property_images[]" multiple accept="image/*" style="display:none;">
            <div id="imagePreview"></div>

            <!-- Footer -->
            <div class="form-footer">
                <a href="my-listings.php" class="btn-cancel-edit"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('newImages').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = ev => {
            const img = document.createElement('img');
            img.src = ev.target.result;
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>