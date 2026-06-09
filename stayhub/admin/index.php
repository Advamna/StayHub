<?php
$activePage = 'dashboard';
require_once 'guard.php';
require_once '../config.php';

// ── Stats ──
$stats = [];
foreach ([
    'total_users'    => "SELECT COUNT(*) AS n FROM users WHERE is_admin = 0",
    'total_listings' => "SELECT COUNT(*) AS n FROM listings",
    'flagged'        => "SELECT COUNT(*) AS n FROM listings WHERE is_flagged = 1",
    'banned_users'   => "SELECT COUNT(*) AS n FROM users WHERE is_banned = 1",
    'total_bookings' => "SELECT COUNT(*) AS n FROM reservations",
] as $key => $q) {
    $r = sqlsrv_query($conn, $q);
    $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC);
    $stats[$key] = $row['n'] ?? 0;
}

// ── Recent listings (last 5) ──
$recentSql  = "SELECT TOP 5 l.id, l.title, l.location, l.price, l.is_flagged, u.name AS host, l.created_at
               FROM listings l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC";
$recentStmt = sqlsrv_query($conn, $recentSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – StayHub</title>
    <link rel="icon" type="image/png" href="../StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include 'admin-style.php'; ?>
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 👋</p>
        </div>
        <div style="font-size:13px; color:#aaa;"><?php echo date('l, d F Y'); ?></div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff0f3; color:#ff385c;"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0f4ff; color:#4361ee;"><i class="fas fa-home"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_listings']); ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff8e1; color:#f59e0b;"><i class="fas fa-flag"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['flagged']); ?></div>
                <div class="stat-label">Flagged Listings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdecea; color:#b91c1c;"><i class="fas fa-ban"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['banned_users']); ?></div>
                <div class="stat-label">Banned Accounts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4; color:#059669;"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_bookings']); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
    </div>

    <!-- Recent Listings -->
    <div class="section-card">
        <div class="section-header">
            <h2>Recent Listings</h2>
            <a href="listings.php" class="view-all">View all <i class="fas fa-arrow-right"></i></a>
        </div>
        <table class="admin-table">
            <thead><tr><th>Title</th><th>Host</th><th>Location</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($r = sqlsrv_fetch_array($recentStmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><a href="../listing.php?id=<?php echo $r['id']; ?>" target="_blank"><?php echo htmlspecialchars($r['title']); ?></a></td>
                    <td><?php echo htmlspecialchars($r['host']); ?></td>
                    <td><?php echo htmlspecialchars($r['location']); ?></td>
                    <td><?php echo number_format($r['price']); ?> MAD</td>
                    <td><?php echo $r['is_flagged'] ? '<span class="badge badge-danger">Flagged</span>' : '<span class="badge badge-success">Active</span>'; ?></td>
                    <td>
                        <a href="listings.php" class="btn-sm btn-outline">Manage</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
