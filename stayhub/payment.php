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

$date1 = ($reservation['check_in'] instanceof DateTime) ? $reservation['check_in'] : new DateTime($reservation['check_in']);
$date2 = ($reservation['check_out'] instanceof DateTime) ? $reservation['check_out'] : new DateTime($reservation['check_out']);
$interval = $date1->diff($date2);
$days = $interval->days > 0 ? $interval->days : 1;

$total_price = $reservation['total_price'];
$cleaning_fee = 150;
$service_fee = round($total_price * 0.1);
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7f7f8; margin: 0; color: #222; opacity: 1; transition: opacity 0.1s; }
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
        .form-group input {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 15px; box-sizing: border-box; transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #ff385c; }
        .form-group input.input-error { border-color: #ff385c; background: #fff5f7; }
        .field-hint { font-size: 11px; color: #aaa; margin-top: 4px; display: none; }
        .field-hint.visible { display: block; color: #ff385c; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn-pay { width: 100%; background: #ff385c; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 15px; transition: 0.2s; }
        .btn-pay:hover { background: #e31c5f; }
        .btn-pay:disabled { background: #ccc; cursor: not-allowed; }
        
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
        <form action="api/process-payment.php" method="POST" id="payment-form">
            <input type="hidden" name="reservation_id" value="<?php echo (int)$reservation_id; ?>">

            <!-- Name on Card — letters only -->
            <div class="form-group">
                <label>Name on Card</label>
                <input type="text" id="card_name" name="card_name"
                       placeholder="John Doe" required autocomplete="cc-name">
                <span class="field-hint" id="hint-name">Only letters and spaces allowed.</span>
            </div>

            <!-- Card Number — digits only, 16 digits, formatted as 0000 0000 0000 0000 -->
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" id="card_number" name="card_number"
                       placeholder="0000 0000 0000 0000"
                       maxlength="19"
                       inputmode="numeric"
                       autocomplete="cc-number"
                       required>
                <span class="field-hint" id="hint-card">Must be exactly 16 digits.</span>
            </div>

            <div class="form-row">
                <!-- Expiry — MM/YY format, auto-slash -->
                <div class="form-group">
                    <label>Expiration Date</label>
                    <input type="text" id="card_exp" name="card_exp"
                           placeholder="MM/YY"
                           maxlength="5"
                           inputmode="numeric"
                           autocomplete="cc-exp"
                           required>
                    <span class="field-hint" id="hint-exp">Must be in MM/YY format.</span>
                </div>

                <!-- CVV — digits only, exactly 3 -->
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" id="card_cvv" name="card_cvv"
                           placeholder="123"
                           maxlength="3"
                           inputmode="numeric"
                           autocomplete="cc-csc"
                           required>
                    <span class="field-hint" id="hint-cvv">Must be exactly 3 digits.</span>
                </div>
            </div>

            <button type="submit" class="btn-pay" id="pay-btn">
                Pay <?php echo number_format($total_price, 0); ?> MAD
            </button>
        </form>
    </div>

    <div class="checkout-box">
        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="summary-img" alt="Listing"
     onerror="this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=60'">
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

<script>
// ── Card Number: digits only, auto-space every 4, exactly 16 digits ──
const cardInput = document.getElementById('card_number');
cardInput.addEventListener('keydown', function(e) {
    const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Home','End'];
    if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
        e.preventDefault(); // Block anything that isn't a digit or control key
    }
});
cardInput.addEventListener('input', function() {
    // Strip non-digits, cap at 16 digits, insert spaces every 4
    let digits = this.value.replace(/\D/g, '').slice(0, 16);
    this.value = digits.replace(/(.{4})/g, '$1 ').trim();
});
cardInput.addEventListener('blur', function() {
    const digits = this.value.replace(/\D/g, '');
    const hint   = document.getElementById('hint-card');
    if (digits.length !== 16) {
        this.classList.add('input-error');
        hint.classList.add('visible');
    } else {
        this.classList.remove('input-error');
        hint.classList.remove('visible');
    }
});

// ── CVV: digits only, exactly 3 ──
const cvvInput = document.getElementById('card_cvv');
cvvInput.addEventListener('keydown', function(e) {
    const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
    if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
        e.preventDefault();
    }
});
cvvInput.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 3);
});
cvvInput.addEventListener('blur', function() {
    const hint = document.getElementById('hint-cvv');
    if (this.value.length !== 3) {
        this.classList.add('input-error');
        hint.classList.add('visible');
    } else {
        this.classList.remove('input-error');
        hint.classList.remove('visible');
    }
});

// ── Expiry: digits only, auto-insert slash after MM, format MM/YY ──
const expInput = document.getElementById('card_exp');
expInput.addEventListener('keydown', function(e) {
    const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
    if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
        e.preventDefault();
    }
});
expInput.addEventListener('input', function(e) {
    let digits = this.value.replace(/\D/g, '').slice(0, 4);
    if (digits.length >= 3) {
        this.value = digits.slice(0, 2) + '/' + digits.slice(2);
    } else if (digits.length === 2 && this.value.length === 2) {
        // Auto-insert slash when user finishes typing month
        this.value = digits + '/';
    } else {
        this.value = digits;
    }
});
expInput.addEventListener('blur', function() {
    const hint  = document.getElementById('hint-exp');
    const parts = this.value.split('/');
    const valid = parts.length === 2 && parts[0].length === 2 && parts[1].length === 2
                  && parseInt(parts[0]) >= 1 && parseInt(parts[0]) <= 12;
    if (!valid) {
        this.classList.add('input-error');
        hint.classList.add('visible');
    } else {
        this.classList.remove('input-error');
        hint.classList.remove('visible');
    }
});

// ── Name on Card: letters and spaces only ──
const nameInput = document.getElementById('card_name');
nameInput.addEventListener('keydown', function(e) {
    const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End',' '];
    if (!allowed.includes(e.key) && !/^[a-zA-Z]$/.test(e.key)) {
        e.preventDefault();
    }
});

// ── Form submit: final validation + loading state ──
document.getElementById('payment-form').addEventListener('submit', function(e) {
    const cardDigits = cardInput.value.replace(/\D/g, '');
    const cvv        = cvvInput.value;
    const expParts   = expInput.value.split('/');
    const expValid   = expParts.length === 2 && expParts[0].length === 2 && expParts[1].length === 2
                       && parseInt(expParts[0]) >= 1 && parseInt(expParts[0]) <= 12;

    if (cardDigits.length !== 16 || cvv.length !== 3 || !expValid) {
        e.preventDefault();
        if (cardDigits.length !== 16) { cardInput.classList.add('input-error'); document.getElementById('hint-card').classList.add('visible'); }
        if (cvv.length !== 3)         { cvvInput.classList.add('input-error');  document.getElementById('hint-cvv').classList.add('visible'); }
        if (!expValid)                { expInput.classList.add('input-error');  document.getElementById('hint-exp').classList.add('visible'); }
        return;
    }
    // All valid — show loading state immediately so it feels instant
    const payBtn = document.getElementById('pay-btn');
    payBtn.disabled = true;
    payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
    payBtn.style.opacity = '0.85';
});

// Show page content immediately (avoid flash of unstyled content)
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.opacity = '1';
});
</script>

</body>
</html>

