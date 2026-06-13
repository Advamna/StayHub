<?php 
session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
require_once 'config.php';

$isHost     = false;
$userAvatar = 'img/default-avatar.png';
$savedIds   = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql_user  = "SELECT is_host, avatar FROM users WHERE id = ?";
    $stmt_user = sqlsrv_query($conn, $sql_user, [$user_id]);
    if ($stmt_user && $user_data = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC)) {
        $isHost = (int)$user_data['is_host'];
        $_SESSION['is_host'] = $isHost;
        if (!empty($user_data['avatar'])) {
            $userAvatar = 'data:image/jpeg;base64,' . base64_encode($user_data['avatar']);
        }
    }

    // Feature 13: fetch user's saved listings
    $wlSql  = "SELECT listing_id FROM wishlists WHERE user_id = ?";
    $wlStmt = sqlsrv_query($conn, $wlSql, [$user_id]);
    if ($wlStmt) {
        while ($wl = sqlsrv_fetch_array($wlStmt, SQLSRV_FETCH_ASSOC)) {
            $savedIds[] = (int)$wl['listing_id'];
        }
    }
}

// ── Feature 9: Search filters ────────────────────────────
$search    = trim($_GET['search']    ?? '');
$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');
$guests    = trim($_GET['guests']    ?? '');
$checkin   = trim($_GET['checkin']   ?? '');
$checkout  = trim($_GET['checkout']  ?? '');

// ── Feature 12: Pagination ───────────────────────────────
$per_page    = 12;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// Build WHERE clauses
$where  = "WHERE (l.status = 'active' OR l.status IS NULL)";
$params = [];

if (!empty($search)) {
    $where   .= " AND (l.title LIKE ? OR l.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($min_price !== '' && is_numeric($min_price)) {
    $where   .= " AND l.price >= ?";
    $params[] = (float)$min_price;
}
if ($max_price !== '' && is_numeric($max_price)) {
    $where   .= " AND l.price <= ?";
    $params[] = (float)$max_price;
}
if ($guests !== '' && is_numeric($guests) && (int)$guests > 0) {
    $where   .= " AND l.max_guests >= ?";
    $params[] = (int)$guests;
}
if (!empty($checkin) && !empty($checkout)) {
    $where .= " AND l.id NOT IN (
        SELECT listing_id FROM reservations
        WHERE status != 'cancelled'
        AND CAST(? AS DATE) < check_out
        AND CAST(? AS DATE) > check_in
    )";
    $params[] = $checkin;
    $params[] = $checkout;
}

// Count total for pagination
$countSql  = "SELECT COUNT(*) AS total FROM listings l $where";
$countStmt = empty($params) ? sqlsrv_query($conn, $countSql) : sqlsrv_query($conn, $countSql, $params);
$totalRows = 0;
if ($countStmt && $cr = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)) {
    $totalRows = (int)$cr['total'];
}
$total_pages = max(1, ceil($totalRows / $per_page));

// Main listing query with pagination (SQL Server 2012+ OFFSET/FETCH)
$sql = "SELECT l.id, u.name AS Host, l.title, l.location, l.price,
               l.max_guests, l.bedrooms, l.bathrooms, l.bed_count,
               i.image_url AS MainPhoto
        FROM listings l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
        $where
        ORDER BY l.created_at DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params[] = $offset;
$params[] = $per_page;
$stmt = sqlsrv_query($conn, $sql, $params);

