<?php
session_start();
require_once 'config.php';

// Only hosts can access this page
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_host'])) {
    header('Location: become-host.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all listings belonging to this host
// Using subqueries instead of GROUP BY to avoid SQL Server column enumeration issues
$sql_listings = "
    SELECT
        l.id, l.user_id, l.title, l.description, l.location,
        l.price, l.voyageur_count, l.bed_count, l.created_at, l.status,
        i.image_url AS main_photo,
        (SELECT COUNT(*) FROM reservations r WHERE r.listing_id = l.id) AS total_bookings,
        COALESCE(
            (SELECT SUM(r2.total_price) FROM reservations r2
             WHERE r2.listing_id = l.id AND r2.status != 'cancelled'), 0
        ) AS total_revenue
    FROM listings l
    LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
";
$stmt_listings = sqlsrv_query($conn, $sql_listings, [$user_id]);

$listings = [];
if ($stmt_listings) {
    while ($row = sqlsrv_fetch_array($stmt_listings, SQLSRV_FETCH_ASSOC)) {
        $listings[] = $row;
    }
} else {
    // Surface SQL errors instead of silently failing
    $errs = sqlsrv_errors();
    error_log("my-listings.php query failed: " . print_r($errs, true));
}

// Build list of listing IDs for the rental query
$listing_ids = array_column($listings, 'id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings | StayHub</title>
    <meta name="description" content="Manage your StayHub properties, view rental history and control your listings.">
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"></noscript>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f7f7f8;
            color: #222;
            min-height: 100vh;
        }

        /* ── Navbar ── */
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
        .nav-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ff385c;
            text-decoration: none;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-link {
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
        .nav-link:hover { background: #f5f5f5; color: #222; }
        .btn-add-listing {
            background: #ff385c;
            color: #fff;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-add-listing:hover { background: #e31c5f; transform: translateY(-1px); }

        /* ── Page Header ── */
        .page-header {
            padding: 48px 8% 28px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header-text h1 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
        }
        .page-header-text p {
            color: #717171;
            font-size: 15px;
            margin-top: 6px;
        }

        /* ── Stats Bar ── */
        .stats-bar {
            margin: 0 8% 32px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 16px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .stat-icon.pink   { background: #fff0f2; color: #ff385c; }
        .stat-icon.green  { background: #e8f8f0; color: #1a9e5a; }
        .stat-icon.blue   { background: #e8f0ff; color: #3b6ef5; }
        .stat-icon.orange { background: #fff4e5; color: #e07b00; }
        .stat-value { font-size: 22px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #717171; margin-top: 2px; }

        /* ── Flash alerts ── */
        .alert {
            margin: 0 8% 24px;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #edfaf3; color: #14532d; border: 1px solid #bef0d1; }
        .alert-error   { background: #fff0f0; color: #7f1d1d; border: 1px solid #f5c6cb; }

        /* ── Listings ── */
        .listings-section {
            padding: 0 8% 60px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #ebebeb;
        }
        .empty-state .empty-icon { font-size: 56px; margin-bottom: 20px; display: block; }
        .empty-state h3 { font-size: 20px; margin-bottom: 8px; }
        .empty-state p  { color: #717171; margin-bottom: 24px; }

        /* ── Listing Block ── */
        .listing-block {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s;
        }
        .listing-block:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.09); }

        /* Listing header row */
        .listing-header {
            display: flex;
            align-items: stretch;
            gap: 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .listing-img {
            width: 200px;
            min-height: 150px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .listing-info {
            flex: 1;
            padding: 22px 26px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .listing-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .listing-location {
            font-size: 13px;
            color: #717171;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        .listing-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
            color: #444;
        }
        .listing-meta-row span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .listing-meta-row i { color: #ff385c; }

        .listing-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 10px;
            padding: 22px 24px;
            flex-shrink: 0;
        }
        .listing-revenue {
            text-align: right;
        }
        .listing-revenue .amount {
            font-size: 22px;
            font-weight: 700;
            color: #1a9e5a;
        }
        .listing-revenue .amount-label {
            font-size: 11px;
            color: #717171;
            margin-top: 2px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-edit {
            background: #fff;
            border: 1.5px solid #3b6ef5;
            color: #3b6ef5;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-edit:hover { background: #3b6ef5; color: #fff; }
        .btn-delete {
            background: #fff;
            border: 1.5px solid #ff385c;
            color: #ff385c;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-delete:hover { background: #ff385c; color: #fff; }

        /* ── Bookings Table ── */
        .bookings-section {
            padding: 0 0 0 0;
        }
        .bookings-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: #fafafa;
            border: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #444;
            cursor: pointer;
            transition: background 0.2s;
        }
        .bookings-toggle:hover { background: #f0f0f0; }
        .bookings-toggle .toggle-count {
            background: #ff385c;
            color: #fff;
            border-radius: 20px;
            padding: 2px 9px;
            font-size: 11px;
            font-weight: 700;
        }
        .toggle-icon { margin-left: auto; transition: transform 0.3s; }
        .bookings-toggle.open .toggle-icon { transform: rotate(180deg); }

        .bookings-table-wrap {
            display: none;
            overflow-x: auto;
            border-top: 1px solid #f0f0f0;
        }
        .bookings-table-wrap.open { display: block; }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .bookings-table th {
            background: #f9f9f9;
            padding: 12px 20px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #ebebeb;
            white-space: nowrap;
        }
        .bookings-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
        }
        .bookings-table tr:last-child td { border-bottom: none; }
        .bookings-table tr:hover td { background: #fafafa; }

        .guest-info { display: flex; flex-direction: column; }
        .guest-name { font-weight: 600; color: #222; }
        .guest-contact { font-size: 12px; color: #717171; margin-top: 2px; }

        .nights-badge {
            background: #f0f0f0;
            color: #444;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
        }
        .price-cell { font-weight: 700; color: #222; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .pill-confirmed  { background: #e8f8f0; color: #1a9e5a; }
        .pill-pending    { background: #fff8e1; color: #856404; }
        .pill-cancelled  { background: #fdecea; color: #b71c1c; }

        .no-bookings {
            text-align: center;
            padding: 28px;
            color: #aaa;
            font-size: 14px;
        }

        /* ── Delete Confirm Modal ── */
        .delete-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9000;
            align-items: center;
            justify-content: center;
        }
        .delete-overlay.open { display: flex; }
        .delete-modal {
            background: #fff;
            border-radius: 20px;
            padding: 40px 36px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.25s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.9); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .delete-modal .del-icon { font-size: 52px; margin-bottom: 18px; }
        .delete-modal h3 { font-size: 21px; margin-bottom: 10px; }
        .delete-modal p  { color: #717171; margin-bottom: 30px; font-size: 15px; line-height: 1.5; }
        .modal-actions { display: flex; gap: 12px; justify-content: center; }
        .btn-keep {
            padding: 12px 28px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-keep:hover { background: #f5f5f5; }
        .btn-confirm-delete {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            background: #ff385c;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-confirm-delete:hover { background: #c0112b; }

        @media (max-width: 768px) {
            .listing-header { flex-direction: column; }
            .listing-img { width: 100%; height: 200px; }
            .listing-actions { flex-direction: row; justify-content: space-between; align-items: center; padding: 16px 20px; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }

        /* ══════════════════════════════════════════
           STAYHUB UNIVERSAL PRINT STYLES
           ══════════════════════════════════════════ */
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #fff !important; margin: 0; padding: 0; }
            @page { margin: 14mm 12mm 14mm 12mm; size: A4 portrait; }

            .top-nav, .nav-bar, .sidebar, .filter-bar,
            .btn-print, .btn-add-listing, .btn-sm,
            .action-buttons, .stats-bar, .bookings-toggle,
            .alert, .delete-overlay, .adm-overlay,
            .no-print { display: none !important; }

            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .container    { margin: 0; padding: 0; max-width: 100%; }
            .page-header  { padding: 0 0 16px 0 !important; }

            .print-header {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 12px;
                margin-bottom: 18px;
                border-bottom: 2.5px solid #ff385c;
            }
            .print-logo { font-size: 28px; font-weight: 900; color: #ff385c !important; letter-spacing: -0.5px; }
            .print-logo span { color: #222 !important; }
            .print-meta { text-align: right; font-size: 11px; color: #555 !important; line-height: 1.8; }
            .print-meta strong { color: #111 !important; font-size: 12px; }

            .section-card, .listing-block, .notification-card {
                box-shadow: none !important; border: 1px solid #ddd !important;
                break-inside: avoid; margin-bottom: 12px !important; border-radius: 8px !important;
            }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #e0e0e0; padding: 8px 10px; }
            th { background: #f9f9f9 !important; font-weight: 600; font-size: 11px; letter-spacing: 0.4px; text-transform: uppercase; }
            .listing-img { width: 120px !important; min-height: 80px !important; }
            .bookings-table-wrap { display: block !important; }
            .bookings-section { page-break-inside: auto; }

            /* single-listing print mode */
            body.print-single .listing-block { display: none !important; }
            body.print-single .listing-block.print-target { display: block !important; }

            .print-footer {
                display: block !important; text-align: center;
                margin-top: 28px; padding-top: 12px;
                border-top: 1px solid #eee;
                font-size: 10px; color: #aaa !important; letter-spacing: 0.3px;
            }
        }

        /* ── Feature 15: Host availability calendar ── */
        .cal-section { margin-top: 0; }
        .cal-section-header { font-size: 14px; font-weight: 700; color: #484848; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .host-mini-cal { font-family: 'Inter', sans-serif; user-select: none; max-width: 320px; }
        .hcal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .hcal-nav button { background: none; border: 1px solid #ddd; border-radius: 6px; padding: 3px 9px; cursor: pointer; font-size: 14px; }
        .hcal-nav button:hover { background: #f5f5f5; }
        .hcal-month { font-weight: 700; font-size: 14px; }
        .hcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; text-align: center; }
        .hcal-dl { font-size: 10px; color: #717171; font-weight: 600; padding: 3px 0; }
        .hcal-day { font-size: 12px; padding: 5px 2px; border-radius: 5px; }
        .hcal-day.past { color: #ccc; }
        .hcal-day.booked { background: #fee2e2; color: #b91c1c; font-weight: 600; position: relative; cursor: default; }
        .hcal-day.avail { color: #222; }
        .hcal-day.today { font-weight: 700; border: 2px solid #ff385c; }
        .hcal-legend { display: flex; gap: 12px; margin-top: 8px; font-size: 11px; color: #717171; }
        .hcal-legend span { display: flex; align-items: center; gap: 4px; }
        .hcal-dot { width: 9px; height: 9px; border-radius: 3px; }
            .btn-toggle-status { padding:8px 14px; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:.15s; }
        .btn-activate   { background:#e8f5e9; color:#2e7d32; }
        .btn-activate:hover { background:#c8e6c9; }
        .btn-deactivate { background:#fff3e0; color:#e65100; }
        .btn-deactivate:hover { background:#ffe0b2; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="top-nav no-print">
    <a href="index.php" class="nav-logo">StayHub</a>
    <div class="nav-right">
        <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        <a href="host-dashboard.php" class="btn-add-listing"><i class="fas fa-plus"></i> Add New Listing</a>
    </div>
</nav>

<!-- Print letterhead -->
<div class="print-header" style="display:none;">
    <div class="print-logo">Stay<span>Hub</span></div>
    <div class="print-meta">
        <strong>My Listings</strong><br>
        <?php echo $totalListings; ?> listing<?php echo $totalListings!=1?'s':''; ?> &bull; <?php echo $totalBookings; ?> booking<?php echo $totalBookings!=1?'s':''; ?><br>
        Printed: <?php echo date('d/m/Y H:i'); ?>
    </div>
</div>


<?php
// Compute totals for stats bar
$totalListings = count($listings);
$totalBookings = 0;
$totalRevenue  = 0;
$totalActive   = 0;
foreach ($listings as $l) {
    $totalBookings += (int)$l['total_bookings'];
    $totalRevenue  += (float)$l['total_revenue'];
    $totalActive++;
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-text">
        <h1>My Listings</h1>
        <p>Manage your properties, track bookings and earnings</p>
    </div>
    <button onclick="printAll()" class="btn-add-listing" style="background:#fff; color:#3b6ef5; border:1.5px solid #3b6ef5; align-self:center;"><i class="fas fa-print"></i> Print All Listings</button>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-icon pink"><i class="fas fa-home"></i></div>
        <div>
            <div class="stat-value"><?php echo $totalListings; ?></div>
            <div class="stat-label">Total Properties</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
        <div>
            <div class="stat-value"><?php echo $totalBookings; ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-coins"></i></div>
        <div>
            <div class="stat-value"><?php echo number_format($totalRevenue, 0); ?> MAD</div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>Listing deleted successfully.</strong></div>
<?php endif; ?>
<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>Listing updated successfully.</strong></div>
<?php endif; ?>

<!-- Listings -->
<div class="listings-section">
    <?php if (empty($listings)): ?>
        <div class="empty-state">
            <span class="empty-icon">🏠</span>
            <h3>No listings yet</h3>
            <p>Start earning by adding your first property to StayHub.</p>
            <a href="host-dashboard.php" class="btn-add-listing" style="display:inline-flex; margin:0 auto;">
                <i class="fas fa-plus"></i> Add Your First Listing
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($listings as $listing):
            $listingId = $listing['id'];
            $imgSrc = !empty($listing['main_photo']) ? $listing['main_photo'] : 'img/default-avatar.png';

            // Fetch reservations for this listing
            $sql_res = "
                SELECT r.id, r.guest_name, r.guest_email, r.guest_phone,
                       r.check_in, r.check_out, r.guests, r.total_price, r.status,
                       DATEDIFF(day, r.check_in, r.check_out) AS nights
                FROM reservations r
                WHERE r.listing_id = ?
                ORDER BY r.check_in DESC
            ";
            $stmt_res = sqlsrv_query($conn, $sql_res, [$listingId]);
            $bookings = [];
            if ($stmt_res) {
                while ($b = sqlsrv_fetch_array($stmt_res, SQLSRV_FETCH_ASSOC)) {
                    $bookings[] = $b;
                }
            }
        ?>
        <div class="listing-block" id="listing-<?php echo $listingId; ?>">
            <!-- Listing Header -->
            <div class="listing-header">
                <img class="listing-img"
                     src="<?php echo htmlspecialchars($imgSrc); ?>"
                     alt="<?php echo htmlspecialchars($listing['title']); ?>">

                <div class="listing-info">
                    <div class="listing-title">
                        <?php echo htmlspecialchars($listing['title']); ?>
                        <?php if ($listing['status'] === 'pending'): ?>
                            <span style="font-size: 11px; background: #fff8e1; color: #856404; padding: 3px 8px; border-radius: 12px; margin-left: 10px; vertical-align: middle;"><i class="fas fa-clock"></i> Pending Approval</span>
                        <?php endif; ?>
                    </div>
                    <div class="listing-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($listing['location']); ?>
                    </div>
                    <div class="listing-meta-row">
                        <span><i class="fas fa-tag"></i> <?php echo number_format($listing['price'], 0); ?> MAD / night</span>
                        <span><i class="fas fa-users"></i> Up to <?php echo (int)$listing['voyageur_count']; ?> guests</span>
                        <span><i class="fas fa-bed"></i> <?php echo (int)$listing['bed_count']; ?> bed<?php echo $listing['bed_count'] > 1 ? 's' : ''; ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo (int)$listing['total_bookings']; ?> booking<?php echo $listing['total_bookings'] != 1 ? 's' : ''; ?></span>
                    </div>
                </div>

                <div class="listing-actions">
                    <div class="listing-revenue">
                        <div class="amount"><?php echo number_format($listing['total_revenue'], 0); ?> MAD</div>
                        <div class="amount-label">total revenue</div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="printSingle(<?php echo $listingId; ?>)" style="color:#222; border-color:#ccc;">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="edit-listing.php?id=<?php echo $listingId; ?>" class="btn-edit">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <?php $isActive = ($listing['status'] === 'active' || empty($listing['status'])); ?>
                        <button class="btn-toggle-status <?php echo $isActive ? 'btn-deactivate' : 'btn-activate'; ?>"
                                onclick="toggleStatus(<?php echo $listingId; ?>, <?php echo $isActive ? 'true' : 'false'; ?>)">
                            <i class="fas <?php echo $isActive ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                            <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        <button class="btn-delete"
                                onclick="confirmDelete(<?php echo $listingId; ?>, '<?php echo htmlspecialchars(addslashes($listing['title'])); ?>')">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bookings toggle -->
            <div class="bookings-section">
                <button class="bookings-toggle" onclick="toggleBookings(this)">
                    <i class="fas fa-users"></i>
                    Rental History
                    <span class="toggle-count"><?php echo count($bookings); ?></span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>

                <div class="bookings-table-wrap">
                    <?php if (empty($bookings)): ?>
                        <div class="no-bookings"><i class="fas fa-inbox" style="margin-right:8px;"></i>No bookings for this property yet.</div>
                    <?php else: ?>
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Duration</th>
                                    <th>Guests</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b):
                                    $checkIn  = ($b['check_in']  instanceof DateTime) ? $b['check_in']->format('d M Y')  : date('d M Y', strtotime($b['check_in']));
                                    $checkOut = ($b['check_out'] instanceof DateTime) ? $b['check_out']->format('d M Y') : date('d M Y', strtotime($b['check_out']));
                                    $nights   = max(1, (int)$b['nights']);
                                    $statusClass = match($b['status']) {
                                        'confirmed' => 'pill-confirmed',
                                        'cancelled' => 'pill-cancelled',
                                        default     => 'pill-pending',
                                    };
                                    $statusIcon = match($b['status']) {
                                        'confirmed' => 'fa-check-circle',
                                        'cancelled' => 'fa-times-circle',
                                        default     => 'fa-clock',
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <div class="guest-info">
                                            <span class="guest-name"><?php echo htmlspecialchars($b['guest_name']); ?></span>
                                            <span class="guest-contact"><?php echo htmlspecialchars($b['guest_email']); ?></span>
                                            <span class="guest-contact"><?php echo htmlspecialchars($b['guest_phone']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $checkIn; ?></td>
                                    <td><?php echo $checkOut; ?></td>
                                    <td><span class="nights-badge"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span></td>
                                    <td><?php echo (int)$b['guests']; ?></td>
                                    <td class="price-cell"><?php echo number_format($b['total_price'], 0); ?> MAD</td>
                                    <td>
                                        <span class="status-pill <?php echo $statusClass; ?>">
                                            <i class="fas <?php echo $statusIcon; ?>"></i>
                                            <?php echo ucfirst($b['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Feature 15: Host Booking Calendar ── -->
            <div class="bookings-section cal-section" style="margin-top:16px;">
                <button class="bookings-toggle" onclick="toggleBookings(this)">
                    <i class="fas fa-calendar-alt"></i>
                    Availability Calendar
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="bookings-table-wrap">
                    <div style="padding:16px;">
                        <div class="host-mini-cal" id="hcal-<?php echo $listing['id']; ?>">
                            <div class="hcal-nav">
                                <button onclick="hCalPrev(<?php echo $listing['id']; ?>)">&#8249;</button>
                                <span class="hcal-month" id="hcal-label-<?php echo $listing['id']; ?>"></span>
                                <button onclick="hCalNext(<?php echo $listing['id']; ?>)">&#8250;</button>
                            </div>
                            <div class="hcal-grid" id="hcal-grid-<?php echo $listing['id']; ?>"></div>
                            <div class="hcal-legend">
                                <span><span class="hcal-dot" style="background:#fee2e2;border:1px solid #f87171;"></span> Booked</span>
                                <span><span class="hcal-dot" style="background:#fff;border:1px solid #ddd;"></span> Available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-overlay" id="deleteOverlay">
    <div class="delete-modal">
        <div class="del-icon">🗑️</div>
        <h3>Delete this listing?</h3>
        <p id="deleteModalText">This will permanently remove the property and all its data. This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-keep" onclick="closeDelete()">Keep it</button>
            <a href="#" class="btn-confirm-delete" id="deleteConfirmLink"><i class="fas fa-trash-alt"></i> Yes, delete</a>
        </div>
    </div>
</div>

<script>
/* Toggle booking history accordion */
function toggleBookings(btn) {
    const wrap = btn.nextElementSibling;
    const isOpen = wrap.classList.contains('open');
    wrap.classList.toggle('open', !isOpen);
    btn.classList.toggle('open', !isOpen);
}

/* Delete modal */
function confirmDelete(id, title) {
    document.getElementById('deleteModalText').textContent =
        'Are you sure you want to delete "' + title + '"? All bookings and photos will be permanently removed.';
    document.getElementById('deleteConfirmLink').href = 'api/delete-listing.php?id=' + id;
    document.getElementById('deleteOverlay').classList.add('open');
}
function closeDelete() {
    document.getElementById('deleteOverlay').classList.remove('open');
}
document.getElementById('deleteOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeDelete();
});

function printAll() {
    document.body.classList.remove('print-single');
    window.print();
}
function printSingle(id) {
    document.body.classList.add('print-single');
    document.querySelectorAll('.listing-block').forEach(b => b.classList.remove('print-target'));
    document.getElementById('listing-' + id).classList.add('print-target');
    window.print();
}

// ── Feature 15: Host per-listing calendars ──────────────
var hCalDates  = {};  // listingId -> bookedRanges
var hCalMonths = {};  // listingId -> current Date

function hCalInit(listingId) {
    hCalMonths[listingId] = new Date();
    hCalMonths[listingId].setDate(1);
    fetch('api/get-booked-dates.php?listing_id=' + listingId)
        .then(function(r){ return r.json(); })
        .then(function(ranges) {
            hCalDates[listingId] = ranges.map(function(r) {
                return { start: new Date(r.start + 'T00:00:00'), end: new Date(r.end + 'T00:00:00') };
            });
            hCalRender(listingId);
        })
        .catch(function(){ hCalDates[listingId] = []; hCalRender(listingId); });
}

function hCalIsBooked(listingId, date) {
    return (hCalDates[listingId] || []).some(function(r){ return date >= r.start && date < r.end; });
}

function hCalRender(listingId) {
    var d = hCalMonths[listingId];
    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var label  = document.getElementById('hcal-label-' + listingId);
    var grid   = document.getElementById('hcal-grid-'  + listingId);
    if (!label || !grid) return;
    label.textContent = months[d.getMonth()] + ' ' + d.getFullYear();
    grid.innerHTML = '';
    ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(function(dl) {
        var el = document.createElement('div'); el.className = 'hcal-dl'; el.textContent = dl; grid.appendChild(el);
    });
    var today = new Date(); today.setHours(0,0,0,0);
    var first = new Date(d.getFullYear(), d.getMonth(), 1);
    for (var b = 0; b < first.getDay(); b++) {
        var blank = document.createElement('div'); blank.className = 'hcal-day'; grid.appendChild(blank);
    }
    var daysInMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    for (var day = 1; day <= daysInMonth; day++) {
        var date = new Date(d.getFullYear(), d.getMonth(), day);
        var el   = document.createElement('div');
        el.className = 'hcal-day';
        el.textContent = day;
        if (date < today) el.classList.add('past');
        else if (hCalIsBooked(listingId, date)) el.classList.add('booked');
        else el.classList.add('avail');
        if (date.toDateString() === today.toDateString()) el.classList.add('today');
        grid.appendChild(el);
    }
}
function hCalPrev(id) { hCalMonths[id].setMonth(hCalMonths[id].getMonth() - 1); hCalRender(id); }
function hCalNext(id) { hCalMonths[id].setMonth(hCalMonths[id].getMonth() + 1); hCalRender(id); }

// Init all listing calendars
<?php foreach ($listings as $l): ?>
hCalInit(<?php echo (int)$l['id']; ?>);
<?php endforeach; ?>
</script>

<!-- Print footer -->
<div class="print-footer" style="display:none;">
    StayHub &bull; My Listings &bull; Printed <?php echo date('d/m/Y'); ?>
</div>
</body>
</html>

