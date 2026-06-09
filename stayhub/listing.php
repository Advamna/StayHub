<?php
session_start();
require_once 'config.php';
if (empty(\$_SESSION['csrf_token'])) {
    \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? $_GET['id'] : die("ID missing");
$sql = "SELECT l.*, u.name as HostName FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?";
$stmt = sqlsrv_query($conn, $sql, array($id));
$listing = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$listing) { die("Listing not found."); }

// --- AUTO-GENERATE DATA IF MISSING ---
$title = htmlspecialchars($listing['title']);

// Use the ID to seed the random generator so it stays consistent for this listing
srand((int)$id); 

// 1. Random Guests and Beds (if 0 or null)
$guests = ($listing['voyageur_count'] > 0) ? $listing['voyageur_count'] : rand(2, 6);
$beds = ($listing['bed_count'] > 0) ? $listing['bed_count'] : rand(1, 4);

// 2. Suitable Random Descriptions
$descriptions = [
    "Experience luxury in this stunning $title. Perfect for travelers looking for a mix of comfort and style. Features modern finishes and a breathtaking view of the surrounding area.",
    "This beautiful $title offers a peaceful escape from the city. Enjoy the spacious living areas, fully equipped kitchen, and cozy bedrooms designed for ultimate relaxation.",
    "A rare find! This $title is located in the heart of the most vibrant neighborhood. Step outside to find the best cafes and shops, then return to your quiet, private sanctuary.",
    "Welcome to your home away from home. This $title is perfect for families or groups, offering plenty of space and all the amenities you need for a perfect stay."
];
$desc = (!empty($listing['description'])) ? $listing['description'] : $descriptions[array_rand($descriptions)];

// 3. Random Amenities — shuffle while seed is still active so result is stable per listing
$fake_amenities = ['High-speed WiFi', 'Kitchen', 'Free Parking', 'Air Conditioning', 'Washing Machine', 'Dedicated Workspace', 'Pool', 'Gym', 'Balcony', 'BBQ Grill'];
shuffle($fake_amenities); // <-- seed still active here, result is deterministic per listing ID
$display_amenities = array_slice($fake_amenities, 0, 6); // Pick 6

// Also fetch DB amenities and prepend them
$amen_sql  = "SELECT name FROM amenities WHERE listing_id = ?";
$amen_stmt = sqlsrv_query($conn, $amen_sql, array($id));
$db_amenities = [];
if ($amen_stmt) {
    while ($a = sqlsrv_fetch_array($amen_stmt, SQLSRV_FETCH_ASSOC)) {
        $db_amenities[] = $a['name'];
    }
}
if (!empty($db_amenities)) {
    $display_amenities = $db_amenities;
} else {
    $display_amenities = $random_display;
}

// Reset seed
srand(); 

