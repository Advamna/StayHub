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

    $sql    = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
    $params = array($full_name, $email, $phone, $hashed_password);
    $stmt   = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $sql_get_id = "SELECT id FROM users WHERE email = ?";
        $stmt_id    = sqlsrv_query($conn, $sql_get_id, array($email));
        $user       = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);

        session_regenerate_id(true);
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_name']   = $full_name;
        $_SESSION['user_avatar'] = "default-avatar.png";

        // ── Issue 3 Fix: Migrate guest wishlist (localStorage IDs sent via hidden field) ──
        $guestWishlist = $_POST['guest_wishlist'] ?? '';
        if (!empty($guestWishlist) && !empty($user['id'])) {
            $ids = array_filter(array_map('intval', explode(',', $guestWishlist)));
            foreach ($ids as $lid) {
                if ($lid > 0) {
                    $chk = sqlsrv_query($conn,
                        "SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?",
                        [$user['id'], $lid]
                    );
                    if ($chk && !sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC)) {
                        sqlsrv_query($conn,
                            "INSERT INTO wishlists (user_id, listing_id, created_at) VALUES (?, ?, GETDATE())",
                            [$user['id'], $lid]
                        );
                    }
                }
            }
        }

        header("Location: ../index.php");
        exit();
    } else {
        error_log('StayHub signup error: ' . print_r(sqlsrv_errors(), true));
        die("An error occurred. Please try again.");
    }
}
?>

