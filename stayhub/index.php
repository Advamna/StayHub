<?php 
session_start();
require_once 'config.php';

$isHost = false;
$userAvatar = 'img/default-avatar.png';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $sql_user = "SELECT is_host, avatar FROM users WHERE id = ?";
    $stmt_user = sqlsrv_query($conn, $sql_user, array($user_id));
    
    if ($stmt_user && $user_data = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC)) {
        $isHost = (int)$user_data['is_host'];
        $_SESSION['is_host'] = $isHost;
        
        if (!empty($user_data['avatar'])) {
            $userAvatar = 'data:image/jpeg;base64,' . base64_encode($user_data['avatar']);
        }
    }
}

$search = $_GET['search'] ?? '';
$sql = "SELECT l.id, u.name AS Host, l.title, l.location, l.price, i.image_url AS MainPhoto
        FROM listings l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
        WHERE l.status = 'active' AND l.id NOT IN (
            SELECT listing_id FROM reservations 
            WHERE check_out > CAST(GETDATE() AS DATE) 
            AND status != 'cancelled'
        )";

if (!empty($search)) {
    $sql .= " AND (l.title LIKE ? OR l.location LIKE ?)";
    $params = ["%$search%", "%$search%"];
    $stmt = sqlsrv_query($conn, $sql, $params);
} else {
    $stmt = sqlsrv_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>StayHub | Find your next stay</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header class="main-header">
    <div class="container header-container">
        <div class="logo" onclick="window.location.href='index.php'">
            <span class="brand-text">StayHub</span>
        </div>

        <div class="search-section">
            <form action="index.php" method="GET" class="floating-search">
                <div class="search-input">
                    <input type="text" name="search" placeholder="Where are you going?" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="search-circle"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="header-right">
            <div class="user-pill-wrapper">
                <div class="user-pill" style="cursor: pointer; display: flex; align-items: center; gap: 10px; border: 1px solid #ddd; padding: 5px 8px; border-radius: 30px;">
                    <i class="fas fa-bars"></i>
                    <div class="user-icon">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <img src="<?php echo $userAvatar; ?>" class="nav-avatar" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 25px; color: #717171;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="userDropdown" class="dropdown-content">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(!empty($_SESSION['is_admin'])): ?>
                            <a href="admin/index.php" style="color: #ff385c; font-weight: bold;"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                            <hr>
                        <?php endif; ?>
                        <a href="my-rentals.php">My Stays</a>
                        <a href="profile.php">Profile</a>
                        <?php if(!empty($_SESSION['is_host'])): ?>
                            <hr>
                            <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                            <a href="my-listings.php"><i class="fas fa-home"></i> My Listings</a>
                            <a href="host-dashboard.php"><i class="fas fa-plus-circle"></i> Add a Listing</a>
                        <?php else: ?>
                            <hr>
                            <a href="become-host.php">Become a Host</a>
                        <?php endif; ?>
                        <hr>
                        <a href="api/logout.php">Log out</a>
                    <?php else: ?>
                        <a href="javascript:void(0);" onclick="openLogin()">Log in</a>
                        <a href="javascript:void(0);" onclick="openSignup()">Sign up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container">
    <div class="listings-grid">
        <?php while ($listing = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
            <div class="listing-card" onclick="window.location.href='listing.php?id=<?php echo $listing['id']; ?>'">
                <div class="card-image">
                    <img src="<?php echo !empty($listing['MainPhoto']) ? $listing['MainPhoto'] : 'img/placeholder.jpg'; ?>" alt="Property">
                </div>
                <div class="card-info">
                    <div class="info-top">
                        <h4><?php echo htmlspecialchars($listing['location']); ?></h4>
                        <span><i class="fas fa-star"></i> 4.9</span>
                    </div>
                    <p style="color: #717171;">Host: <?php echo htmlspecialchars($listing['Host']); ?></p>
                    <p class="price"><b><?php echo number_format($listing['price']); ?> MAD</b> / night</p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<div id="loginModal" class="modal-overlay" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 450px; position: relative;">
        <span class="close" onclick="document.getElementById('loginModal').style.display='none'" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer;">&times;</span>
        <?php include 'api/login.php'; ?>
    </div>
</div>

<div id="signupModal" class="modal-overlay" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 450px; position: relative;">
        <span class="close" onclick="document.getElementById('signupModal').style.display='none'" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer;">&times;</span>
        <?php include 'api/signup.php'; ?>
    </div>
</div>

<script>
    // Open/close the dropdown — stopPropagation stops the document click
    // from immediately closing it the same moment it opens
    var userPill     = document.querySelector('.user-pill');
    var userDropdown = document.getElementById('userDropdown');

    if (userPill) {
        userPill.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
    }

    // Close dropdown when clicking anywhere outside it
    document.addEventListener('click', function(e) {
        if (userDropdown && !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('show');
        }
        // Close modal when clicking the dark background
        if (e.target.classList.contains('modal-overlay')) {
            e.target.style.display = 'none';
        }
    });

    // Modal open functions (called by onclick attributes in the dropdown)
    function openLogin() {
        userDropdown.classList.remove('show');
        var modal = document.getElementById('loginModal');
        if (modal) { modal.style.display = 'flex'; }
    }

    function openSignup() {
        userDropdown.classList.remove('show');
        var modal = document.getElementById('signupModal');
        if (modal) { modal.style.display = 'flex'; }
    }
</script>
</body>
</html>