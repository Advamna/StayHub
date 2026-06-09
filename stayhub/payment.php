<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_GET['id'] ?? null;

if (!$reservation_id) {
    header('Location: my-rentals.php');
    exit;
}

$sql = "SELECT r.*, l.title, l.location, i.image_url 
        FROM reservations r
        JOIN listings l ON r.listing_id = l.id
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'";

$stmt = sqlsrv_query($conn, $sql, array($reservation_id, $user_id));
$reservation = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$reservation) {
    header('Location: my-rentals.php');
    exit;
}

// Calculate breakdown
// Calculate breakdown
$date1 = ($reservation['check_in'] instanceof DateTime) ? $reservation['check_in'] : new DateTime($reservation['check_in']);
$date2 = ($reservation['check_out'] instanceof DateTime) ? $reservation['check_out'] : new DateTime($reservation['check_out']);
$interval = $date1->diff($date2);
$days = $interval->days > 0 ? $interval->days : 1;

$total_price = $reservation['total_price'];
$cleaning_fee = 150; // Mock cleaning fee
$service_fee = round($total_price * 0.1); // 10% service fee
$base_price = $total_price - $cleaning_fee - $service_fee;

$imgSrc = !empty($reservation['image_url']) ? 
    (strpos($reservation['image_url'], 'http') === 0 ? $reservation['image_url'] : 'uploads/' . $reservation['image_url']) 
    : 'img/placeholder.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7f7f8; margin: 0; color: #222; }
        .stays-nav { background: #fff; border-bottom: 1px solid #ebebeb; padding: 16px 8%; display: flex; justify-content: space-between; align-items: center; }
        .stays-logo { font-size: 22px; font-weight: 700; color: #ff385c; text-decoration: none; }
        .back-link { font-size: 14px; color: #717171; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .back-link:hover { color: #222; }
        
        .checkout-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
        
        .checkout-box { background: #fff; padding: 30px; border-radius: 15px; border: 1px solid #ebebeb; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .checkout-box h2 { margin-top: 0; margin-bottom: 25px; font-size: 22px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn-pay { width: 100%; background: #ff385c; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 15px; transition: 0.2s; }
        .btn-pay:hover { background: #e31c5f; }
        
        .summary-img { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; }
        .summary-title { font-size: 18px; font-weight: 600; margin: 0 0 5px; }
        .summary-location { color: #717171; font-size: 14px; margin: 0 0 20px; }
        .summary-dates { background: #f7f7f8; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        
        .price-breakdown { font-size: 15px; }
        .price-item { display: flex; justify-content: space-between; padding: 8px 0; color: #444; }
        .price-total { display: flex; justify-content: space-between; padding: 15px 0 0; margin-top: 10px; border-top: 1px solid #ddd; font-weight: 700; font-size: 18px; color: #222; }
    </style>
</head>
<body>

<nav class="stays-nav">
    <a class="stays-logo" href="index.php">StayHub</a>
    <a class="back-link" href="my-rentals.php"><i class="fas fa-arrow-left"></i> Cancel Payment</a>
</nav>

<div class="checkout-container">
    <div class="checkout-box">
        <h2>Payment Details</h2>
        <p style="color: #717171; font-size: 14px; margin-top: -15px; margin-bottom: 20px;">
            <i class="fas fa-lock"></i> All transactions are secure and encrypted. (Mock Payment)
        </p>
        <form action="api/process-payment.php" method="POST">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
            <div class="form-group">
                <label>Name on Card</label>
                <input type="text" name="card_name" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expiration Date</label>
                    <input type="text" name="card_exp" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" name="card_cvv" placeholder="123" maxlength="3" required>
                </div>
            </div>
            <button type="submit" class="btn-pay">Pay <?php echo number_format($total_price, 0); ?> MAD</button>
        </form>
    </div>

    <div class="checkout-box">
        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="summary-img" alt="Listing">
        <h3 class="summary-title"><?php echo htmlspecialchars($reservation['title']); ?></h3>
        <p class="summary-location"><?php echo htmlspecialchars($reservation['location']); ?></p>
        
        <div class="summary-dates">
            <div><strong>Check-in:</strong> <?php echo ($reservation['check_in'] instanceof DateTime) ? $reservation['check_in']->format('M d, Y') : date('M d, Y', strtotime($reservation['check_in'])); ?></div>
            <div style="margin-top:5px;"><strong>Check-out:</strong> <?php echo ($reservation['check_out'] instanceof DateTime) ? $reservation['check_out']->format('M d, Y') : date('M d, Y', strtotime($reservation['check_out'])); ?></div>
        </div>

        <div class="price-breakdown">
            <div class="price-item">
                <span><?php echo number_format($daily_price ?? ($base_price / $days), 0); ?> MAD x <?php echo $days; ?> nights</span>
                <span><?php echo number_format($base_price, 0); ?> MAD</span>
            </div>
            <div class="price-item">
                <span>Cleaning fee</span>
                <span><?php echo number_format($cleaning_fee, 0); ?> MAD</span>
            </div>
            <div class="price-item">
                <span>StayHub service fee</span>
                <span><?php echo number_format($service_fee, 0); ?> MAD</span>
            </div>
            <div class="price-total">
                <span>Total (MAD)</span>
                <span><?php echo number_format($total_price, 0); ?> MAD</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
