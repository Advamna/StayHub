<?php
// ── receipt.php ─────────────────────────────────────────────────────
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reservation_id) {
    header('Location: my-rentals.php');
    exit;
}

// ── Fetch reservation + listing + host details ────────────────────
$sql = "SELECT
            r.*,
            l.title         AS listing_title,
            l.location      AS listing_location,
            l.price         AS nightly_price,
            host.name       AS host_name,
            host.email      AS host_email,
            host.phone      AS host_phone,
            p.id            AS payment_id,
            p.amount        AS payment_amount,
            p.payment_method,
            p.payment_status,
            p.created_at    AS payment_date,
            inv.invoice_number,
            inv.tax_amount,
            inv.total_amount AS invoice_total,
            inv.issued_at
        FROM reservations r
        JOIN listings l          ON r.listing_id   = l.id
        JOIN users host          ON l.user_id       = host.id
        LEFT JOIN payments p     ON p.reservation_id = r.id
        LEFT JOIN invoices inv   ON inv.payment_id   = p.id
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'confirmed'";

$stmt = sqlsrv_query($conn, $sql, [$reservation_id, $user_id]);
if (!$stmt) {
    error_log('receipt.php query error: ' . print_r(sqlsrv_errors(), true));
    header('Location: my-rentals.php?error=server');
    exit;
}
$res = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$res) {
    // Try fetching without confirmed status check — maybe status isn't confirmed yet
    $stmt2 = sqlsrv_query($conn, str_replace("AND r.status = 'confirmed'", "", $sql), [$reservation_id, $user_id]);
    if ($stmt2) $res = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    if (!$res) {
        header('Location: my-rentals.php?error=notfound');
        exit;
    }
    // Log that receipt was accessed for non-confirmed booking
    error_log("receipt.php: reservation $reservation_id accessed by user $user_id — status may not be confirmed");
}

// ── Date calculations ────────────────────────────────────────────────
$d1    = ($res['check_in']  instanceof DateTime) ? $res['check_in']  : new DateTime($res['check_in']);
$d2    = ($res['check_out'] instanceof DateTime) ? $res['check_out'] : new DateTime($res['check_out']);
$nights = max(1, $d1->diff($d2)->days);

$nightly_price  = (float)($res['nightly_price'] ?? 0);
$base_amount    = $nightly_price > 0 ? $nightly_price * $nights : (float)($res['total_price'] ?? 0);
$cleaning_fee   = 150.00;
$service_fee    = round($base_amount * 0.10, 2);
$tax_amount     = (float)($res['tax_amount']    ?? round($base_amount * 0.20, 2));
$total_amount   = (float)($res['invoice_total'] ?? $res['payment_amount'] ?? $res['total_price'] ?? 0);
$payment_method = $res['payment_method'] ?? 'Card';
$payment_status = $res['payment_status'] ?? 'completed';

// ── Warn flag (payment log missing but reservation is confirmed) ─────
$showPaymentWarn = isset($_GET['warn']) && $_GET['warn'] === 'payment_log';
$hasPaymentData  = !empty($res['payment_id']);

// ── Receipt & Invoice numbers ────────────────────────────────────────
// Use real invoice_number from DB if available, otherwise fallback
$invoice_number = $res['invoice_number'] ?? ('INV-' . date('Y') . '-' . str_pad($reservation_id, 5, '0', STR_PAD_LEFT));
$receipt_number = 'REC-' . date('Y') . '-' . str_pad($reservation_id, 6, '0', STR_PAD_LEFT);

// ── Payment date ─────────────────────────────────────────────────────
if (!empty($res['payment_date'])) {
    $pDateObj = ($res['payment_date'] instanceof DateTime) ? $res['payment_date'] : new DateTime($res['payment_date']);
    $payment_date_str = $pDateObj->format('d/m/Y H:i');
} else {
    $payment_date_str = date('d/m/Y H:i');
}

