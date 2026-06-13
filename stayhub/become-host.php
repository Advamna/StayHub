<?php
session_start();
require_once 'config.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// If already a host, skip to dashboard
$user_id  = $_SESSION['user_id'];
$chkSql   = "SELECT is_host FROM users WHERE id = ?";
$chkStmt  = sqlsrv_query($conn, $chkSql, [$user_id]);
$chkRow   = sqlsrv_fetch_array($chkStmt, SQLSRV_FETCH_ASSOC);
if ($chkRow && $chkRow['is_host'] == 1) {
    header("Location: host-dashboard.php");
    exit();
}

// Handle POST — user accepted and clicked confirm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accepted'])) {
    $sql   = "UPDATE users SET is_host = 1 WHERE id = ?";
    $stmt  = sqlsrv_query($conn, $sql, [$user_id]);
    if ($stmt) {
        $_SESSION['is_host'] = 1;
        header("Location: host-dashboard.php");
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Host – StayHub</title>
    <meta name="description" content="Join StayHub as a host and start earning by renting your property.">
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            margin: 0;
            background: #f7f7f8;
            color: #222;
        }

        /* ── Navbar ── */
        .bh-nav {
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
        .bh-logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .bh-nav a.back { font-size: 14px; color: #717171; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .bh-nav a.back:hover { color: #222; }

        /* ── Hero ── */
        .bh-hero {
            background: linear-gradient(135deg, #ff385c 0%, #bd1e59 100%);
            color: #fff;
            text-align: center;
            padding: 80px 8% 70px;
        }
        .bh-hero .icon { font-size: 56px; margin-bottom: 20px; }
        .bh-hero h1 { font-size: 38px; font-weight: 800; margin: 0 0 14px; }
        .bh-hero p  { font-size: 18px; opacity: 0.9; max-width: 560px; margin: 0 auto; line-height: 1.6; }

        /* ── Perks grid ── */
        .bh-perks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            padding: 50px 8%;
        }
        .perk-card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 16px;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .perk-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.09); }
        .perk-card .perk-icon { font-size: 36px; margin-bottom: 14px; }
        .perk-card h3 { font-size: 16px; font-weight: 700; margin: 0 0 8px; }
        .perk-card p  { font-size: 14px; color: #717171; margin: 0; line-height: 1.5; }

        /* ── CTA ── */
        .bh-cta {
            text-align: center;
            padding: 20px 8% 70px;
        }
        .btn-become {
            background: #ff385c;
            color: #fff;
            border: none;
            padding: 16px 48px;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            box-shadow: 0 4px 16px rgba(255,56,92,0.35);
        }
        .btn-become:hover { background: #e31c5f; transform: translateY(-2px); }

        /* ── Rules Modal Overlay ── */
        .rules-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .rules-overlay.open { display: flex; }

        .rules-modal {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
            overflow: hidden;
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .rules-header {
            padding: 28px 28px 20px;
            border-bottom: 1px solid #ebebeb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .rules-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
        .rules-close {
            background: none;
            border: none;
            font-size: 26px;
            cursor: pointer;
            color: #717171;
            line-height: 1;
            padding: 0;
            transition: color 0.15s;
        }
        .rules-close:hover { color: #222; }

        .rules-body {
            padding: 24px 28px;
            overflow-y: auto;
            flex: 1;
        }
        .rules-body p.intro {
            color: #555;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 24px;
        }

        .rule-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 16px 18px;
            border-radius: 12px;
            background: #f9f9f9;
            border-left: 4px solid #ff385c;
        }
        .rule-item .rule-icon { font-size: 22px; flex-shrink: 0; margin-top: 2px; }
        .rule-item .rule-text h4 { margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #222; }
        .rule-item .rule-text p  { margin: 0; font-size: 13px; color: #555; line-height: 1.5; }

        .warning-box {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin: 4px 0 24px;
        }
        .warning-box .wi { font-size: 20px; flex-shrink: 0; }
        .warning-box p   { margin: 0; font-size: 13px; color: #795548; line-height: 1.5; }
        .warning-box strong { color: #5d4037; }

        .agree-check {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        .agree-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #ff385c;
            flex-shrink: 0;
            margin-top: 2px;
            cursor: pointer;
        }

        .rules-footer {
            padding: 20px 28px;
            border-top: 1px solid #ebebeb;
            display: flex;
            gap: 12px;
            flex-shrink: 0;
        }
        .btn-decline {
            flex: 1;
            padding: 13px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            background: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-decline:hover { background: #f5f5f5; }

        .btn-accept {
            flex: 2;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #ff385c;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
        }
        .btn-accept:hover:not(:disabled) { background: #e31c5f; }
        .btn-accept:disabled { opacity: 0.45; cursor: not-allowed; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="bh-nav">
    <a class="bh-logo" href="index.php">StayHub</a>
    <a class="back" href="index.php"><i class="fas fa-arrow-left"></i> Back to home</a>
</nav>

<!-- Hero -->
<div class="bh-hero">
    <div class="icon">🏠</div>
    <h1>Become a StayHub Host</h1>
    <p>Share your space, earn extra income, and meet travellers from around the world — all on your terms.</p>
</div>

<!-- Perks -->
<div class="bh-perks">
    <div class="perk-card">
        <div class="perk-icon">💰</div>
        <h3>Earn money</h3>
        <p>Set your own price and earn income from your unused space on your schedule.</p>
    </div>
    <div class="perk-card">
        <div class="perk-icon">🛡️</div>
        <h3>Host protection</h3>
        <p>Your listing is covered by StayHub's damage protection and 24/7 host support.</p>
    </div>
    <div class="perk-card">
        <div class="perk-icon">📅</div>
        <h3>You're in control</h3>
        <p>Decide when your space is available, who can book it, and for how long.</p>
    </div>
    <div class="perk-card">
        <div class="perk-icon">🌍</div>
        <h3>Global reach</h3>
        <p>Millions of travellers search StayHub every day — your listing is ready to be found.</p>
    </div>
</div>

<!-- CTA -->
<div class="bh-cta">
    <button class="btn-become" onclick="document.getElementById('rulesOverlay').classList.add('open')">
        Get started →
    </button>
</div>

<!-- ── Rules Modal ── -->
<div class="rules-overlay" id="rulesOverlay">
    <div class="rules-modal" role="dialog" aria-modal="true" aria-labelledby="rulesTitle">

        <div class="rules-header">
            <h2 id="rulesTitle">📋 Host Agreement</h2>
            <button class="rules-close" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>

        <div class="rules-body">
            <p class="intro">
                Before joining as a host you must read and agree to the following rules. 
                Violations may result in <strong>suspension or permanent removal</strong> of your account.
            </p>

            <div class="rule-item">
                <div class="rule-icon">✅</div>
                <div class="rule-text">
                    <h4>Accurate listings</h4>
                    <p>All information in your listing — photos, descriptions, amenities and pricing — must be truthful and up to date. Misleading content is strictly prohibited.</p>
                </div>
            </div>

            <div class="rule-item">
                <div class="rule-icon">🧹</div>
                <div class="rule-text">
                    <h4>Clean &amp; safe property</h4>
                    <p>Your space must be clean, well-maintained, and meet basic safety standards (smoke detectors, secure locks, etc.) before each guest arrival.</p>
                </div>
            </div>

            <div class="rule-item">
                <div class="rule-icon">💬</div>
                <div class="rule-text">
                    <h4>Timely communication</h4>
                    <p>Respond to booking requests and guest messages within 24 hours. Repeated failure to respond may result in your listing being hidden.</p>
                </div>
            </div>

            <div class="rule-item">
                <div class="rule-icon">🚫</div>
                <div class="rule-text">
                    <h4>No discrimination</h4>
                    <p>You must not refuse bookings based on race, religion, gender, nationality, disability, or sexual orientation. Violations lead to immediate ban.</p>
                </div>
            </div>

            <div class="rule-item">
                <div class="rule-icon">💳</div>
                <div class="rule-text">
                    <h4>Respect bookings &amp; payments</h4>
                    <p>Do not cancel confirmed bookings without a valid reason. Any disputes about payments must go through StayHub's official resolution process.</p>
                </div>
            </div>

            <div class="rule-item">
                <div class="rule-icon">📜</div>
                <div class="rule-text">
                    <h4>Local laws &amp; regulations</h4>
                    <p>You are responsible for complying with all local rental laws, licensing requirements, and tax obligations that apply to short-term rentals in your area.</p>
                </div>
            </div>

            <div class="warning-box">
                <div class="wi">⚠️</div>
                <p><strong>Account suspension & removal:</strong> Serious or repeated violations of any of the above rules may result in temporary suspension or <strong>permanent removal</strong> of your host account without prior notice.</p>
            </div>

            <label class="agree-check" for="agreeBox">
                <input type="checkbox" id="agreeBox" onchange="toggleAccept(this)">
                I have read and agree to all StayHub host rules and understand that violations may result in my account being banned or removed.
            </label>
        </div>

        <div class="rules-footer">
            <button class="btn-decline" onclick="closeModal()">Decline</button>
            <form method="POST" action="become-host.php" style="flex:2; display:flex;">
                <input type="hidden" name="accepted" value="1">
                <button type="submit" class="btn-accept" id="acceptBtn" disabled>
                    I agree — Become a Host
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    function closeModal() {
        document.getElementById('rulesOverlay').classList.remove('open');
        // Reset checkbox when closing
        document.getElementById('agreeBox').checked = false;
        document.getElementById('acceptBtn').disabled = true;
    }

    function toggleAccept(checkbox) {
        document.getElementById('acceptBtn').disabled = !checkbox.checked;
    }

    // Close on backdrop click
    document.getElementById('rulesOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

</body>
</html>