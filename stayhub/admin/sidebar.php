<?php
$page = $activePage ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <span style="font-size:24px;">🛡️</span>
        <div>
            StayHub
            <span>Admin Panel</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php"    class="nav-item <?php echo $page==='dashboard' ? 'active':'' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="listings.php" class="nav-item <?php echo $page==='listings'  ? 'active':'' ?>"><i class="fas fa-home"></i> Listings</a>
        <a href="users.php"    class="nav-item <?php echo $page==='users'     ? 'active':'' ?>"><i class="fas fa-users"></i> Users</a>
        <a href="reviews.php"  class="nav-item <?php echo $page==='reviews'   ? 'active':'' ?>"><i class="fas fa-star"></i> Reviews</a>
        <a href="../index.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
</aside>