// --- IMAGE FETCH ---
$img_sql = "SELECT image_url FROM images WHERE listing_id = ? AND is_primary = 1";
$img_stmt = sqlsrv_query($conn, $img_sql, array($id));
$img_row = sqlsrv_fetch_array($img_stmt, SQLSRV_FETCH_ASSOC);
$display_image = $img_row ? $img_row['image_url'] : 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?> | StayHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; color: #222; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 10%; border-bottom: 1px solid #eee; }
        .logo { font-size: 24px; font-weight: bold; color: #ff385c; text-decoration: none; }
        .container { padding: 20px 10%; }
        .gallery-grid { width: 100%; height: 450px; background: url('<?php echo $display_image; ?>') center/cover; border-radius: 15px; margin: 20px 0; }
        .listing-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 50px; }
        .booking-card { border: 1px solid #ddd; padding: 25px; border-radius: 15px; position: sticky; top: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); text-align: center; }
        .btn-reserve { width: 100%; background: #ff385c; color: white; border: none; padding: 15px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; }
        .btn-reserve:hover { background: #e31c5f; }
        .amenity-tag { display: inline-block; background: #f7f7f7; padding: 8px 15px; border-radius: 20px; margin: 5px; font-size: 14px; }
        /* Modal Styles */
        /* Modal must be at the highest level */
/* The background overlay */
.modal { 
    display: none; 
    position: fixed; 
    z-index: 9999; 
    left: 0; top: 0; 
    width: 100%; height: 100%; 
    background-color: rgba(0,0,0,0.6);
    pointer-events: auto; /* Ensure the overlay can be clicked to close */
}

/* The actual white box */
.modal-content { 
    background-color: white; 
    margin: 5% auto; 
    padding: 30px; 
    width: 90%;
    max-width: 450px; 
    border-radius: 15px; 
    position: relative; 
    z-index: 10001; /* Must be higher than .modal */
    pointer-events: all; /* Force this area to allow typing and clicks */
}

/* Make the X button very easy to click */
.close { 
    position: absolute; 
    right: 20px; 
    top: 15px; 
    cursor: pointer; 
    font-size: 28px; 
    font-weight: bold;
    color: #333;
    z-index: 10002;
    padding: 10px; /* Bigger hit area */
}

/* Ensure the button is clickable */
.btn-reserve { 
    position: relative;
    z-index: 10;
    cursor: pointer !important; 
    pointer-events: auto !important;
}
.modal-content input { 
    width: 100%; 
    padding: 12px; 
    margin: 10px 0; 
    border: 1px solid #ddd; 
    border-radius: 8px; 
    box-sizing: border-box; /* This keeps them from overflowing */
}
</style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">StayHub</a>
        <a href="index.php" style="text-decoration:none; color:#717171;">← Return Home</a>
    </nav>

    <div class="container" style="padding-bottom: 0;">
    <?php if (isset($_GET['success']) && $_GET['success'] == 'booked'): ?>
        <div style="background: #e6fff1; color: #1d643b; padding: 15px; border-radius: 12px; border: 1px solid #c3e6cb; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">✅</span>
            <strong>Booking Successful!</strong> Your stay at <?php echo $title; ?> has been reserved.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background: #fff0f0; color: #721c24; padding: 15px; border-radius: 12px; border: 1px solid #f5c6cb; margin-bottom: 20px;">
            <strong>Error:</strong> 
            <?php 
                if ($_GET['error'] == 'already_booked') {
                    echo "Those dates are already taken. Please try another range.";
                } elseif ($_GET['error'] == 'not_logged_in') {
                    echo "You must be logged in to reserve this listing.";
                } else {
                    echo "Something went wrong. Please try again.";
                }
            ?>
        </div>
    <?php endif; ?>
</div>
    <div class="container">
        <h1><?php echo $title; ?></h1>
        <p style="color: #717171;"><?php echo htmlspecialchars($listing['location']); ?></p>

        <div class="gallery-grid"></div>

        <div class="listing-layout">
            <div class="details">
                <h3>Entire home hosted by <?php echo htmlspecialchars($listing['HostName']); ?></h3>
                <p style="color: #717171;"><?php echo $guests; ?> guests · <?php echo $beds; ?> beds</p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
                
                <h4 style="font-size: 20px;">About this space</h4>
                <p style="line-height: 1.6; color: #444;"><?php echo nl2br(htmlspecialchars($desc)); ?></p>
                
                <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
                <h4>What this place offers</h4>
                <div class="amenities-container">
                    <?php foreach($display_amenities as $amenity): ?>
                        <span class="amenity-tag"><?php echo $amenity; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sidebar">
                <div class="booking-card">
                    <p style="font-size: 22px; margin-bottom: 20px;"><b><?php echo number_format($listing['price']); ?> MAD</b> / night</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button id="openModal" class="btn-reserve">Reserve</button>
                        <p style="font-size: 12px; color: #717171; margin-top: 15px;">You won't be charged yet</p>
                    <?php else: ?>
                        <button onclick="document.getElementById('loginModal').style.display='flex';" class="btn-reserve">Log in to Reserve</button>
                        <p style="font-size: 12px; color: #717171; margin-top: 15px;">Please log in or sign up to book this stay</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div id="resModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h3 style="margin-top:0;">Reserve your stay</h3>
            <form action="api/process-booking.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="listing_id" value="<?php echo $id; ?>">
                <input type="text" name="guest_name" placeholder="Full Name" required>
                <input type="email" name="guest_email" placeholder="Email" required>
                <input type="text" name="guest_phone" placeholder="Phone Number" required>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label style="font-size:12px;">Check-in</label>
                        <input type="date" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label style="font-size:12px;">Check-out</label>
                        <input type="date" name="check_out" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn-reserve" style="margin-top:15px;">Confirm Booking</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Auth Modals -->
    <div id="loginModal" class="modal-overlay" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 450px; position: relative;">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer;">&times;</span>
            <?php include 'api/login.php'; ?>
            <p style="text-align: center; margin-top: 15px; font-size: 14px;">Don't have an account? <a href="javascript:void(0);" onclick="document.getElementById('loginModal').style.display='none'; document.getElementById('signupModal').style.display='flex';" style="color: #ff385c; text-decoration: none;">Sign up</a></p>
        </div>
    </div>

    <div id="signupModal" class="modal-overlay" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 450px; position: relative;">
            <span class="close" onclick="document.getElementById('signupModal').style.display='none'" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer;">&times;</span>
            <?php include 'api/signup.php'; ?>
            <p style="text-align: center; margin-top: 15px; font-size: 14px;">Already have an account? <a href="javascript:void(0);" onclick="document.getElementById('signupModal').style.display='none'; document.getElementById('loginModal').style.display='flex';" style="color: #ff385c; text-decoration: none;">Log in</a></p>
        </div>
    </div>
    <?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("resModal");
    const openBtn = document.getElementById("openModal");
    const closeBtn = document.getElementById("closeModal");

    // Open Modal
    if (openBtn) {
        openBtn.onclick = function(e) {
            e.preventDefault();
            modal.style.display = "block";
        }
    }

    // Close Modal via X
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
    }

    // Close Modal by clicking outside the white box
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Date Picker Constraints
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            checkOutInput.min = this.value;
            if (checkOutInput.value && checkOutInput.value < this.value) {
                checkOutInput.value = this.value;
            }
        });
    }
});
if (window.location.search.includes('success=booked')) {
    // Optional: Remove the "success=booked" from the URL after 5 seconds 
    // so the message doesn't stay there if they refresh
    setTimeout(() => {
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?id=<?php echo $id; ?>';
        window.history.pushState({path:newUrl},'',newUrl);
    }, 5000);
}
</script>
</body>
</html>