// Build query string for pagination links (preserve filters)
$filter_qs = http_build_query(array_filter([
    'search'    => $search,
    'min_price' => $min_price,
    'max_price' => $max_price,
    'guests'    => $guests,
    'checkin'   => $checkin,
    'checkout'  => $checkout,
]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <title>StayHub | Find your next stay</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── Filter Bar ── */
        .filter-bar {
            background: #fff;
            border-bottom: 1px solid #ebebeb;
            padding: 14px 0;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2.5%;
        }
        .filter-input {
            padding: 9px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
        }
        .filter-input:focus { border-color: #ff385c; }
        .filter-input.narrow { width: 90px; }
        .filter-input.medium { width: 130px; }
        .btn-filter {
            background: #ff385c;
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-filter:hover { background: #e31c5f; }
        .btn-clear {
            color: #717171;
            font-size: 13px;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn-clear:hover { background: #f5f5f5; color: #222; }

        /* ── Wishlist heart ── */
        .wish-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.85);
            border: none;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
            transition: transform 0.15s;
            z-index: 5;
        }
        .wish-btn:hover { transform: scale(1.15); }
        .wish-btn.saved i { color: #ff385c; }
        .wish-btn:not(.saved) i { color: #717171; }
        .card-image { position: relative; min-height: 200px; background: #f0f0f0; }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 30px 0 60px;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #222;
            transition: background 0.15s, border 0.15s;
        }
        .pagination a:hover { background: #f5f5f5; }
        .pagination span.current { background: #ff385c; color: #fff; border-color: #ff385c; }
        .pagination span.dots { border: none; color: #717171; }

        /* ── Result count ── */
        .results-meta {
            font-size: 14px;
            color: #717171;
            padding: 18px 0 4px;
        }

        /* ── Rating pill ── */
        .rating-pill {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 13px;
            font-weight: 600;
        }
        .rating-pill i { color: #ff385c; font-size: 12px; }
    </style>
</head>
<body>

<header class="main-header">
    <div class="container header-container">
        <div class="logo" onclick="window.location.href='index.php'">
            <span class="brand-text">StayHub</span>
        </div>

        <div class="search-section">
            <form action="index.php" method="GET" class="floating-search" id="mainSearchForm">
                <div class="search-input">
                    <input type="text" name="search" placeholder="Where are you going?" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <!-- preserve other filters when doing text search -->
                <?php if ($min_price !== ''): ?><input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>"><?php endif; ?>
                <?php if ($max_price !== ''): ?><input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>"><?php endif; ?>
                <?php if ($guests !== ''):    ?><input type="hidden" name="guests"    value="<?php echo htmlspecialchars($guests); ?>"><?php endif; ?>
                <?php if ($checkin !== ''):   ?><input type="hidden" name="checkin"   value="<?php echo htmlspecialchars($checkin); ?>"><?php endif; ?>
                <?php if ($checkout !== ''):  ?><input type="hidden" name="checkout"  value="<?php echo htmlspecialchars($checkout); ?>"><?php endif; ?>
                <button type="submit" class="search-circle"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="header-right">
            <div class="user-pill-wrapper">
                <div class="user-pill" onclick="this.parentElement.querySelector('.dropdown-content').classList.toggle('show')" style="cursor:pointer;">
                    <i class="fas fa-bars"></i>
                    <div class="user-icon">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <img src="<?php echo $userAvatar; ?>" class="nav-avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size:25px;color:#717171;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="userDropdown" class="dropdown-content">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <a href="admin/index.php" style="color:#ff385c;font-weight:bold;"><i class="fas fa-shield-alt"></i> Admin Panel</a><hr>
                        <?php endif; ?>
                        <a href="my-rentals.php">My Stays</a>
                        <a href="wishlist.php"><i class="fas fa-heart"></i> Saved
                            <?php if (!empty($savedIds)): ?>
                            <span style="background:#ff385c;color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;font-weight:700;margin-left:4px;"><?php echo count($savedIds); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php">Profile</a>
                        <?php if (!empty($_SESSION['is_host'])): ?>
                            <hr>
                            <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                            <a href="my-listings.php"><i class="fas fa-home"></i> My Listings</a>
                            <a href="host-dashboard.php"><i class="fas fa-plus-circle"></i> Add a Listing</a>
                        <?php else: ?>
                            <hr><a href="become-host.php">Become a Host</a>
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

<!-- ── Feature 9: Filter Bar ──────────────────────────── -->
<div class="filter-bar">
    <form method="GET" action="index.php" class="filter-form">
        <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <?php endif; ?>
        <input type="number" name="min_price" placeholder="Min price" class="filter-input narrow"
               value="<?php echo htmlspecialchars($min_price); ?>" min="0" step="50">
        <input type="number" name="max_price" placeholder="Max price" class="filter-input narrow"
               value="<?php echo htmlspecialchars($max_price); ?>" min="0" step="50">
        <input type="number" name="guests" placeholder="Guests" class="filter-input narrow"
               value="<?php echo htmlspecialchars($guests); ?>" min="1" max="20">
        <input type="date"   name="checkin"  class="filter-input medium"
               value="<?php echo htmlspecialchars($checkin); ?>"
               min="<?php echo date('Y-m-d'); ?>">
        <input type="date"   name="checkout" class="filter-input medium"
               value="<?php echo htmlspecialchars($checkout); ?>"
               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
        <button type="submit" class="btn-filter"><i class="fas fa-sliders-h"></i> Filter</button>
        <?php if ($min_price !== '' || $max_price !== '' || $guests !== '' || $checkin !== '' || $checkout !== ''): ?>
            <a href="index.php<?php echo !empty($search) ? '?search='.urlencode($search) : ''; ?>" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<main class="container">
    <p class="results-meta">
        <?php echo $totalRows; ?> <?php echo $totalRows === 1 ? 'property' : 'properties'; ?> found
        <?php if (!empty($search)): ?> for "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
    </p>

    <div class="listings-grid">
        <?php if ($stmt): while ($listing = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
            $isSaved  = in_array((int)$listing['id'], $savedIds);
            $avgRating = ('' > 0) ? number_format((float)'', 1) : null;
            $revCount  = (int)0;
        ?>
            <div class="listing-card" onclick="window.location.href='listing.php?id=<?php echo $listing['id']; ?>'">
                <div class="card-image">
                    <img src="<?php echo !empty($listing['MainPhoto']) ? htmlspecialchars($listing['MainPhoto']) : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=70'; ?>"
                         alt="<?php echo htmlspecialchars($listing['title']); ?>"
                         loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=70'">
                    <!-- Feature 13: Wishlist heart -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="wish-btn <?php echo $isSaved ? 'saved' : ''; ?>"
                            data-listing="<?php echo $listing['id']; ?>"
                            onclick="toggleWish(event, this, <?php echo $listing['id']; ?>)"
                            title="<?php echo $isSaved ? 'Remove from saved' : 'Save listing'; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-info">
                    <div class="info-top">
                        <h4><?php echo htmlspecialchars($listing['location']); ?></h4>
                        <span class="rating-pill">
                            <i class="fas fa-star"></i>
                            <?php echo $avgRating ? $avgRating . ($revCount > 0 ? " ($revCount)" : '') : 'New'; ?>
                        </span>
                    </div>
                    <p style="color:#717171;font-size:13px;">Host: <?php echo htmlspecialchars($listing['Host']); ?></p>
                    <p class="price"><b><?php echo number_format($listing['price']); ?> MAD</b> / night</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;font-size:12px;color:#717171;">
                        <span><i class="fas fa-users"></i> <?php echo (int)$listing['max_guests']; ?> guests</span>
                        <span><i class="fas fa-door-open"></i> <?php echo (int)($listing['bedrooms'] ?? 1); ?> bed<?php echo ($listing['bedrooms'] ?? 1) != 1 ? 'rooms' : 'room'; ?></span>
                        <span><i class="fas fa-bath"></i> <?php echo (int)($listing['bathrooms'] ?? 1); ?> bath<?php echo ($listing['bathrooms'] ?? 1) != 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            </div>
        <?php endwhile; endif; ?>

        <?php if ($totalRows === 0): ?>
        <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#717171;">
            <i class="fas fa-search" style="font-size:40px; margin-bottom:16px; display:block;"></i>
            <h3 style="margin:0 0 8px; color:#222;">No properties found</h3>
            <p style="margin:0;">Try adjusting your filters or search term.</p>
            <a href="index.php" style="display:inline-block; margin-top:20px; background:#ff385c; color:#fff; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600;">Clear all filters</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Feature 12: Pagination ────────────────── -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?<?php echo $filter_qs; ?>&page=<?php echo $current_page - 1; ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $total_pages; $p++):
            if ($p === 1 || $p === $total_pages || abs($p - $current_page) <= 2): ?>
                <?php if ($p === $current_page): ?>
                    <span class="current"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="?<?php echo $filter_qs; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php elseif (abs($p - $current_page) === 3): ?>
                <span class="dots">…</span>
            <?php endif;
        endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?<?php echo $filter_qs; ?>&page=<?php echo $current_page + 1; ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<!-- Auth Modals -->
<div id="authModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeAuthModal()">
    <div class="modal-content" style="position:relative;">
        <span class="close" onclick="closeAuthModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script src="script.js"></script>
<script>
// Dropdown toggle
document.addEventListener('click', function(e) {
    var dd = document.getElementById('userDropdown');
    if (dd && !e.target.closest('.user-pill-wrapper')) {
        dd.classList.remove('show');
    }
});

function openLogin() {
    const modal = document.getElementById('authModal');
    document.getElementById('modalBody').innerHTML = `
        <h2 style="margin-bottom:20px;">Log in to StayHub</h2>
        <form action="api/login.php" method="POST">
            <div class="input-group"><label>Email</label><input type="email" name="email" placeholder="Enter your email" required></div>
            <div class="input-group"><label>Password</label><input type="password" name="password" placeholder="Password" required></div>
            <button type="submit" class="auth-submit-btn">Log in</button>
        </form>
        <p style="margin-top:16px;text-align:center;font-size:14px;">No account? <a href="javascript:void(0)" onclick="openSignup()" style="color:#ff385c;font-weight:bold;">Sign up</a></p>`;
    modal.style.display = 'flex';
    document.getElementById('userDropdown').classList.remove('show');
}
function openSignup() {
    const modal = document.getElementById('authModal');
    document.getElementById('modalBody').innerHTML = `
        <h2 style="margin-bottom:20px;">Create your account</h2>
        <form action="api/signup.php" method="POST">
            <div style="display:flex;gap:10px;">
                <div class="input-group" style="flex:1"><label>First name</label><input type="text" name="firstname" placeholder="First name" required></div>
                <div class="input-group" style="flex:1"><label>Last name</label><input type="text" name="lastname" placeholder="Last name" required></div>
            </div>
            <div class="input-group"><label>Email</label><input type="email" name="email" placeholder="Email" required></div>
            <div class="input-group"><label>Phone</label><input type="tel" name="phone" placeholder="Phone" required></div>
            <div class="input-group"><label>Password</label><input type="password" name="password" placeholder="Password (min 8 chars)" minlength="8" required></div>
            <button type="submit" class="auth-submit-btn">Create account</button>
        </form>
        <p style="margin-top:16px;text-align:center;font-size:14px;">Have an account? <a href="javascript:void(0)" onclick="openLogin()" style="color:#ff385c;font-weight:bold;">Log in</a></p>`;
    modal.style.display = 'flex';
    document.getElementById('userDropdown').classList.remove('show');
}
function closeAuthModal() { document.getElementById('authModal').style.display = 'none'; }

// ── Issue 3 Fix: Wishlist toggle (AJAX for logged-in, localStorage for guests) ──
var _wishlistInFlight = {};

function toggleWish(e, btn, listingId) {
    e.stopPropagation();
    if (_wishlistInFlight[listingId]) return; // prevent double-click
    _wishlistInFlight[listingId] = true;

    const wasSaved = btn.classList.contains('saved');
    // Optimistic UI update
    btn.classList.toggle('saved', !wasSaved);
    btn.title = !wasSaved ? 'Remove from saved' : 'Save listing';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const isLoggedIn = csrfMeta && csrfMeta.content && csrfMeta.content.length > 0;

    if (!isLoggedIn) {
        // Guest: use localStorage
        var guestWish = JSON.parse(localStorage.getItem('stayhub_wishlist') || '[]');
        var idx = guestWish.indexOf(listingId);
        if (wasSaved) {
            if (idx > -1) guestWish.splice(idx, 1);
        } else {
            if (idx === -1) guestWish.push(listingId);
        }
        localStorage.setItem('stayhub_wishlist', JSON.stringify(guestWish));
        delete _wishlistInFlight[listingId];
        openLogin(); // Prompt login so they know it's guest-only
        return;
    }

    const fd = new FormData();
    fd.append('listing_id', listingId);
    if (csrfMeta && csrfMeta.content) fd.append('csrf_token', csrfMeta.content);

    fetch('api/toggle-wishlist.php', { method: 'POST', body: fd })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (data.redirect || (data.message && data.message === 'Login required')) {
                btn.classList.toggle('saved', wasSaved);
                btn.title = wasSaved ? 'Remove from saved' : 'Save listing';
                openLogin();
                return;
            }
            if (data.success) {
                btn.classList.toggle('saved', data.saved);
                btn.title = data.saved ? 'Remove from saved' : 'Save listing';
                // Update saved count badge in dropdown
                var badge = document.querySelector('.dropdown-content a[href="wishlist.php"] span');
                if (badge) {
                    var cur = parseInt(badge.textContent) || 0;
                    var next = data.saved ? cur + 1 : Math.max(0, cur - 1);
                    badge.textContent = next;
                    badge.style.display = next > 0 ? '' : 'none';
                }
            } else {
                btn.classList.toggle('saved', wasSaved);
                btn.title = wasSaved ? 'Remove from saved' : 'Save listing';
                console.error('[StayHub] Wishlist error:', data.message, data.debug || '');
            }
        })
        .catch(function(err) {
            btn.classList.toggle('saved', wasSaved);
            btn.title = wasSaved ? 'Remove from saved' : 'Save listing';
            console.error('[StayHub] Wishlist network error:', err);
        })
        .finally(function() {
            delete _wishlistInFlight[listingId];
        });
}
</script>
</body>
</html>


