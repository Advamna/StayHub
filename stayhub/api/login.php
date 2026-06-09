<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/../config.php';

    $email    = $_POST['email'];
    $password = $_POST['password'];

    $sql  = "SELECT id, name, password, avatar, is_admin, is_banned, ban_reason, is_host FROM users WHERE email = ?";
    $stmt = sqlsrv_query($conn, $sql, array($email));
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Block banned accounts
        if (!empty($user['is_banned']) && $user['is_banned'] == 1) {
            $reason = !empty($user['ban_reason']) ? htmlspecialchars($user['ban_reason']) : 'Violation of StayHub terms of service.';
            echo "<script>alert('Your account has been banned.\\nReason: " . addslashes($reason) . "'); window.location.href='../index.php';</script>";
            exit();
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = !empty($user['is_admin']) ? 1 : 0;
        $_SESSION['is_host']   = (int)$user['is_host'];

        if (!empty($user['avatar'])) {
            $_SESSION['user_avatar'] = 'data:image/jpeg;base64,' . base64_encode($user['avatar']);
        } else {
            $_SESSION['user_avatar'] = 'img/default-avatar.png';
        }

        // Redirect admins straight to the admin panel
        if ($_SESSION['is_admin'] == 1) {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    } else {
        echo "<script>alert('Invalid email or password'); window.location.href='../index.php';</script>";
        exit();
    }
}
?>

<div class="login-form-container">
    <h2>Welcome Back</h2>
    <form action="api/login.php" method="POST">
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="auth-submit-btn">Login</button>
    </form>
</div>