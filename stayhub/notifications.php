<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read when opening the page
$sql_update = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
sqlsrv_query($conn, $sql_update, [$user_id]);

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = sqlsrv_query($conn, $sql, [$user_id]);

$notifications = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $notifications[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f7f7f8; color: #222; min-height: 100vh; }
        
        .top-nav {
            background: #fff; border-bottom: 1px solid #ebebeb; padding: 0 8%; height: 70px;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200;
        }
        .nav-logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .nav-link { font-size: 14px; font-weight: 500; color: #717171; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .nav-link:hover { color: #222; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 800; }
        .page-header p { color: #717171; font-size: 15px; margin-top: 5px; }

        .notification-list { display: flex; flex-direction: column; gap: 15px; }
        .notification-card {
            background: #fff; padding: 20px 25px; border-radius: 12px;
            border: 1px solid #ebebeb; display: flex; align-items: flex-start; gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .icon {
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .icon.alert { background: #fff0f2; color: #ff385c; }
        .icon.info { background: #e8f0ff; color: #3b6ef5; }
        
        .content { flex: 1; }
        .message { font-size: 15px; line-height: 1.5; color: #333; }
        .date { font-size: 12px; color: #888; margin-top: 8px; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px solid #ebebeb; }
        .empty-state i { font-size: 40px; color: #ccc; margin-bottom: 15px; }
        .empty-state h3 { font-size: 18px; color: #444; }
        
        @media print {
            .top-nav, .btn-print, .empty-state i { display: none !important; }
            body { background: #fff; }
            .container { margin: 0; padding: 0; max-width: 100%; }
            .notification-card { border: none; border-bottom: 1px solid #eee; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="index.php" class="nav-logo">StayHub</a>
    <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
</nav>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1>Notifications</h1>
            <p>Stay updated on your listings and account activity.</p>
        </div>
        <button onclick="window.print()" class="btn-print" style="padding:10px 18px; border:none; border-radius:10px; background:#fff; color:#ff385c; border:1.5px solid #ff385c; cursor:pointer; font-weight:600; font-size:14px;"><i class="fas fa-print"></i> Print Notifications</button>
    </div>

    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications yet</h3>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): 
                $date = ($n['created_at'] instanceof DateTime) ? $n['created_at']->format('M d, Y H:i') : date('M d, Y H:i', strtotime($n['created_at']));
                $isAlert = strpos(strtolower($n['message']), 'declined') !== false;
                $iconClass = $isAlert ? 'alert' : 'info';
                $iconName = $isAlert ? 'fa-times-circle' : 'fa-info-circle';
            ?>
            <div class="notification-card">
                <div class="icon <?php echo $iconClass; ?>">
                    <i class="fas <?php echo $iconName; ?>"></i>
                </div>
                <div class="content">
                    <div class="message"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="date"><?php echo $date; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
