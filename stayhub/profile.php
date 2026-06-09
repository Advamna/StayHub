<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch latest data including name and avatar
$sql = "SELECT name, email, avatar FROM users WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, array($user_id));
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// 2. Logic to convert Binary DB data to a viewable Image
if (!empty($user['avatar'])) {
    $encoded = base64_encode($user['avatar']);
    $displayImage = "data:image/jpeg;base64," . $encoded;
} else {
    $displayImage = "img/default-avatar.png";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - StayHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        #profileModalOverlay {
            display: none;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.75) !important;
            z-index: 999999 !important;
            align-items: center;
            justify-content: center;
        }
        .custom-modal-box {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body style="background: #f7f7f7;">

<header style="background: white; border-bottom: 1px solid #ebebeb; padding: 15px 0; position: sticky; top: 0; z-index: 1000; width: 100%;">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; max-width: 1280px; margin: 0 auto; width: 95%;">
        <div style="flex: 1;"></div>
        <div style="flex: 1; text-align: center;">
            <span onclick="window.location.href='index.php'" style="color: #FF5A5F; font-weight: bold; font-size: 24px; cursor: pointer; font-family: 'Inter', sans-serif;">StayHub</span>
        </div>
        <div style="flex: 1; text-align: right;">
            <a href="index.php" style="text-decoration: none; color: #717171; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;">
                <span style="font-size: 18px;">←</span> Return Home
            </a>
        </div>
    </div>
</header>

<div class="container" style="margin-top: 50px; max-width: 600px;">
    <div class="add-listing-card" style="text-align: center; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <div style="position: relative; display: inline-block;">
            <img id="mainProfilePic" src="<?php echo $displayImage; ?>" 
             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            
            <button id="openBtn" style="position: absolute; bottom: 5px; right: 5px; background: #FF5A5F; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center;">+</button>
        </div>
        
        <h2 style="margin-top: 20px;"><?php echo htmlspecialchars($user['name']); ?></h2>
        <p style="color: #717171;"><?php echo htmlspecialchars($user['email']); ?></p>

        <button onclick="window.location.href='logout.php'" style="margin-top: 30px; background: #222; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">Se déconnecter</button>
    </div>
</div>

<div id="profileModalOverlay">
    <div class="custom-modal-box">
        <span id="closeX" style="position: absolute; right: 20px; top: 15px; font-size: 28px; cursor: pointer;">&times;</span>
        <h3 style="margin-bottom: 20px;">Modifier mon profil</h3>
        
        <form id="finalProfileForm" action="update-profile.php" method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 15px; text-align: left;">
                <label style="display: block; font-size: 14px; margin-bottom: 5px;">Nom complet</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" required>
            </div>
            <div style="margin-bottom: 25px; text-align: left;">
                <label style="display: block; font-size: 14px; margin-bottom: 5px;">Photo de profil</label>
                <input type="file" id="fileInput" name="avatar" accept="image/*" style="width: 100%;">
            </div>
            
            <button type="submit" id="saveButton" style="width: 100%; background: #FF5A5F; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <span id="btnText">Enregistrer les modifications</span>
                <div id="loadingSpinner" class="spinner"></div>
            </button>
        </form>
    </div>
</div>

<script>
    const overlay = document.getElementById('profileModalOverlay');
    const openBtn = document.getElementById('openBtn');
    const closeX = document.getElementById('closeX');
    const fileInput = document.getElementById('fileInput');
    const mainPic = document.getElementById('mainProfilePic');

    openBtn.onclick = function() { overlay.style.display = "flex"; };
    closeX.onclick = function() { overlay.style.display = "none"; };
    window.onclick = function(event) { if (event.target == overlay) { overlay.style.display = "none"; } };

    fileInput.onchange = function() {
        const [file] = fileInput.files;
        if (file) { mainPic.src = URL.createObjectURL(file); }
    };

    document.getElementById('finalProfileForm').onsubmit = function() {
        document.getElementById('saveButton').style.opacity = "0.7";
        document.getElementById('saveButton').style.pointerEvents = "none";
        document.getElementById('btnText').innerText = "Téléchargement...";
        document.getElementById('loadingSpinner').style.display = "block";
    };
</script>
</body>
</html>