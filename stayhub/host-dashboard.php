<?php
session_start();
require_once 'config.php';

// Security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_host']) || $_SESSION['is_host'] != 1) { 
    header("Location: become-host.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard | StayHub</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #eee; }
        .form-header { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .input-group { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #484848; font-size: 14px; }
        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; transition: border 0.3s;
        }
        input:focus { border-color: #FF5A5F; outline: none; box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1); }
        .amenities-section { background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .amenities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        .checkbox-item { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; }
        .checkbox-item input { accent-color: #FF5A5F; width: 18px; height: 18px; }
        .file-upload { border: 2px dashed #ddd; padding: 30px; text-align: center; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .file-upload:hover { border-color: #FF5A5F; background: #fff8f8; }
    </style>
</head>
<body>

<header class="main-header">
    <div class="container header-container">
        <div class="logo" onclick="window.location.href='index.php'">
            <span class="brand-text">StayHub</span>
        </div>
        <div class="header-right">
            <a href="index.php" class="btn-text">Switch to Traveling</a>
        </div>
    </div>
</header>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<div class="modal-overlay" style="display:flex; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:15px; width:90%; max-width:400px; text-align:center;">
        <i class="fas fa-check-circle" style="font-size:50px; color:#4CAF50; margin-bottom:15px;"></i>
        <h2 style="margin-bottom:10px;">Listing Published!</h2>
        <p style="color:#717171; margin-bottom:25px;">Your property has been successfully added to StayHub.</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <a href="index.php" class="btn-text" style="border:1px solid #ddd; padding:10px 15px; border-radius:8px; text-decoration:none; color:inherit;">Go to Home Page</a>
            <a href="host-dashboard.php" class="btn-reserve" style="text-decoration:none; display:inline-block; padding:10px 15px; border-radius:8px;">Add Another</a>
        </div>
    </div>
</div>
<?php endif; ?>

<main class="dashboard-container">
    <div class="form-header">
        <h1>Create a New Listing</h1>
        <p style="color: #717171;">Welcome back, <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>! Let's get your home ready for guests.</p>
    </div>

    <div class="form-card">
        <form action="api/add-listing.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <div class="full-width">
                    <label>Property Title</label>
                    <input type="text" name="title" placeholder="e.g. Cozy Beachfront Villa with Pool" required>
                </div>
                <div>
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Casablanca, Morocco" required>
                </div>
                <div>
                    <label>Price (MAD / night)</label>
                    <input type="number" name="price" placeholder="450" required>
                </div>
                <div>
                    <label>Max Guests</label>
                    <input type="number" name="voyageur_count" placeholder="4" required>
                </div>
                <div>
                    <label>Beds</label>
                    <input type="number" name="bed_count" placeholder="2" required>
                </div>
                <div class="full-width">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe what makes your place special..." rows="4"></textarea>
                </div>
            </div>

            <div class="amenities-section">
                <label>What amenities do you offer?</label>
                <div class="amenities-grid">
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="WiFi"> <i class="fas fa-wifi"></i> WiFi</label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="Kitchen"> <i class="fas fa-utensils"></i> Kitchen</label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="AC"> <i class="fas fa-snowflake"></i> Air Conditioning</label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="Parking"> <i class="fas fa-car"></i> Free Parking</label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="Pool"> <i class="fas fa-swimmer"></i> Pool</label>
                </div>
            </div>

            <label>Photos of your place</label>
            <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color: #FF5A5F;"></i>
                <p>Click to upload images</p>
                <input type="file" id="fileInput" name="property_images[]" multiple style="display:none;" required accept="image/*">
            </div>
            <div id="imagePreview" style="display:flex; gap:10px; margin-top:15px; flex-wrap:wrap;"></div>

            <button type="submit" class="btn-reserve" style="width: 100%; margin-top: 30px; font-size: 16px;">
                Publish My Listing
            </button>
        </form>
    </div>
</main>

<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    const files = e.target.files;
    for(let i=0; i<files.length; i++) {
        const file = files[i];
        if(!file.type.startsWith('image/')) continue;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100px';
            img.style.height = '100px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            img.style.border = '1px solid #ddd';
            img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            preview.appendChild(img);
        }
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>