<div class="login-form-container">
    <h2>Create Account</h2>
    <form action="api/signup.php" method="POST" id="signup-form" novalidate>

        <div class="form-row-inline">
            <div class="input-group">
                <label for="su-firstname">First Name</label>
                <input type="text" id="su-firstname" name="firstname"
                       required placeholder="First name"
                       autocomplete="given-name">
                <span class="field-error" id="err-firstname"></span>
            </div>
            <div class="input-group">
                <label for="su-lastname">Last Name</label>
                <input type="text" id="su-lastname" name="lastname"
                       required placeholder="Last name"
                       autocomplete="family-name">
                <span class="field-error" id="err-lastname"></span>
            </div>
        </div>

        <div class="input-group">
            <label for="su-email">Email</label>
            <input type="text" id="su-email" name="email"
                   required placeholder="Enter your email"
                   autocomplete="email">
            <span class="field-error" id="err-email"></span>
        </div>

        <div class="input-group">
            <label for="su-phone">Phone <span style="color:#aaa;font-weight:400;">(optional)</span></label>
            <input type="text" id="su-phone" name="phone"
                   placeholder="Phone number"
                   inputmode="numeric"
                   autocomplete="tel">
            <span class="field-error" id="err-phone"></span>
        </div>

        <div class="input-group">
            <label for="su-password">Password</label>
            <input type="password" id="su-password" name="password"
                   required placeholder="At least 8 characters"
                   autocomplete="new-password">
            <span class="field-error" id="err-password"></span>
            <div id="strength-bar-wrap" style="margin-top:6px; display:none;">
                <div style="height:4px; border-radius:4px; background:#eee; overflow:hidden;">
                    <div id="strength-bar" style="height:100%; width:0; border-radius:4px; transition:width 0.3s, background 0.3s;"></div>
                </div>
                <span id="strength-label" style="font-size:11px; color:#717171;"></span>
            </div>
        </div>

        <button type="submit" class="auth-submit-btn">Sign Up</button>
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
#signup-form input.input-invalid {
    border-color: #ff385c;
    background: #fff5f7;
}
#signup-form input.input-valid {
    border-color: #34c759;
}
.form-row-inline {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
</style>

<script>
(function () {
    const form = document.getElementById('signup-form');

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

    // ── Block digits & symbols on name fields (letters, spaces, hyphens, apostrophes only) ──
    ['su-firstname', 'su-lastname'].forEach(function(id) {
        document.getElementById(id).addEventListener('keydown', function(e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End',' ','-',"'"];
            if (!allowed.includes(e.key) && !/^[a-zA-ZÀ-ÿ]$/.test(e.key)) {
                e.preventDefault(); // Block digits and all other non-letter keys
            }
        });
    });

    // ── Block letters & symbols on phone field (digits, +, -, spaces, parentheses only) ──
    document.getElementById('su-phone').addEventListener('keydown', function(e) {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End',
                         '+','-',' ','(', ')'];
        if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
            e.preventDefault(); // Block letters and all non-numeric keys
        }
    });

    // ── Block digits on email field ──
    document.getElementById('su-email').addEventListener('keydown', function(e) {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End',
                         '@','.','_','-','+']; // valid email punctuation
        if (!allowed.includes(e.key) && !/^\d$/.test(e.key) === false) {
            // It IS a digit — block it
            e.preventDefault();
        }
        // Also block anything that's not a letter, allowed symbol, or control key
        if (!/^[a-zA-Z]$/.test(e.key) && !allowed.includes(e.key) && e.key.length === 1) {
            e.preventDefault();
        }
    });

    // ── Validators ──
    function validateName(val, label) {
        if (!val.trim()) return label + ' is required.';
        if (val.trim().length < 2) return label + ' must be at least 2 characters.';
        if (/[0-9]/.test(val)) return label + ' must only contain letters.';
        return '';
    }

    function validateEmail(val) {
        if (!val.trim()) return 'Email is required.';
        if (/[0-9]/.test(val)) return 'Email address cannot contain numbers.';
        if (!val.includes('@')) return 'Missing "@" — please enter a valid email address.';
        const parts = val.split('@');
        if (parts[0].length === 0) return 'Please enter something before the "@".';
        if (!parts[1] || !parts[1].includes('.')) return 'Missing domain — e.g. name@example.com';
        if (parts[1].split('.').pop().length < 2) return 'Enter a valid domain ending — e.g. .com or .net';
        return '';
    }

    function validatePassword(val) {
        if (!val) return 'Password is required.';
        if (val.length < 8) return 'Password must be at least 8 characters (currently ' + val.length + ').';
        if (!/[a-zA-Z]/.test(val)) return 'Password must contain at least one letter.';
        return '';
    }

    function validatePhone(val) {
        if (!val) return ''; // optional
        if (!/^[\d\s\+\-\(\)]{6,20}$/.test(val)) return 'Enter a valid phone number.';
        return '';
    }

    // ── Password strength bar ──
    const passEl        = document.getElementById('su-password');
    const strengthWrap  = document.getElementById('strength-bar-wrap');
    const strengthBar   = document.getElementById('strength-bar');
    const strengthLabel = document.getElementById('strength-label');

    passEl.addEventListener('input', function () {
        const val = this.value;
        strengthWrap.style.display = val.length ? 'block' : 'none';

        let score = 0;
        if (val.length >= 8)           score++;
        if (val.length >= 12)          score++;
        if (/[A-Z]/.test(val))         score++;
        if (/[0-9]/.test(val))         score++;
        if (/[^A-Za-z0-9]/.test(val))  score++;

        const levels = [
            { label: 'Too short', color: '#ff385c', pct: '20%'  },
            { label: 'Weak',      color: '#ff385c', pct: '35%'  },
            { label: 'Fair',      color: '#ff9500', pct: '55%'  },
            { label: 'Good',      color: '#34c759', pct: '75%'  },
            { label: 'Strong',    color: '#34c759', pct: '100%' },
        ];
        const lvl = levels[Math.min(score, 4)];
        strengthBar.style.width      = lvl.pct;
        strengthBar.style.background = lvl.color;
        strengthLabel.textContent    = lvl.label;
        strengthLabel.style.color    = lvl.color;

        if (passEl.classList.contains('input-invalid')) {
            showError(passEl, document.getElementById('err-password'), validatePassword(val));
        }
    });

    // ── Blur + live re-validation ──
    const fields = [
        { el: 'su-firstname', err: 'err-firstname', fn: v => validateName(v, 'First name') },
        { el: 'su-lastname',  err: 'err-lastname',  fn: v => validateName(v, 'Last name')  },
        { el: 'su-email',     err: 'err-email',     fn: v => validateEmail(v)               },
        { el: 'su-phone',     err: 'err-phone',     fn: v => validatePhone(v)               },
        { el: 'su-password',  err: 'err-password',  fn: v => validatePassword(v)            },
    ];

    fields.forEach(function(f) {
        const inputEl = document.getElementById(f.el);
        const errEl   = document.getElementById(f.err);
        inputEl.addEventListener('blur', function () {
            showError(inputEl, errEl, f.fn(this.value));
        });
        inputEl.addEventListener('input', function () {
            if (inputEl.classList.contains('input-invalid')) {
                showError(inputEl, errEl, f.fn(this.value));
            }
        });
    });

    // ── Submit guard + guest wishlist migration ──
    form.addEventListener('submit', function (e) {
        let hasError = false;
        fields.forEach(function(f) {
            const inputEl = document.getElementById(f.el);
            const errEl   = document.getElementById(f.err);
            const msg     = f.fn(inputEl.value);
            showError(inputEl, errEl, msg);
            if (msg) hasError = true;
        });
        if (hasError) { e.preventDefault(); return; }
        // Carry over guest localStorage wishlist
        try {
            var guestWish = JSON.parse(localStorage.getItem('stayhub_wishlist') || '[]');
            if (guestWish.length > 0) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'guest_wishlist';
                hidden.value = guestWish.join(',');
                form.appendChild(hidden);
                localStorage.removeItem('stayhub_wishlist'); // clear after migration
            }
        } catch(err) { /* ignore localStorage errors */ }
    });
})();
</script>
