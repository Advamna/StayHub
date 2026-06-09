<?php
session_start();
require_once 'config.php';

// FIX #5a: Validate and cast $_GET['id'] — reject non-numeric or missing values
$host_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($host_id <= 0) {
    header('Location: index.php');
    exit;
}

// 1. Fetch Host Details
$sql_user  = "SELECT name, avatar, is_host FROM users WHERE id = ?";
$stmt_user = sqlsrv_query($conn, $sql_user, array($host_id));
$host      = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);

// FIX #5b: Redirect cleanly instead of die() on not found
if (!$host) {
    header('Location: index.php?error=host_not_found');
    exit;
}

// FIX #5c: Build avatar from BLOB stored in DB (not a filename)
if (!empty($host['avatar'])) {
    $avatar_src = 'data:image/jpeg;base64,' . base64_encode($host['avatar']);
} else {
    $avatar_src = 'img/default-avatar.png';
}

// 2. Fetch all listings by this host
$sql_listings  = "SELECT l.*, i.image_url 
                  FROM listings l 
                  LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1 
                  WHERE l.user_id = ?";
$stmt_listings = sqlsrv_query($conn, $sql_listings, array($host_id));

// FIX #5d: Handle DB error privately
if (!$stmt_listings) {
    error_log('StayHub host-profile listings error: ' . print_r(sqlsrv_errors(), true));
    header('Location: index.php?error=server');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile of <?php echo htmlspecialchars($host['name']); ?> – StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background: #f7f7f7; font-family: 'Inter', sans-serif;">
    <div class="container" style="max-width: 1100px; margin: 50px auto; padding: 0 24px;">
        <div style="display: flex; gap: 40px; flex-wrap: wrap;">

            <!-- Host Card -->
            <div style="flex: 1; min-width: 280px; max-width: 350px;">
                <div class="add-listing-card" style="text-align: center; padding: 40px; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
                    <!-- FIX #5c: avatar rendered from BLOB base64 or fallback -->
                    <img src="<?php echo htmlspecialchars($avatar_src); ?>"
                         alt="<?php echo htmlspecialchars($host['name']); ?>"
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover;">
                    <h1 style="margin-top: 20px; font-size: 22px;">
                        <?php echo htmlspecialchars($host['name']); ?>
                    </h1>
                    <p style="color: #717171;">
                        <?php echo $host['is_host'] ? 'Confirmed Host' : 'StayHub Member'; ?>
                    </p>
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                    <div style="text-align: left; font-size: 14px; color: #444; display: flex; flex-direction: column; gap: 8px;">
                        <p>⭐ 120 reviews</p>
                        <p>🛡️ Identity verified</p>
                    </div>
                </div>
            </div>

            <!-- Listings Grid -->
            <div style="flex: 2; min-width: 300px;">
                <h2 style="margin-bottom: 20px; font-size: 20px;">
                    Listings by <?php echo htmlspecialchars($host['name']); ?>
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                    <?php
                    $has_listings = false;
                    while ($row = sqlsrv_fetch_array($stmt_listings, SQLSRV_FETCH_ASSOC)):
                        $has_listings = true;
                        // FIX #5c: image_url is an actual path (from uploads), not a BLOB — use as-is
                        $img_src = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'img/default.jpg';
                    ?>
                        <div onclick="window.location.href='listing.php?id=<?php echo (int)$row['id']; ?>'"
                             style="cursor: pointer; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.07); transition: transform 0.2s;"
                             onmouseover="this.style.transform='translateY(-4px)'"
                             onmouseout="this.style.transform='translateY(0)'">
                            <img src="<?php echo $img_src; ?>"
                                 alt="<?php echo htmlspecialchars($row['title']); ?>"
                                 style="width: 100%; height: 180px; object-fit: cover;">
                            <div style="padding: 14px;">
                                <h3 style="font-size: 15px; margin: 0 0 6px; color: #222;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h3>
                                <p style="font-size: 14px; color: #717171; margin: 0;">
                                    <?php echo number_format($row['price'], 0); ?> MAD / night
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php if (!$has_listings): ?>
                        <p style="color: #717171; font-size: 14px;">This host has no active listings yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
