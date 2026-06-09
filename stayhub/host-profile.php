<?php
session_start();
require_once 'config.php';

$host_id = $_GET['id'] ?? 0;

// 1. Fetch Host Details
$sql_user = "SELECT name, avatar, is_host FROM users WHERE id = ?";
$stmt_user = sqlsrv_query($conn, $sql_user, array($host_id));
$host = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);

if (!$host) { die("Utilisateur non trouvé."); }

// 2. Fetch all listings by this specific host
$sql_listings = "SELECT l.*, i.image_url FROM listings l 
                 LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1 
                 WHERE l.user_id = ?";
$stmt_listings = sqlsrv_query($conn, $sql_listings, array($host_id));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Profil de <?php echo htmlspecialchars($host['name']); ?> - StayHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background: #f7f7f7;">
    <div class="container" style="margin-top: 50px;">
        <div style="display: flex; gap: 40px;">
            <div style="flex: 1; max-width: 350px;">
                <div class="add-listing-card" style="text-align: center; padding: 40px;">
                    <img src="img/<?php echo $host['avatar'] ?? 'default-avatar.png'; ?>" 
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover;">
                    <h1 style="margin-top: 20px;"><?php echo htmlspecialchars($host['name']); ?></h1>
                    <p style="color: #717171;">Hôte confirmé</p>
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                    <div style="text-align: left;">
                        <p>⭐ 120 commentaires</p>
                        <p>🛡️ Identité vérifiée</p>
                    </div>
                </div>
            </div>

            <div style="flex: 2;">
                <h2 style="margin-bottom: 20px;">Logements de <?php echo htmlspecialchars($host['name']); ?></h2>
                <div class="listings-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <?php while($row = sqlsrv_fetch_array($stmt_listings, SQLSRV_FETCH_ASSOC)): ?>
                        <div class="card" onclick="window.location.href='listing.php?id=<?php echo $row['id']; ?>'" style="cursor:pointer;">
                            <img src="img/<?php echo $row['image_url'] ?? 'default.jpg'; ?>" style="width:100%; height:200px; border-radius:12px; object-fit:cover;">
                            <h3 style="margin-top:10px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo number_format($row['price'], 0); ?> MAD / nuit</p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>