<?php
session_start();
require_once 'config.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("ID missing");

$sql     = "SELECT l.*, u.name AS HostName, u.id AS HostId FROM listings l JOIN users u ON l.user_id = u.id WHERE l.id = ?";
$stmt    = sqlsrv_query($conn, $sql, [$id]);
$listing = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$listing) die("Listing not found.");

$title = htmlspecialchars($listing['title']);
srand((int)$id);
$guests = ($listing['voyageur_count'] > 0) ? $listing['voyageur_count'] : rand(2, 6);
$beds   = ($listing['bed_count']      > 0) ? $listing['bed_count']      : rand(1, 4);

$descriptions = [
    "Experience luxury in this stunning $title. Perfect for travelers looking for a mix of comfort and style.",
    "This beautiful $title offers a peaceful escape from the city. Enjoy the spacious living areas and cozy bedrooms.",
    "A rare find! This $title is located in the heart of the most vibrant neighborhood.",
    "Welcome to your home away from home. This $title is perfect for families or groups."
];
$desc = (!empty($listing['description'])) ? $listing['description'] : $descriptions[array_rand($descriptions)];

$fake_amenities = ['High-speed WiFi','Kitchen','Free Parking','Air Conditioning','Washing Machine','Dedicated Workspace','Pool','Gym','Balcony','BBQ Grill'];
shuffle($fake_amenities);
$display_amenities = array_slice($fake_amenities, 0, 6);
srand();

$amen_sql  = "SELECT name FROM amenities WHERE listing_id = ?";
$amen_stmt = sqlsrv_query($conn, $amen_sql, [$id]);
$db_amenities = [];
if ($amen_stmt) {
    while ($a = sqlsrv_fetch_array($amen_stmt, SQLSRV_FETCH_ASSOC)) $db_amenities[] = $a['name'];
}
if (!empty($db_amenities)) $display_amenities = $db_amenities;

$img_sql   = "SELECT image_url FROM images WHERE listing_id = ? AND is_primary = 1";
$img_stmt  = sqlsrv_query($conn, $img_sql, [$id]);
$img_row   = sqlsrv_fetch_array($img_stmt, SQLSRV_FETCH_ASSOC);
$display_image = $img_row ? $img_row['image_url'] : 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200';

// ── Feature 8: Fetch reviews ──────────────────────────────
$rev_sql  = "SELECT rv.*, u.name AS ReviewerName
             FROM reviews rv
             JOIN users u ON rv.user_id = u.id
             WHERE rv.listing_id = ?
             ORDER BY rv.created_at DESC";
$rev_stmt = sqlsrv_query($conn, $rev_sql, [$id]);
$reviews  = [];
if ($rev_stmt) {
    while ($r = sqlsrv_fetch_array($rev_stmt, SQLSRV_FETCH_ASSOC)) $reviews[] = $r;
}

// Compute real average rating
$avg_rating  = count($reviews) ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;
$review_count = count($reviews);

// Can current user review? (they must have a reservation for this listing)
$canReview      = false;
$reviewResId    = null;
$alreadyReviewed = false;
if (isset($_SESSION['user_id'])) {
    $canRevSql  = "SELECT r.id FROM reservations r WHERE r.listing_id = ? AND r.user_id = ? AND r.status IN ('confirmed','pending')";
    $canRevStmt = sqlsrv_query($conn, $canRevSql, [$id, $_SESSION['user_id']]);
    if ($canRevStmt && $canRevRow = sqlsrv_fetch_array($canRevStmt, SQLSRV_FETCH_ASSOC)) {
        $reviewResId = (int)$canRevRow['id'];
        // Check they haven't reviewed yet
        $dupSql  = "SELECT id FROM reviews WHERE reservation_id = ? AND user_id = ?";
        $dupStmt = sqlsrv_query($conn, $dupSql, [$reviewResId, $_SESSION['user_id']]);
        if ($dupStmt && sqlsrv_fetch_array($dupStmt, SQLSRV_FETCH_ASSOC)) {
            $alreadyReviewed = true;
        } else {
            $canReview = true;
        }
    }
}

$isHost = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$listing['HostId'];

