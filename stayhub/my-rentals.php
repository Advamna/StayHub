<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT r.*, l.title, l.location, l.price, i.image_url 
        FROM reservations r
        JOIN listings l ON r.listing_id = l.id
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";

$params = array($user_id);
$stmt   = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$rentals = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rentals[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stays - StayHub</title>
    <meta name="description" content="View and manage your StayHub reservations.">
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f7f7f8;
            margin: 0;
            color: #222;
        }

        /* ── Navbar ── */
        .stays-nav {
            background: #fff;
            border-bottom: 1px solid #ebebeb;
            padding: 16px 8%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .stays-logo {
            font-size: 22px;
            font-weight: 700;
            color: #ff385c;
            text-decoration: none;
            cursor: pointer;
        }
        .stays-nav a.back-link {
            font-size: 14px;
            color: #717171;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .stays-nav a.back-link:hover { color: #222; }

        /* ── Page Header ── */
        .page-header {
            padding: 40px 8% 20px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 6px;
        }
        .page-header p {
            color: #717171;
            margin: 0;
            font-size: 15px;
        }

        /* ── Alerts ── */
        .alert {
            margin: 0 8% 20px;
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

        /* ── Cards Grid ── */
        .stays-list {
            padding: 0 8% 60px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #ebebeb;
        }
        .empty-state .empty-icon {
            font-size: 56px;
            margin-bottom: 20px;
            display: block;
        }
        .empty-state h3 { font-size: 20px; margin: 0 0 8px; }
        .empty-state p  { color: #717171; margin: 0 0 24px; }
        .btn-explore {
            display: inline-block;
            background: #ff385c;
            color: #fff;
            padding: 13px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-explore:hover { background: #e31c5f; transform: translateY(-1px); }

        /* ── Rental Card ── */
        .rental-card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .rental-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.09);
            transform: translateY(-2px);
        }
        .rental-card.cancelled {
            opacity: 0.7;
        }

        .rental-img {
            width: 180px;
            min-height: 140px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .rental-body {
            flex: 1;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .rental-title {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 5px;
        }
        .rental-location {
            font-size: 13px;
            color: #717171;
            margin: 0 0 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .rental-dates {
            font-size: 14px;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rental-dates i { color: #ff385c; }

        .rental-meta {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            min-width: 160px;
        }

        .rental-price {
            font-size: 20px;
            font-weight: 700;
            color: #222;
        }
        .rental-price span {
            font-size: 12px;
            font-weight: 400;
            color: #717171;
            display: block;
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-confirmed  { background: #e8f5e9; color: #1b5e20; }
        .badge-pending    { background: #fff8e1; color: #856404; }
        .badge-cancelled  { background: #fdecea; color: #b71c1c; }

        /* Cancel button */
        .btn-cancel {
            background: transparent;
            border: 1.5px solid #ff385c;
            color: #ff385c;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-cancel:hover {
            background: #ff385c;
            color: #fff;
        }

        /* ── Confirm Cancel Modal ── */
        .cancel-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9000;
            align-items: center;
            justify-content: center;
        }
        .cancel-modal-overlay.open { display: flex; }
        .cancel-modal {
            background: #fff;
            border-radius: 20px;
            padding: 36px;
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
        .cancel-modal .modal-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .cancel-modal h3 { font-size: 20px; margin: 0 0 10px; }
        .cancel-modal p  { color: #717171; margin: 0 0 28px; font-size: 15px; }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn-modal-keep {
            padding: 12px 26px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            background: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-modal-keep:hover { background: #f5f5f5; }
        .btn-modal-cancel {
            padding: 12px 26px;
            border: none;
            border-radius: 10px;
            background: #ff385c;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-modal-cancel:hover { background: #e31c5f; }

        @media (max-width: 640px) {
            .rental-card { flex-direction: column; }
            .rental-img  { width: 100%; height: 180px; }
            .rental-meta { flex-direction: row; align-items: center; padding: 0 20px 20px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="stays-nav">
    <a class="stays-logo" href="index.php">StayHub</a>
    <a class="back-link" href="index.php"><i class="fas fa-arrow-left"></i> Back to listings</a>
</nav>

<!-- Page Header -->
<div class="page-header">
    <h1>My Stays</h1>
    <p>All your reservations in one place</p>
</div>

<!-- Flash Messages -->
<?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <strong>Booking cancelled.</strong> Your reservation has been successfully cancelled.
    </div>
<?php elseif (isset($_GET['success']) && $_GET['success'] === 'paid'): ?>
    <div class="alert alert-success" style="background: #e6fff1; color: #1d643b; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle"></i>
        <strong>Payment successful!</strong> Your reservation is confirmed.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php
            $errMap = [
                'already_cancelled' => 'This booking was already cancelled.',
                'notfound'          => 'Reservation not found.',
                'db'                => 'A database error occurred. Please try again.',
                'invalid'           => 'Invalid request.',
            ];
            echo $errMap[$_GET['error']] ?? 'Something went wrong.';
        ?>
    </div>
<?php endif; ?>

<!-- Content -->
<div class="stays-list">
    <?php if (empty($rentals)): ?>
        <div class="empty-state">
            <span class="empty-icon">🏡</span>
            <h3>No stays yet</h3>
            <p>Time to plan your next adventure!</p>
            <a href="index.php" class="btn-explore">Explore listings</a>
        </div>
    <?php else: ?>
        <?php foreach ($rentals as $rental):
            $status      = $rental['status'] ?? 'pending';
            $isCancelled = ($status === 'cancelled');
            $badgeClass  = ($status === 'confirmed') ? 'badge-confirmed'
                         : (($status === 'cancelled') ? 'badge-cancelled' : 'badge-pending');
            $badgeIcon   = ($status === 'confirmed') ? 'fa-check-circle'
                         : (($status === 'cancelled') ? 'fa-times-circle' : 'fa-clock');
            $statusLabel = ucfirst($status);

            // Image URL
            if (!empty($rental['image_url'])) {
                $imgSrc = (strpos($rental['image_url'], 'http') === 0)
                        ? $rental['image_url']
                        : 'img/uploads/' . $rental['image_url'];
            } else {
                $imgSrc = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=60';
            }

            // Dates
            $checkIn  = ($rental['check_in']  instanceof DateTime) ? $rental['check_in']->format('d M Y')  : date('d M Y', strtotime($rental['check_in']));
            $checkOut = ($rental['check_out'] instanceof DateTime) ? $rental['check_out']->format('d M Y') : date('d M Y', strtotime($rental['check_out']));
        ?>
        <div class="rental-card <?php echo $isCancelled ? 'cancelled' : ''; ?>">

            <img class="rental-img" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($rental['title']); ?>">

            <div class="rental-body">
                <h3 class="rental-title"><?php echo htmlspecialchars($rental['title']); ?></h3>
                <p class="rental-location"><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($rental['location']); ?></p>
                <div class="rental-dates">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo $checkIn; ?> &rarr; <?php echo $checkOut; ?>
                </div>
            </div>

            <div class="rental-meta">
                <div>
                    <div class="rental-price">
                        <?php echo number_format($rental['total_price'], 0); ?> MAD
                        <span>total paid</span>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:10px;">
                    <span class="status-badge <?php echo $badgeClass; ?>">
                        <i class="fas <?php echo $badgeIcon; ?>"></i>
                        <?php echo $statusLabel; ?>
                    </span>

                    <?php if ($status === 'pending'): ?>
                        <a href="payment.php?id=<?php echo $rental['id']; ?>" 
                           class="btn-explore" 
                           style="padding: 8px 18px; font-size: 13px;"
                           onclick="this.innerHTML='<i class='fas fa-spinner fa-spin'></i> Loading…'; this.style.pointerEvents='none';">
                            <i class="fas fa-credit-card"></i> Pay Now
                        </a>
                        <button class="btn-cancel" onclick="openCancelModal(<?php echo (int)$rental['id']; ?>, '<?php echo htmlspecialchars(addslashes($rental['title'])); ?>')">
                            <i class="fas fa-times"></i> Cancel booking
                        </button>
                    <?php elseif ($status === 'confirmed'): ?>
                        <a href="receipt.php?id=<?php echo $rental['id']; ?>" class="btn-explore" style="background: #222; padding: 8px 18px; font-size: 13px;">
                            <i class="fas fa-file-invoice"></i> View Receipt
                        </a>
                    <?php endif; ?>
                    <?php
                    // Feature 8: Review button — show for confirmed OR pending after checkout date
                    if ($status !== 'cancelled') {
                        $checkoutDate = ($rental['check_out'] instanceof DateTime)
                            ? $rental['check_out']
                            : new DateTime($rental['check_out']);
                        $checkoutDate->setTime(0,0,0);
                        $todayDate = new DateTime(); $todayDate->setTime(0,0,0);
                        $canLeaveReview = ($checkoutDate <= $todayDate);
                        $revChkSql  = "SELECT id FROM reviews WHERE reservation_id = ? AND user_id = ?";
                        $revChkStmt = sqlsrv_query($conn, $revChkSql, [(int)$rental['id'], $user_id]);
                        $hasReviewed = ($revChkStmt && sqlsrv_fetch_array($revChkStmt, SQLSRV_FETCH_ASSOC));
                    }
                    ?>
                    <?php if ($status !== 'cancelled' && $canLeaveReview && !$hasReviewed): ?>
                    <a href="listing.php?id=<?php echo $rental['listing_id']; ?>#reviews"
                       class="btn-explore"
                       style="background:#ff385c; padding:8px 18px; font-size:13px;">
                        <i class="fas fa-star"></i> Leave a Review
                    </a>
                    <?php elseif ($status !== 'cancelled' && isset($hasReviewed) && $hasReviewed): ?>
                    <span style="font-size:12px;color:#717171;font-style:italic;"><i class="fas fa-check"></i> Reviewed</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Cancel Confirmation Modal -->
<div class="cancel-modal-overlay" id="cancelModalOverlay">
    <div class="cancel-modal">
        <div class="modal-icon">⚠️</div>
        <h3>Cancel this booking?</h3>
        <p id="cancelModalText">Are you sure you want to cancel your stay?</p>
        <form method="POST" action="api/cancel-booking.php" id="cancelForm">
            <input type="hidden" name="reservation_id" id="cancelReservationId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-modal-keep" onclick="closeCancelModal()">Keep it</button>
                <button type="submit" class="btn-modal-cancel">Yes, cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCancelModal(reservationId, title) {
        document.getElementById('cancelReservationId').value = reservationId;
        document.getElementById('cancelModalText').textContent =
            'Are you sure you want to cancel your stay at "' + title + '"? This cannot be undone.';
        document.getElementById('cancelModalOverlay').classList.add('open');
    }

    function closeCancelModal() {
        document.getElementById('cancelModalOverlay').classList.remove('open');
    }

    // Close when clicking outside the modal box
    document.getElementById('cancelModalOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
</script>

</body>
</html>

