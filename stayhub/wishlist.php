<?php
// ── wishlist.php — Feature 13: Saved listings ──
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql  = "SELECT l.id, l.title, l.location, l.price,
                (SELECT AVG(CAST(r.rating AS FLOAT)) FROM reviews r WHERE r.listing_id = l.id AND r.status = 'approved') AS rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.listing_id = l.id AND r.status = 'approved') AS reviews,
                i.image_url AS MainPhoto
         FROM wishlists w
         JOIN listings l ON w.listing_id = l.id
         LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
         WHERE w.user_id = ?
         ORDER BY w.created_at DESC";
$stmt = sqlsrv_query($conn, $sql, [$user_id]);

$saved = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $saved[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Listings | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"></noscript>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7f7f8; margin: 0; color: #222; }
        .top-nav { background: #fff; border-bottom: 1px solid #ebebeb; padding: 0 8%; height: 70px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .nav-logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .nav-link  { font-size: 14px; color: #717171; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .nav-link:hover { color: #222; }
        .page-header { padding: 48px 8% 24px; }
        .page-header h1 { font-size: 30px; font-weight: 800; margin: 0; }
        .page-header p  { color: #717171; margin: 6px 0 0; font-size: 15px; }
        .listings-wrap { padding: 0 8% 60px; }
        .listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .listing-card { cursor: pointer; background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid #ebebeb; transition: box-shadow 0.2s, transform 0.2s; }
        .listing-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .card-img { width: 100%; aspect-ratio: 4/3; object-fit: cover; }
        .card-body { padding: 14px 16px 16px; }
        .card-title { font-size: 15px; font-weight: 700; margin: 0 0 4px; }
        .card-loc   { font-size: 13px; color: #717171; margin: 0 0 10px; }
        .card-price { font-size: 15px; font-weight: 700; }
        .card-price span { font-weight: 400; color: #717171; font-size: 13px; }
        .card-row   { display: flex; justify-content: space-between; align-items: center; }
        .rating-pill { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 3px; }
        .rating-pill i { color: #ff385c; font-size: 12px; }
        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-state .icon { font-size: 56px; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-size: 22px; margin: 0 0 8px; }
        .empty-state p { color: #717171; margin: 0 0 24px; }
        .btn-explore { display: inline-block; background: #ff385c; color: #fff; padding: 13px 30px; border-radius: 10px; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="index.php" class="nav-logo">StayHub</a>
    <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to listings</a>
</nav>

<div class="page-header">
    <h1><i class="fas fa-heart" style="color:#ff385c;margin-right:10px;"></i>Saved Listings</h1>
    <p><?php echo count($saved); ?> saved propert<?php echo count($saved) !== 1 ? 'ies' : 'y'; ?></p>
</div>

<div class="listings-wrap">
    <?php if (empty($saved)): ?>
    <div class="empty-state">
        <span class="icon">💔</span>
        <h3>Nothing saved yet</h3>
        <p>Tap the ♥ on any listing to save it for later.</p>
        <a href="index.php" class="btn-explore">Explore listings</a>
    </div>
    <?php else: ?>
    <div class="listings-grid">
        <?php foreach ($saved as $l):
            $rating    = ($l['rating'] > 0) ? number_format((float)$l['rating'], 1) : null;
            $revCount  = (int)$l['reviews'];
        ?>
        <div class="listing-card" onclick="window.location.href='listing.php?id=<?php echo $l['id']; ?>'">
            <img class="card-img"
                 src="<?php echo !empty($l['MainPhoto']) ? htmlspecialchars($l['MainPhoto']) : 'img/placeholder.jpg'; ?>"
                 alt="<?php echo htmlspecialchars($l['title']); ?>" loading="lazy"
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=60'">
            <div class="card-body">
                <h4 class="card-title"><?php echo htmlspecialchars($l['title']); ?></h4>
                <p class="card-loc"><i class="fas fa-map-marker-alt" style="color:#ff385c;"></i> <?php echo htmlspecialchars($l['location']); ?></p>
                <div class="card-row">
                    <div class="card-price"><?php echo number_format($l['price']); ?> MAD <span>/ night</span></div>
                    <span class="rating-pill">
                        <i class="fas fa-star"></i>
                        <?php echo $rating ? $rating . ($revCount > 0 ? " ($revCount)" : '') : 'New'; ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
