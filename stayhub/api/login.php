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

        if (!empty($user['is_banned']) && $user['is_banned'] == 1) {
            $reason = !empty($user['ban_reason']) ? htmlspecialchars($user['ban_reason']) : 'Violation of StayHub terms of service.';
            echo "<script>alert('Your account has been banned.\\nReason: " . addslashes($reason) . "'); window.location.href='../index.php';</script>";
            exit();
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = !empty($user['is_admin']) ? 1 : 0;
        $_SESSION['is_host']   = (int)$user['is_host'];

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        if (!empty($user['avatar'])) {
            $_SESSION['user_avatar'] = 'data:image/jpeg;base64,' . base64_encode($user['avatar']);
        } else {
            $_SESSION['user_avatar'] = 'img/default-avatar.png';
        }

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
    <form action="api/login.php" method="POST" id="login-form" novalidate>

        <div class="input-group">
            <label for="login-email">Email</label>
            <input type="email" id="login-email" name="email"
                   required placeholder="Enter your email"
                   autocomplete="email">
            <span class="field-error" id="login-email-err"></span>
        </div>

        <div class="input-group">
            <label for="login-password">Password</label>
            <input type="password" id="login-password" name="password"
                   required placeholder="Enter your password"
                   autocomplete="current-password">
            <span class="field-error" id="login-password-err"></span>
        </div>

        <button type="submit" class="auth-submit-btn">Log in</button>
    </form>
</div>

<style>
.field-error {
    display: block;
    font-size: 12px;
    color: #ff385c;
    margin-top: 4px;
    min-height: 16px;
}
#login-form input.input-invalid {
    border-color: #ff385c;
    background: #fff5f7;
}
#login-form input.input-valid {
    border-color: #34c759;
}
</style>

<script>
(function () {
    const form     = document.getElementById('login-form');
    const emailEl  = document.getElementById('login-email');
    const passEl   = document.getElementById('login-password');
    const emailErr = document.getElementById('login-email-err');
    const passErr  = document.getElementById('login-password-err');

    function validateEmail(val) {
        if (!val) return 'Email is required.';
        if (!val.includes('@')) return 'Missing "@" — please enter a valid email address.';
        const parts = val.split('@');
        if (parts[0].length === 0) return 'Please enter something before the "@".';
        if (!parts[1] || !parts[1].includes('.')) return 'Missing domain — e.g. name@example.com';
        return '';
    }

    function validatePassword(val) {
        if (!val) return 'Password is required.';
        return '';
    }

    function showError(inputEl, errEl, msg) {
        errEl.textContent = msg;
        if (msg) {
            inputEl.classList.add('input-invalid');
            inputEl.classList.remove('input-valid');
        } else {
            inputEl.classList.remove('input-invalid');
            inputEl.classList.add('input-valid');
        }
    }

    emailEl.addEventListener('blur', function () {
        showError(emailEl, emailErr, validateEmail(this.value.trim()));
    });
    emailEl.addEventListener('input', function () {
        if (emailEl.classList.contains('input-invalid')) {
            showError(emailEl, emailErr, validateEmail(this.value.trim()));
        }
    });

    passEl.addEventListener('blur', function () {
        showError(passEl, passErr, validatePassword(this.value));
    });

    form.addEventListener('submit', function (e) {
        const eMsg = validateEmail(emailEl.value.trim());
        const pMsg = validatePassword(passEl.value);
        showError(emailEl, emailErr, eMsg);
        showError(passEl,  passErr,  pMsg);
        if (eMsg || pMsg) e.preventDefault();
    });
})();
</script>
