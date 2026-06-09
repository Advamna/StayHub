// --- TOGGLE DROPDOWN ---
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// --- MODAL FUNCTIONS ---
function openLogin() {
    const modal = document.getElementById('authModal');
    const body = document.getElementById('modalBody');
    
    // Inject the Login Form
    body.innerHTML = `
        <h2 style="margin-bottom:10px;">Log in to StayHub</h2>
        <p style="color:#717171; margin-bottom:25px;">Welcome back! Please enter your details.</p>
        <form action="api/login.php" method="POST" class="auth-form-internal">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-reserve">Log in</button>
        </form>
        <p style="margin-top:20px; text-align:center; font-size:14px;">
            Don't have an account? <a href="javascript:void(0)" onclick="openSignup()" style="color:#FF5A5F; font-weight:bold; text-decoration:none;">Sign up</a>
        </p>
    `;
    
    modal.style.display = 'flex'; // Force display
    document.getElementById('userDropdown').classList.remove('show'); // Close menu
}

function openSignup() {
    const modal = document.getElementById('authModal');
    const body = document.getElementById('modalBody');
    
    // Inject the Signup Form
    body.innerHTML = `
        <h2 style="margin-bottom:10px;">Sign up for StayHub</h2>
        <p style="color:#717171; margin-bottom:25px;">Create your account to start booking amazing stays.</p>
        <form action="api/signup.php" method="POST" class="auth-form-internal">
            <div style="display:flex; gap:10px; margin-bottom:0px;">
                <div class="form-group" style="flex:1;">
                    <label>First name</label>
                    <input type="text" name="firstname" placeholder="First name" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Last name</label>
                    <input type="text" name="lastname" placeholder="Last name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label>Phone number</label>
                <input type="tel" name="phone" placeholder="Phone number" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" minlength="8" required>
            </div>
            <button type="submit" class="btn-reserve">Create account</button>
        </form>
        <p style="margin-top:20px; text-align:center; font-size:14px;">
            Already have an account? <a href="javascript:void(0)" onclick="openLogin()" style="color:#FF5A5F; font-weight:bold; text-decoration:none;">Log in</a>
        </p>
    `;
    
    modal.style.display = 'flex'; // Force display
    document.getElementById('userDropdown').classList.remove('show'); // Close menu
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function openBecomeHostModal() {
    const modal = document.getElementById('authModal');
    const body = document.getElementById('modalBody');
    
    body.innerHTML = `
        <span class="close-btn" onclick="closeAuthModal()">&times;</span>
        <h2>Become a Host</h2>
        <p style="margin: 15px 0; color: #717171;">To list your apartment, you must agree to our community standards:</p>
        <ul style="text-align: left; margin-bottom: 20px; font-size: 14px; line-height: 1.6;">
            <li>✅ Provide accurate photos and descriptions.</li>
            <li>✅ Respond to booking requests within 24 hours.</li>
            <li>✅ Ensure the safety and cleanliness of your home.</li>
        </ul>
        <form action="api/become-host.php" method="POST">
            <button type="submit" class="btn-reserve">I Agree, Let's Start</button>
        </form>
    `;
    modal.style.display = 'flex';
}

// --- GLOBAL CLICK HANDLER ---
window.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const modal = document.getElementById('authModal');

    // Close dropdown if clicking outside the pill
    if (!event.target.closest('.user-pill-wrapper')) {
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }

    // Close modal if clicking the dark background
    if (event.target === modal) {
        closeAuthModal();
    }
});

// --- REMAINING SEARCH & RESERVATION FUNCTIONS ---
async function makeReservation(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    try {
        const response = await fetch('api/make-reservation.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert('✅ Réservation réussie !');
            form.reset();
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}