// Wishlist status
$isSaved = false;
if (isset($_SESSION['user_id'])) {
    $wlSql  = "SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?";
    $wlStmt = sqlsrv_query($conn, $wlSql, [$_SESSION['user_id'], $id]);
    $isSaved = ($wlStmt && sqlsrv_fetch_array($wlStmt, SQLSRV_FETCH_ASSOC));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $title; ?> | StayHub</title>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; color: #222; }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:15px 10%; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:100; }
        .logo { font-size:24px; font-weight:bold; color:#ff385c; text-decoration:none; }
        .container { padding:20px 10%; }
        .gallery-grid { width:100%; height:450px; background:url('<?php echo $display_image; ?>') center/cover; border-radius:15px; margin:20px 0; position:relative; }
        .listing-layout { display:grid; grid-template-columns:2fr 1fr; gap:50px; }
        .booking-card { border:1px solid #ddd; padding:25px; border-radius:15px; position:sticky; top:90px; box-shadow:0 10px 20px rgba(0,0,0,0.05); text-align:center; }
        .btn-reserve { width:100%; background:#ff385c; color:#fff; border:none; padding:15px; border-radius:8px; cursor:pointer; font-size:16px; font-weight:bold; transition:0.3s; }
        .btn-reserve:hover { background:#e31c5f; }
        .amenity-tag { display:inline-block; background:#f7f7f7; padding:8px 15px; border-radius:20px; margin:5px; font-size:14px; }
        hr.divider { border:0; border-top:1px solid #eee; margin:30px 0; }

        /* ── Booking modal ───────────────────────────── */
        .modal {
            display: none; position: fixed; z-index: 9999;
            inset: 0; background: rgba(0,0,0,0.55);
            backdrop-filter: blur(3px);
            overflow-y: auto;
        }
        .modal-dialog {
            background: #fff;
            margin: 40px auto 40px;
            width: 92%; max-width: 480px;
            border-radius: 20px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.18);
            position: relative;
            overflow: hidden;
        }
        .modal-header {
            padding: 22px 28px 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-header h3 {
            margin: 0; font-size: 20px; font-weight: 700; color: #222;
        }
        .modal-header .modal-price-tag {
            font-size: 13px; color: #717171; font-weight: 400; margin-top: 2px;
        }
        .modal-close-btn {
            width: 34px; height: 34px; border-radius: 50%; border: none;
            background: #f5f5f5; cursor: pointer; font-size: 18px; color: #555;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .modal-close-btn:hover { background: #ebebeb; color: #222; }
        .modal-body { padding: 24px 28px 28px; }
        .field-group { margin-bottom: 16px; }
        .field-group label {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; color: #555;
            letter-spacing: 0.5px; text-transform: uppercase;
            margin-bottom: 6px;
        }
        .field-group label i { color: #ff385c; font-size: 11px; }
        .field-group input,
        .field-group select {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #e0e0e0; border-radius: 10px;
            box-sizing: border-box; font-size: 14px; color: #222;
            background: #fff; transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
            appearance: none; -webkit-appearance: none;
        }
        .field-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 14px center;
            padding-right: 36px;
        }
        .field-group input:focus,
        .field-group select:focus {
            outline: none; border-color: #ff385c;
            box-shadow: 0 0 0 3px rgba(255,56,92,0.1);
        }
        .field-group input::placeholder { color: #aaa; }
        .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-divider { border: none; border-top: 1px solid #f0f0f0; margin: 18px 0; }
        /* Price preview */
        .price-preview-box {
            background: linear-gradient(135deg, #fff5f7 0%, #fff 100%);
            border: 1.5px solid #ffd0d9;
            border-radius: 12px; padding: 14px 16px;
            margin-bottom: 18px; display: none;
        }
        .price-preview-box .pp-row {
            display: flex; justify-content: space-between;
            font-size: 13px; color: #555; margin-bottom: 6px;
        }
        .price-preview-box .pp-total {
            display: flex; justify-content: space-between;
            font-size: 15px; font-weight: 700; color: #222;
            border-top: 1px solid #ffd0d9; padding-top: 10px; margin-top: 6px;
        }
        /* Submit button */
        .btn-confirm-reserve {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #ff385c 0%, #e31c5f 100%);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            letter-spacing: 0.3px;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(255,56,92,0.35);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-confirm-reserve:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,56,92,0.45);
        }
        .btn-confirm-reserve:active { transform: translateY(0); }
        .btn-confirm-reserve:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .modal-footer-note {
            text-align: center; margin-top: 12px;
            font-size: 12px; color: #aaa;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        /* Keep old .close / .modal-content for auth modal compatibility */
        .modal-content { background:#fff; margin:5% auto; padding:30px; width:90%; max-width:470px; border-radius:15px; position:relative; z-index:10001; }
        .close { position:absolute; right:20px; top:15px; cursor:pointer; font-size:28px; font-weight:bold; color:#333; z-index:10002; }

        /* ── Feature 11: Availability calendar ── */
        .avail-calendar { margin-top: 20px; }
        .avail-calendar h4 { font-size: 17px; margin-bottom: 14px; }
        #mini-cal { font-family: 'Segoe UI', sans-serif; user-select: none; }
        .cal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .cal-header button { background:none; border:1px solid #ddd; border-radius:8px; padding:4px 10px; cursor:pointer; font-size:15px; }
        .cal-header button:hover { background:#f5f5f5; }
        .cal-month-label { font-weight:700; font-size:15px; }
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; text-align:center; }
        .cal-day-label { font-size:11px; color:#717171; font-weight:600; padding:4px 0; }
        .cal-day { font-size:13px; padding:6px 2px; border-radius:6px; cursor:default; }
        .cal-day.empty { }
        .cal-day.available { color:#222; background:#fff; }
        .cal-day.booked { background:#fee2e2; color:#b91c1c; text-decoration:line-through; }
        .cal-day.past { color:#ccc; }
        .cal-day.today { font-weight:700; border:2px solid #ff385c; }
        .cal-legend { display:flex; gap:16px; margin-top:10px; font-size:12px; color:#717171; align-items:center; }
        .cal-legend span { display:flex; align-items:center; gap:5px; }
        .dot { width:10px; height:10px; border-radius:3px; }

        /* ── Feature 8: Reviews section ── */
        .reviews-section { margin-top: 0; }
        .reviews-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .avg-rating-big { display:flex; align-items:center; gap:8px; font-size:22px; font-weight:700; }
        .avg-rating-big .stars i { color:#ff385c; font-size:18px; }
        .review-card { background:#f9f9f9; border-radius:12px; padding:20px; margin-bottom:16px; }
        .review-card .reviewer { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .reviewer-avatar { width:38px; height:38px; border-radius:50%; background:#ff385c; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; flex-shrink:0; }
        .reviewer-name { font-weight:600; font-size:15px; }
        .reviewer-date { font-size:12px; color:#717171; }
        .review-stars i { color:#ff385c; font-size:13px; }
        .review-comment { margin-top:8px; line-height:1.5; color:#444; font-size:14px; }
        .host-reply { margin-top:14px; padding:12px 16px; background:#fff; border-left:3px solid #ff385c; border-radius:0 8px 8px 0; }
        .host-reply-label { font-size:12px; color:#ff385c; font-weight:700; margin-bottom:5px; }
        .host-reply-text { font-size:14px; color:#444; }
        .reply-form textarea { width:100%; border:1px solid #ddd; border-radius:8px; padding:10px; font-size:14px; box-sizing:border-box; resize:vertical; margin-top:8px; }
        .reply-form button { background:#222; color:#fff; border:none; padding:8px 18px; border-radius:8px; cursor:pointer; font-size:13px; margin-top:6px; }
        .btn-write-review { background:#ff385c; color:#fff; border:none; padding:10px 22px; border-radius:8px; cursor:pointer; font-size:15px; font-weight:600; }
        .review-form-wrap { background:#f9f9f9; border-radius:12px; padding:24px; margin-top:24px; }
        .stars-picker { display:flex; gap:6px; margin:10px 0; }
        .stars-picker i { font-size:28px; color:#ddd; cursor:pointer; transition:color 0.15s; }
        .stars-picker i.active { color:#ff385c; }
        .review-form-wrap textarea { width:100%; border:1px solid #ddd; border-radius:8px; padding:12px; font-size:14px; box-sizing:border-box; resize:vertical; }
        .review-form-wrap button[type=submit] { background:#ff385c; color:#fff; border:none; padding:11px 28px; border-radius:8px; cursor:pointer; font-size:15px; font-weight:700; margin-top:12px; }

        /* Wishlist heart on gallery */
        .gallery-heart { position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.85); border:none; border-radius:50%; width:40px; height:40px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 1px 4px rgba(0,0,0,0.2); }
        .gallery-heart.saved i { color:#ff385c; }
        .gallery-heart:not(.saved) i { color:#717171; }

        @media(max-width:768px) {
            .listing-layout { grid-template-columns:1fr; }
            .booking-card { position:static; }
            .container { padding:16px 5%; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">StayHub</a>
    <a href="index.php" style="text-decoration:none;color:#717171;">← Return Home</a>
</nav>

<div class="container" style="padding-bottom:0;">
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'booked'): ?>
        <div style="background:#e6fff1;color:#1d643b;padding:15px;border-radius:12px;border:1px solid #c3e6cb;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <span style="font-size:20px;">✅</span><strong>Booking Successful!</strong> Your stay at <?php echo $title; ?> has been reserved.
        </div>
        <?php elseif ($_GET['success'] === 'reviewed'): ?>
        <div style="background:#e6fff1;color:#1d643b;padding:15px;border-radius:12px;border:1px solid #c3e6cb;margin-bottom:20px;">
            ⭐ <strong>Review submitted!</strong> Thank you for your feedback.
        </div>
        <?php elseif ($_GET['success'] === 'replied'): ?>
        <div style="background:#e6fff1;color:#1d643b;padding:15px;border-radius:12px;border:1px solid #c3e6cb;margin-bottom:20px;">
            ✅ <strong>Reply posted!</strong>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div style="background:#fff0f0;color:#721c24;padding:15px;border-radius:12px;border:1px solid #f5c6cb;margin-bottom:20px;">
        <strong>Error:</strong>
        <?php
            $errs = ['already_booked' => 'Those dates are already taken.', 'not_logged_in' => 'Please log in to reserve.', 'already_reviewed' => 'You already reviewed this listing.'];
            echo $errs[$_GET['error']] ?? 'Something went wrong. Please try again.';
        ?>
    </div>
    <?php endif; ?>
</div>

<div class="container">
    <h1 style="margin-bottom:4px;"><?php echo $title; ?></h1>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <p style="color:#717171;margin:0;">
            <?php if ($avg_rating): ?>
                <i class="fas fa-star" style="color:#ff385c;"></i>
                <?php echo number_format($avg_rating, 1); ?> · <?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?> ·
            <?php endif; ?>
            <?php echo htmlspecialchars($listing['location']); ?>
        </p>
        <?php if (isset($_SESSION['user_id'])): ?>
        <button class="gallery-heart <?php echo $isSaved ? 'saved' : ''; ?>"
                id="wishBtn"
                onclick="toggleWish(<?php echo $id; ?>)"
                title="<?php echo $isSaved ? 'Remove from saved' : 'Save listing'; ?>">
            <i class="fas fa-heart"></i>
        </button>
        <?php endif; ?>
    </div>

    <div class="gallery-grid"></div>

    <div class="listing-layout">
        <!-- Left column -->
        <div class="details">
            <h3>Entire home hosted by <?php echo htmlspecialchars($listing['HostName']); ?></h3>
            <p style="color:#717171;display:flex;flex-wrap:wrap;gap:16px;font-size:15px;margin:6px 0 0;">
                <span><i class="fas fa-users" style="color:#ff385c;margin-right:5px;"></i><?php echo (int)$listing['voyageur_count'] ?: $guests; ?> guests</span>
                <span><i class="fas fa-door-open" style="color:#ff385c;margin-right:5px;"></i><?php echo (int)($listing['bedrooms'] ?? 1); ?> bedroom<?php echo ($listing['bedrooms'] ?? 1) > 1 ? 's' : ''; ?></span>
                <span><i class="fas fa-bed" style="color:#ff385c;margin-right:5px;"></i><?php echo (int)($listing['bed_count'] ?? $beds); ?> bed<?php echo ($listing['bed_count'] ?? $beds) > 1 ? 's' : ''; ?></span>
                <span><i class="fas fa-bath" style="color:#ff385c;margin-right:5px;"></i><?php echo (int)($listing['bathrooms'] ?? 1); ?> bathroom<?php echo ($listing['bathrooms'] ?? 1) > 1 ? 's' : ''; ?></span>
            </p>
            <hr class="divider">

            <h4 style="font-size:20px;">About this space</h4>
            <p style="line-height:1.6;color:#444;"><?php echo nl2br(htmlspecialchars($desc)); ?></p>
            <hr class="divider">

            <h4>What this place offers</h4>
            <div class="amenities-container">
                <?php foreach ($display_amenities as $amenity): ?>
                    <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                <?php endforeach; ?>
            </div>
            <hr class="divider">

            <!-- ── Report listing ── -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $listing['HostId']): ?>
            <div style="margin-bottom:20px;">
                <?php if (!empty($listing['is_flagged'])): ?>
                    <span style="font-size:13px;color:#aaa;"><i class="fas fa-flag"></i> This listing has been reported and is under review.</span>
                <?php else: ?>
                    <button onclick="reportListing(<?php echo $id; ?>)" id="reportBtn"
                        style="background:none;border:none;color:#717171;font-size:13px;cursor:pointer;padding:0;display:flex;align-items:center;gap:6px;text-decoration:underline;">
                        <i class="fas fa-flag"></i> Report this listing
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ── Feature 11: Availability Calendar ── -->
            <div class="avail-calendar">
                <h4 style="font-size:20px;">Availability</h4>
                <p style="color:#717171;font-size:14px;margin-bottom:14px;">Select dates to see if this property is available.</p>
                <div id="mini-cal">
                    <div class="cal-header">
                        <button onclick="calPrev()">&#8249;</button>
                        <span class="cal-month-label" id="calMonthLabel"></span>
                        <button onclick="calNext()">&#8250;</button>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                    <div class="cal-legend">
                        <span><span class="dot" style="background:#fee2e2;border:1px solid #f87171;"></span> Booked</span>
                        <span><span class="dot" style="background:#fff;border:1px solid #ddd;"></span> Available</span>
                    </div>
                </div>
            </div>
            <hr class="divider">

            <!-- ── Feature 8: Reviews Section ── -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <div class="avg-rating-big">
                        <?php if ($avg_rating): ?>
                            <span><i class="fas fa-star" style="color:#ff385c;"></i></span>
                            <span><?php echo number_format($avg_rating, 1); ?></span>
                            <span style="font-size:14px;font-weight:400;color:#717171;">· <?php echo $review_count; ?> review<?php echo $review_count !== 1 ? 's' : ''; ?></span>
                        <?php else: ?>
                            <span style="font-size:18px;font-weight:600;">No reviews yet</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php foreach ($reviews as $rev): ?>
                <div class="review-card">
                    <div class="reviewer">
                        <div class="reviewer-avatar"><?php echo strtoupper(substr($rev['ReviewerName'], 0, 1)); ?></div>
                        <div>
                            <div class="reviewer-name"><?php echo htmlspecialchars($rev['ReviewerName']); ?></div>
                            <div class="reviewer-date">
                                <span class="review-stars">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="fas fa-star" style="color:<?php echo $s <= $rev['rating'] ? '#ff385c' : '#ddd'; ?>;"></i>
                                    <?php endfor; ?>
                                </span>
                                · <?php echo ($rev['created_at'] instanceof DateTime ? $rev['created_at']->format('M Y') : substr($rev['created_at'], 0, 7)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'] ?? '')); ?></div>

                    <?php if (!empty($rev['host_reply'])): ?>
                    <div class="host-reply">
                        <div class="host-reply-label"><i class="fas fa-user-tie"></i> Host response</div>
                        <div class="host-reply-text"><?php echo nl2br(htmlspecialchars($rev['host_reply'])); ?></div>
                    </div>
                    <?php elseif ($isHost): ?>
                    <!-- Feature 14: Host reply form -->
                    <div style="margin-top:10px;">
                        <button onclick="this.style.display='none'; document.getElementById('replyForm<?php echo $rev['id']; ?>').style.display='block';"
                                style="background:none;border:1px solid #ddd;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:13px;color:#717171;">
                            <i class="fas fa-reply"></i> Reply to review
                        </button>
                        <div id="replyForm<?php echo $rev['id']; ?>" style="display:none;" class="reply-form">
                            <form action="api/host-reply.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="review_id"  value="<?php echo $rev['id']; ?>">
                                <input type="hidden" name="listing_id" value="<?php echo $id; ?>">
                                <textarea name="host_reply" rows="3" placeholder="Write a public response…" required></textarea>
                                <button type="submit">Post reply</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Write a review form -->
                <?php if ($canReview): ?>
                <div class="review-form-wrap">
                    <h4 style="margin:0 0 6px;">Share your experience</h4>
                    <p style="font-size:14px;color:#717171;margin:0 0 16px;">How was your stay at <?php echo $title; ?>?</p>
                    <form action="api/submit-review.php" method="POST" id="reviewForm">
                        <input type="hidden" name="csrf_token"     value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="listing_id"     value="<?php echo $id; ?>">
                        <input type="hidden" name="reservation_id" value="<?php echo $reviewResId; ?>">
                        <input type="hidden" name="rating"         id="ratingInput" value="0">
                        <div class="stars-picker" id="starPicker">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fas fa-star" data-val="<?php echo $s; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comment" rows="4" placeholder="Tell other guests about your stay…"></textarea>
                        <button type="submit">Submit review</button>
                    </form>
                </div>
                <?php elseif ($alreadyReviewed): ?>
                <p style="font-size:14px;color:#717171;font-style:italic;margin-top:10px;">You've already reviewed this listing.</p>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                <p style="font-size:14px;color:#717171;margin-top:10px;">
                    <a href="javascript:void(0)" onclick="openLogin()" style="color:#ff385c;font-weight:600;">Log in</a> to leave a review after your stay.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right column: booking card -->
        <div class="sidebar">
            <div class="booking-card">
                <p style="font-size:22px;margin-bottom:20px;"><b><?php echo number_format($listing['price']); ?> MAD</b> / night</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="openModal" class="btn-reserve">Reserve</button>
                    <p style="font-size:12px;color:#717171;margin-top:15px;">You won't be charged yet</p>
                <?php else: ?>
                    <button onclick="openLogin()" class="btn-reserve">Log in to Reserve</button>
                    <p style="font-size:12px;color:#717171;margin-top:15px;">Please log in to book this stay</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Booking Modal ── -->
<?php if (isset($_SESSION['user_id'])): ?>
<div id="resModal" class="modal" onclick="if(event.target===this)closeResModal()">
    <div class="modal-dialog">

        <!-- Header -->
        <div class="modal-header">
            <div>
                <h3>Reserve your stay</h3>
                <div class="modal-price-tag">
                    <i class="fas fa-map-marker-alt" style="color:#ff385c;"></i>
                    <?php echo htmlspecialchars($location); ?>
                </div>
            </div>
            <button class="modal-close-btn" onclick="closeResModal()" aria-label="Close">&#x2715;</button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <form action="api/process-booking.php" method="POST" id="reserveForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="listing_id" value="<?php echo $id; ?>">

                <!-- Guest name -->
                <div class="field-group">
                    <label><i class="fas fa-user"></i> Full name</label>
                    <input type="text" name="guest_name" placeholder="John Doe" required autocomplete="name">
                </div>

                <!-- Email -->
                <div class="field-group">
                    <label><i class="fas fa-envelope"></i> Email address</label>
                    <input type="email" name="guest_email" placeholder="you@example.com" required autocomplete="email">
                </div>

                <!-- Phone -->
                <div class="field-group">
                    <label><i class="fas fa-phone"></i> Phone number</label>
                    <input type="tel" name="guest_phone" placeholder="+212 6 00 00 00 00" required autocomplete="tel">
                </div>

                <hr class="modal-divider">

                <!-- Dates -->
                <div class="date-row">
                    <div class="field-group">
                        <label><i class="fas fa-calendar-check"></i> Check-in</label>
                        <input type="date" name="check_in" id="checkInField"
                               required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="field-group">
                        <label><i class="fas fa-calendar-times"></i> Check-out</label>
                        <input type="date" name="check_out" id="checkOutField"
                               required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>

                <!-- Guests -->
                <div class="field-group">
                    <label><i class="fas fa-users"></i> Guests</label>
                    <select name="guests" id="guestsField">
                        <?php for ($g = 1; $g <= max(1, $guests); $g++): ?>
                            <option value="<?php echo $g; ?>"><?php echo $g; ?> Guest<?php echo $g > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Live price preview -->
                <div class="price-preview-box" id="pricePreview">
                    <div class="pp-row">
                        <span id="ppNights">— nights</span>
                        <span id="ppBase">— MAD</span>
                    </div>
                    <div class="pp-row">
                        <span>Cleaning fee</span>
                        <span>150 MAD</span>
                    </div>
                    <div class="pp-row">
                        <span>Service fee (10%)</span>
                        <span id="ppService">— MAD</span>
                    </div>
                    <div class="pp-total">
                        <span>Total</span>
                        <span id="ppTotal">— MAD</span>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-confirm-reserve" id="reserveSubmitBtn">
                    <i class="fas fa-lock"></i>
                    Confirm Reservation
                </button>

                <p class="modal-footer-note">
                    <i class="fas fa-shield-alt"></i>
                    You won't be charged yet — payment is done on the next step
                </p>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Auth Modal (for non-logged-in users) -->
<div id="authModal" class="modal-overlay" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;" onclick="if(event.target===this)closeAuthModal()">
    <div class="modal-content" style="background:#fff;padding:32px;border-radius:16px;width:90%;max-width:480px;position:relative;">
        <span class="close" onclick="closeAuthModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script>
// ── Gallery image fallback ───────────────────────────────────
(function() {
    var bg = document.querySelector('.gallery-grid');
    if (bg) {
        var url = bg.style.backgroundImage.replace(/url\(['"]?|['"]?\)/g,'');
        if (url && !url.startsWith('https://images.unsplash')) {
            var img = new Image();
            img.onerror = function() {
                bg.style.backgroundImage = "url('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200')";
            };
            img.src = url;
        }
    }
})();

// ── Booking modal ──────────────────────────────────────────
var NIGHTLY_PRICE = <?php echo (int)$price; ?>;
var openBtn  = document.getElementById('openModal');
var resModal = document.getElementById('resModal');

if (openBtn) openBtn.addEventListener('click', function() {
    resModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
});
function closeResModal() {
    if (resModal) resModal.style.display = 'none';
    document.body.style.overflow = '';
}

// Live price preview with breakdown
var checkIn  = document.getElementById('checkInField');
var checkOut = document.getElementById('checkOutField');

function updatePreview() {
    var box = document.getElementById('pricePreview');
    if (!checkIn || !checkOut || !checkIn.value || !checkOut.value) { box.style.display = 'none'; return; }
    var d1     = new Date(checkIn.value), d2 = new Date(checkOut.value);
    var nights = Math.round((d2 - d1) / 86400000);
    if (nights < 1) { box.style.display = 'none'; return; }
    var base    = NIGHTLY_PRICE * nights;
    var clean   = 150;
    var service = Math.round(base * 0.1);
    var total   = base + clean + service;
    document.getElementById('ppNights').textContent  = nights + ' night' + (nights > 1 ? 's' : '') + ' × ' + NIGHTLY_PRICE.toLocaleString() + ' MAD';
    document.getElementById('ppBase').textContent    = base.toLocaleString() + ' MAD';
    document.getElementById('ppService').textContent = service.toLocaleString() + ' MAD';
    document.getElementById('ppTotal').textContent   = total.toLocaleString() + ' MAD';
    box.style.display = 'block';
    // enforce checkout min
    var nextDay = new Date(checkIn.value);
    nextDay.setDate(nextDay.getDate() + 1);
    checkOut.min = nextDay.toISOString().split('T')[0];
}

if (checkIn)  checkIn.addEventListener('change', function() {
    if (checkOut.value && checkOut.value <= this.value) checkOut.value = '';
    updatePreview();
});
if (checkOut) checkOut.addEventListener('change', updatePreview);

// Submit loading state
var reserveForm = document.getElementById('reserveForm');
if (reserveForm) {
    reserveForm.addEventListener('submit', function() {
        var btn = document.getElementById('reserveSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
        }
    });
}

// ── Auth modal ────────────────────────────────────────────
function openLogin() {
    document.getElementById('modalBody').innerHTML = `
        <h2 style="margin-bottom:20px;">Log in to StayHub</h2>
        <form action="api/login.php" method="POST">
            <div style="margin-bottom:12px;"><label style="font-size:13px;font-weight:600;">Email</label><input type="email" name="email" placeholder="Email" required style="width:100%;padding:11px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box;margin-top:4px;"></div>
            <div style="margin-bottom:12px;"><label style="font-size:13px;font-weight:600;">Password</label><input type="password" name="password" placeholder="Password" required style="width:100%;padding:11px;border:1px solid #ccc;border-radius:8px;box-sizing:border-box;margin-top:4px;"></div>
            <button type="submit" style="background:#ff385c;color:#fff;border:none;padding:13px;width:100%;border-radius:8px;font-size:16px;font-weight:bold;cursor:pointer;">Log in</button>
        </form>`;
    document.getElementById('authModal').style.display = 'flex';
}
function closeAuthModal() { document.getElementById('authModal').style.display = 'none'; }

// ── Report listing ────────────────────────────────────────
function reportListing(listingId) {
    if (!confirm('Report this listing as inappropriate or inaccurate?')) return;
    var fd = new FormData();
    fd.append('listing_id', listingId);
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) fd.append('csrf_token', csrfMeta.content);
    fetch('api/flag-listing.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(d) {
            var btn = document.getElementById('reportBtn');
            if (d.success) {
                btn.outerHTML = '<span style="font-size:13px;color:#aaa;"><i class=\'fas fa-flag\'></i> Reported — our team will review this listing.</span>';
            } else {
                alert(d.message || 'Could not submit report. Please try again.');
            }
        })
        .catch(function() { alert('Network error. Please try again.'); });
}

// ── Star picker (review form) ─────────────────────────────
var stars = document.querySelectorAll('#starPicker i');
stars.forEach(function(star) {
    star.addEventListener('mouseover', function() {
        var val = parseInt(this.dataset.val);
        stars.forEach(function(s, i) { s.classList.toggle('active', i < val); });
    });
    star.addEventListener('click', function() {
        var val = parseInt(this.dataset.val);
        document.getElementById('ratingInput').value = val;
        stars.forEach(function(s, i) { s.classList.toggle('active', i < val); });
    });
});
document.getElementById('starPicker')?.addEventListener('mouseleave', function() {
    var current = parseInt(document.getElementById('ratingInput')?.value || 0);
    stars.forEach(function(s, i) { s.classList.toggle('active', i < current); });
});
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    if (parseInt(document.getElementById('ratingInput').value) < 1) {
        e.preventDefault();
        alert('Please select a star rating.');
    }
});

// ── Wishlist heart ────────────────────────────────────────
function toggleWish(listingId) {
    var btn = document.getElementById('wishBtn');
    var fd  = new FormData();
    fd.append('listing_id', listingId);
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) fd.append('csrf_token', csrfMeta.content);
    fetch('api/toggle-wishlist.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.redirect) { openLogin(); return; }
            if (data.success) {
                btn.classList.toggle('saved', data.saved);
                btn.title = data.saved ? 'Remove from saved' : 'Save listing';
            }
        });
}

// ── Feature 11: Availability mini-calendar ───────────────
var listingId = <?php echo $id; ?>;
var bookedRanges = [];
var calDate = new Date();
calDate.setDate(1);

fetch('api/get-booked-dates.php?listing_id=' + listingId)
    .then(r => r.json())
    .then(function(ranges) {
        bookedRanges = ranges.map(function(r) {
            return { start: new Date(r.start + 'T00:00:00'), end: new Date(r.end + 'T00:00:00') };
        });
        renderCal();
    })
    .catch(function() { renderCal(); });

function isBooked(date) {
    return bookedRanges.some(function(r) { return date >= r.start && date < r.end; });
}

function renderCal() {
    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = months[calDate.getMonth()] + ' ' + calDate.getFullYear();
    var grid = document.getElementById('calGrid');
    grid.innerHTML = '';
    ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(function(d) {
        var el = document.createElement('div');
        el.className = 'cal-day-label';
        el.textContent = d;
        grid.appendChild(el);
    });
    var first = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
    var today = new Date(); today.setHours(0,0,0,0);
    // Blanks before first day
    for (var b = 0; b < first.getDay(); b++) {
        var blank = document.createElement('div'); blank.className = 'cal-day empty'; grid.appendChild(blank);
    }
    var daysInMonth = new Date(calDate.getFullYear(), calDate.getMonth() + 1, 0).getDate();
    for (var d = 1; d <= daysInMonth; d++) {
        var date = new Date(calDate.getFullYear(), calDate.getMonth(), d);
        var el   = document.createElement('div');
        el.className = 'cal-day';
        el.textContent = d;
        if (date < today) { el.classList.add('past'); }
        else if (isBooked(date)) { el.classList.add('booked'); el.title = 'Already booked'; }
        else { el.classList.add('available'); }
        if (date.toDateString() === today.toDateString()) el.classList.add('today');
        grid.appendChild(el);
    }
}
function calPrev() { calDate.setMonth(calDate.getMonth() - 1); renderCal(); }
function calNext() { calDate.setMonth(calDate.getMonth() + 1); renderCal(); }
</script>
</body>
</html>