$issued_date = !empty($res['issued_at'])
    ? (($res['issued_at'] instanceof DateTime ? $res['issued_at'] : new DateTime($res['issued_at']))->format('d/m/Y'))
    : date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?php echo htmlspecialchars($receipt_number); ?> | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f5f7;
            color: #222;
            min-height: 100vh;
        }

        /* ── Top nav ── */
        .receipt-nav {
            background: #fff;
            border-bottom: 1px solid #ebebeb;
            padding: 14px 8%;
            display: flex; justify-content: space-between; align-items: center;
        }
        .stays-logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .nav-actions { display: flex; gap: 12px; }
        .btn-outline {
            padding: 9px 18px; border: 1.5px solid #ddd; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer; background: #fff;
            color: #444; text-decoration: none; display: flex; align-items: center; gap: 6px;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-outline:hover { border-color: #ff385c; color: #ff385c; }
        .btn-primary-sm {
            padding: 9px 18px; background: #ff385c; border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer; color: #fff;
            display: flex; align-items: center; gap: 6px; text-decoration: none;
            transition: background 0.2s;
        }
        .btn-primary-sm:hover { background: #e31c5f; }

        /* ── Page wrapper ── */
        .receipt-page {
            max-width: 820px; margin: 36px auto; padding: 0 20px 60px;
        }

        /* ── Status banner ── */
        .status-banner {
            background: linear-gradient(135deg, #ff385c 0%, #c2185b 100%);
            color: #fff; border-radius: 16px;
            padding: 28px 32px; margin-bottom: 28px;
            display: flex; align-items: center; gap: 20px;
        }
        .status-icon {
            width: 56px; height: 56px; background: rgba(255,255,255,0.2);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .status-title { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
        .status-sub   { font-size: 14px; opacity: 0.88; }

        /* ── Cards ── */
        .receipt-card {
            background: #fff; border-radius: 16px; border: 1px solid #e8e8e8;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px; overflow: hidden;
        }
        .card-header {
            padding: 18px 24px; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; gap: 10px;
        }
        .card-header i  { color: #ff385c; font-size: 15px; }
        .card-header h3 { font-size: 15px; font-weight: 700; color: #222; }
        .card-header .badge {
            margin-left: auto; padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.4px;
        }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-info    { background: #e3f2fd; color: #1565c0; }
        .card-body { padding: 20px 24px; }

        /* ── Key-value rows ── */
        .kv-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
        }
        .kv-item .kv-label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .kv-item .kv-value { font-size: 14px; color: #222; font-weight: 500; }

        /* ── Price table ── */
        .price-table { width: 100%; }
        .price-row {
            display: flex; justify-content: space-between;
            padding: 10px 0; font-size: 14px; color: #444;
            border-bottom: 1px solid #f5f5f5;
        }
        .price-row:last-child { border-bottom: none; }
        .price-row.total {
            font-size: 17px; font-weight: 800; color: #222;
            border-top: 2px solid #e8e8e8; border-bottom: none;
            padding-top: 14px; margin-top: 4px;
        }
        .price-row.tax { color: #888; font-size: 13px; }

        /* ── Payment method chip ── */
        .payment-chip {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f5f5f5; border-radius: 8px; padding: 10px 14px;
            font-size: 14px; font-weight: 600; color: #333; margin-top: 6px;
        }
        .payment-chip i { color: #ff385c; }

        /* ── Two-col layout for host/guest ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) {
            .two-col { grid-template-columns: 1fr; }
            .kv-grid  { grid-template-columns: 1fr; }
            .status-banner { flex-direction: column; text-align: center; }
        }

        /* ── Print styles ── */
        @media print {
            body { background: #fff; }
            .receipt-nav .nav-actions,
            .no-print { display: none !important; }
            .receipt-card { box-shadow: none; border: 1px solid #ccc; }
            .status-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav class="receipt-nav">
    <a href="index.php" class="stays-logo">StayHub</a>
    <div class="nav-actions no-print">
        <button onclick="window.print()" class="btn-outline"><i class="fas fa-print"></i> Print</button>
        <a href="my-rentals.php" class="btn-primary-sm"><i class="fas fa-home"></i> My Stays</a>
    </div>
</nav>

<div class="receipt-page">

    <!-- ── Status Banner ── -->
    <?php if ($showPaymentWarn && !$hasPaymentData): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:14px 20px;margin-bottom:20px;font-size:14px;color:#92400e;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-exclamation-triangle" style="color:#d97706;"></i>
        <span>Your booking is confirmed, but we couldn't log the payment record. Your stay is guaranteed — please contact support if needed.</span>
    </div>
    <?php endif; ?>
    <div class="status-banner">
        <div class="status-icon"><i class="fas fa-check"></i></div>
        <div>
            <div class="status-title">Payment Confirmed!</div>
            <div class="status-sub">
                Your stay at <strong><?php echo htmlspecialchars($res['listing_title']); ?></strong> is booked.
                Receipt <strong><?php echo htmlspecialchars($receipt_number); ?></strong>
                <?php if ($hasPaymentData): ?> &bull; Invoice <strong><?php echo htmlspecialchars($invoice_number); ?></strong><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Booking Details ── -->
    <div class="receipt-card">
        <div class="card-header">
            <i class="fas fa-bed"></i>
            <h3>Booking Details</h3>
            <span class="badge badge-success"><i class="fas fa-circle" style="font-size:7px;"></i> Confirmed</span>
        </div>
        <div class="card-body">
            <div class="kv-grid" style="margin-bottom:16px;">
                <div class="kv-item">
                    <div class="kv-label">Property</div>
                    <div class="kv-value"><?php echo htmlspecialchars($res['listing_title']); ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Location</div>
                    <div class="kv-value"><?php echo htmlspecialchars($res['listing_location']); ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Check-in</div>
                    <div class="kv-value"><?php echo $d1->format('D, d M Y'); ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Check-out</div>
                    <div class="kv-value"><?php echo $d2->format('D, d M Y'); ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Duration</div>
                    <div class="kv-value"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Guests</div>
                    <div class="kv-value"><?php echo (int)$res['guests']; ?> guest<?php echo $res['guests'] > 1 ? 's' : ''; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Price Breakdown ── -->
    <div class="receipt-card">
        <div class="card-header">
            <i class="fas fa-receipt"></i>
            <h3>Price Breakdown</h3>
            <span class="badge badge-info"><?php echo htmlspecialchars($invoice_number); ?></span>
        </div>
        <div class="card-body">
            <div class="price-table">
                <div class="price-row">
                    <span><?php echo number_format($nightly_price, 0); ?> MAD &times; <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                    <span><?php echo number_format($base_amount, 2, '.', ' '); ?> MAD</span>
                </div>
                <div class="price-row">
                    <span>Cleaning fee</span>
                    <span><?php echo number_format($cleaning_fee, 2, '.', ' '); ?> MAD</span>
                </div>
                <div class="price-row">
                    <span>Service fee (10%)</span>
                    <span><?php echo number_format($service_fee, 2, '.', ' '); ?> MAD</span>
                </div>
                <div class="price-row tax">
                    <span>VAT (20%)</span>
                    <span><?php echo number_format($tax_amount, 2, '.', ' '); ?> MAD</span>
                </div>
                <div class="price-row total">
                    <span>Total Paid</span>
                    <span><?php echo number_format($total_amount, 2, '.', ' '); ?> MAD</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Payment Info ── -->
    <div class="receipt-card">
        <div class="card-header">
            <i class="fas fa-credit-card"></i>
            <h3>Payment Information</h3>
            <span class="badge badge-success">Paid</span>
        </div>
        <div class="card-body">
            <div class="kv-grid">
                <div class="kv-item">
                    <div class="kv-label">Payment Method</div>
                    <div class="kv-value">
                        <div class="payment-chip">
                            <i class="fas fa-credit-card"></i>
                            <?php echo htmlspecialchars($payment_method); ?>
                        </div>
                    </div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Payment Status</div>
                    <div class="kv-value" style="color:#2e7d32; font-weight:700; text-transform:capitalize;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(ucfirst($payment_status)); ?>
                    </div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Payment Date</div>
                    <div class="kv-value"><?php echo $payment_date_str; ?></div>
                </div>
                <div class="kv-item">
                    <div class="kv-label">Invoice Issued</div>
                    <div class="kv-value"><?php echo $issued_date; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Guest & Host ── -->
    <div class="two-col">
        <div class="receipt-card">
            <div class="card-header">
                <i class="fas fa-user"></i>
                <h3>Guest</h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom:10px;">
                    <div class="kv-item"><div class="kv-label">Name</div><div class="kv-value"><?php echo htmlspecialchars($res['guest_name']); ?></div></div>
                </div>
                <div style="margin-bottom:10px;">
                    <div class="kv-item"><div class="kv-label">Email</div><div class="kv-value"><?php echo htmlspecialchars($res['guest_email']); ?></div></div>
                </div>
                <div>
                    <div class="kv-item"><div class="kv-label">Phone</div><div class="kv-value"><?php echo htmlspecialchars($res['guest_phone']); ?></div></div>
                </div>
            </div>
        </div>
        <div class="receipt-card">
            <div class="card-header">
                <i class="fas fa-home"></i>
                <h3>Host</h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom:10px;">
                    <div class="kv-item"><div class="kv-label">Name</div><div class="kv-value"><?php echo htmlspecialchars($res['host_name']); ?></div></div>
                </div>
                <div style="margin-bottom:10px;">
                    <div class="kv-item"><div class="kv-label">Email</div><div class="kv-value"><?php echo htmlspecialchars($res['host_email']); ?></div></div>
                </div>
                <div>
                    <div class="kv-item"><div class="kv-label">Phone</div><div class="kv-value"><?php echo htmlspecialchars($res['host_phone'] ?? '—'); ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Footer ── -->
    <div style="text-align:center; margin-top:24px; font-size:13px; color:#aaa;" class="no-print">
        <i class="fas fa-shield-alt"></i> This receipt is your official proof of payment. Keep it for your records.
        &nbsp;&bull;&nbsp; <a href="my-rentals.php" style="color:#ff385c;">View all your stays</a>
    </div>

</div>
</body>
</html>
