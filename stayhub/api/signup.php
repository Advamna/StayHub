<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'] ?? '';
    $lastname  = $_POST['lastname'] ?? '';
    $email     = $_POST['email'] ?? '';
    $phone     = $_POST['phone'] ?? '';
    $password  = $_POST['password'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        die("Please fill in all required fields.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $full_name = $firstname . ' ' . $lastname;

    $sql = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
    $params = array($full_name, $email, $phone, $hashed_password);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $sql_get_id = "SELECT id FROM users WHERE email = ?";
        $stmt_id = sqlsrv_query($conn, $sql_get_id, array($email));
        $user = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_avatar'] = "default-avatar.png";

        header("Location: ../index.php");
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>

<div class="login-form-container">
    <h2>Create Account</h2>
    <form action="api/signup.php" method="POST">
        <div class="input-group">
            <label>First Name</label>
            <input type="text" name="firstname" required placeholder="First name">
        </div>
        <div class="input-group">
            <label>Last Name</label>
            <input type="text" name="lastname" required placeholder="Last name">
        </div>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="input-group">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="Phone number">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Create a password">
        </div>
        <button type="submit" class="auth-submit-btn">Sign Up</button>
    </form>
</div>