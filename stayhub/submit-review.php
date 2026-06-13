<?php
// ── submit-review.php ─────────────────────────────────────
// Standalone review submission page (Feature 2)
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

if (!$reservation_id) {
    header('Location: my-rentals.php');
    exit;
}

// ── Validate: reservation must belong to this user, be confirmed, check_out in past ──
$resSql = "SELECT r.id, r.listing_id, r.check_in, r.check_out, r.guests, r.status,
                  l.title AS listing_title, l.location AS listing_location,
                  u.name AS user_name, u.email AS user_email
           FROM reservations r
           JOIN listings l ON r.listing_id = l.id
           JOIN users u    ON r.user_id    = u.id
           WHERE r.id = ? AND r.user_id = ? AND r.status = 'confirmed'
             AND r.check_out <= CAST(GETDATE() AS DATE)";
$resStmt = sqlsrv_query($conn, $resSql, [$reservation_id, $user_id]);
if (!$resStmt || !($res = sqlsrv_fetch_array($resStmt, SQLSRV_FETCH_ASSOC))) {
    header('Location: my-rentals.php?error=not_eligible');
    exit;
}

$listing_id = (int)$res['listing_id'];

// ── Check not already reviewed ──
$dupSql  = "SELECT id FROM reviews WHERE reservation_id = ? AND user_id = ?";
$dupStmt = sqlsrv_query($conn, $dupSql, [$reservation_id, $user_id]);
$alreadyReviewed = ($dupStmt && sqlsrv_fetch_array($dupStmt, SQLSRV_FETCH_ASSOC));

// ── Handle POST submission ──
$errors  = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {
    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Security token mismatch. Please try again.';
    } else {
        $rating  = (int)($_POST['rating']  ?? 0);
        $title   = trim($_POST['title']    ?? '');
        $comment = trim($_POST['comment']  ?? '');

        if ($rating < 1 || $rating > 5)      $errors[] = 'Please select a rating.';
        if (strlen($comment) < 10)            $errors[] = 'Review must be at least 10 characters.';
        if (strlen($title) > 255)             $errors[] = 'Title is too long.';

        // Handle photo uploads (max 5)
        $photoUrls = [];
        if (!empty($_FILES['photos']['name'][0]) && empty($errors)) {
            $uploadDir = __DIR__ . '/uploads/reviews/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            $count = min(5, count($_FILES['photos']['name']));
            for ($pi = 0; $pi < $count; $pi++) {
                if ($_FILES['photos']['error'][$pi] === UPLOAD_ERR_OK) {
                    if (!in_array($_FILES['photos']['type'][$pi], $allowed)) { continue; }
                    if ($_FILES['photos']['size'][$pi] > 5 * 1024 * 1024) { continue; }
                    $ext  = pathinfo($_FILES['photos']['name'][$pi], PATHINFO_EXTENSION);
                    $fname = 'rev_' . $user_id . '_' . $reservation_id . '_' . $pi . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$pi], $uploadDir . $fname)) {
                        $photoUrls[] = 'uploads/reviews/' . $fname;
                    }
                }
            }
        }

        if (empty($errors)) {
            $photosJson = !empty($photoUrls) ? json_encode($photoUrls) : null;
            $titleSafe  = $title  ? htmlspecialchars($title,  ENT_QUOTES, 'UTF-8') : null;
            $commentSafe = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

            $insSql = "INSERT INTO reviews
                        (listing_id, user_id, reservation_id, rating, title, comment, photos, status, is_featured, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, GETDATE())";
            $insStmt = sqlsrv_query($conn, $insSql, [
                $listing_id, $user_id, $reservation_id, $rating, $titleSafe, $commentSafe, $photosJson
            ]);

            if ($insStmt) {
                // Recalculate avg rating from approved reviews only
                $avgSql = "SELECT AVG(CAST(rating AS FLOAT)) AS avg_r, COUNT(*) AS cnt
                           FROM reviews WHERE listing_id = ? AND status = 'approved'";
                $avgStmt = sqlsrv_query($conn, $avgSql, [$listing_id]);
                if ($avgStmt && $avgRow = sqlsrv_fetch_array($avgStmt, SQLSRV_FETCH_ASSOC)) {
                    $avg = round((float)$avgRow['avg_r'], 1);
                    $cnt = (int)$avgRow['cnt'];
                    sqlsrv_query($conn, "UPDATE listings SET rating = ?, reviews = ? WHERE id = ?",
                                 [$avg, $cnt, $listing_id]);
                }
                $success = true;
                $alreadyReviewed = true;
            } else {
                $errors[] = 'Server error. Please try again.';
                error_log('submit-review POST error: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }
}

// Format dates
$checkIn  = $res['check_in']  instanceof DateTime ? $res['check_in']->format('d M Y')  : substr($res['check_in'],  0, 10);
$checkOut = $res['check_out'] instanceof DateTime ? $res['check_out']->format('d M Y') : substr($res['check_out'], 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review – <?php echo htmlspecialchars($res['listing_title']); ?> | StayHub</title>
    <link rel="icon" type="image/png" href="StayHubIcon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f6f7fb; color: #222; min-height: 100vh; }

        /* Nav */
        .review-nav {
            background: #fff; border-bottom: 1px solid #eee;
            padding: 0 32px; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .review-nav .logo { font-size: 22px; font-weight: 800; color: #ff385c; text-decoration: none; }
        .review-nav .nav-back { color: #717171; text-decoration: none; font-size: 14px; font-weight: 500; }
        .review-nav .nav-back:hover { color: #222; }

        /* Page */
        .page-wrap { max-width: 680px; margin: 40px auto; padding: 0 20px 60px; }

        /* Property card */
        .property-card {
            background: #fff; border-radius: 16px; padding: 20px 24px;
            border: 1px solid #eee; margin-bottom: 24px;
            display: flex; align-items: center; gap: 16px;
        }
        .property-icon {
            width: 52px; height: 52px; background: #fff0f3; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #ff385c; font-size: 22px; flex-shrink: 0;
        }
        .property-title { font-size: 17px; font-weight: 700; color: #222; margin-bottom: 4px; }
        .property-meta  { font-size: 13px; color: #717171; }
        .property-meta span + span::before { content: ' · '; }

        /* Form card */
        .form-card {
            background: #fff; border-radius: 16px; padding: 32px;
            border: 1px solid #eee; margin-bottom: 24px;
        }
        .form-card h2 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
        .form-card .sub { font-size: 14px; color: #717171; margin-bottom: 28px; }

        /* Star picker */
        .star-label { font-size: 13px; font-weight: 600; color: #444; margin-bottom: 10px; display: block; }
        .stars-picker-wrap { display: flex; gap: 6px; margin-bottom: 28px; }
        .stars-picker-wrap .star-btn {
            background: none; border: none; cursor: pointer;
            font-size: 36px; color: #ddd; padding: 0;
            transition: color .15s, transform .1s;
        }
        .stars-picker-wrap .star-btn:hover,
        .stars-picker-wrap .star-btn.active { color: #ff385c; }
        .stars-picker-wrap .star-btn:hover { transform: scale(1.15); }
        .rating-label { font-size: 13px; color: #717171; margin-left: 10px; align-self: center; }

        /* Fields */
        .field-group { margin-bottom: 20px; }
        .field-group label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 7px; }
        .field-group input[type=text],
        .field-group textarea {
            width: 100%; border: 1px solid #ddd; border-radius: 10px;
            padding: 12px 14px; font-size: 14px; font-family: inherit;
            outline: none; transition: border-color .15s;
            background: #fafafa;
        }
        .field-group input[type=text]:focus,
        .field-group textarea:focus { border-color: #ff385c; background: #fff; }
        .field-group textarea { resize: vertical; min-height: 120px; }
        .char-count { font-size: 11px; color: #aaa; text-align: right; margin-top: 4px; }

        /* Photo upload */
        .photo-upload-area {
            border: 2px dashed #ddd; border-radius: 12px; padding: 20px;
            text-align: center; cursor: pointer; transition: border-color .15s;
            background: #fafafa;
        }
        .photo-upload-area:hover { border-color: #ff385c; }
        .photo-upload-area i { font-size: 28px; color: #ccc; margin-bottom: 8px; }
        .photo-upload-area p { font-size: 13px; color: #717171; margin: 0; }
        .photo-upload-area small { font-size: 11px; color: #aaa; }
        #photoInput { display: none; }
        .photo-preview { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        .photo-preview img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }

        /* Submit */
        .btn-submit {
            width: 100%; background: #ff385c; color: #fff; border: none;
            padding: 15px; border-radius: 12px; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: background .15s, transform .1s;
            margin-top: 8px;
        }
        .btn-submit:hover { background: #e0314f; transform: translateY(-1px); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }

        /* Errors */
        .error-box {
            background: #fff5f5; border: 1px solid #fca5a5; border-radius: 10px;
            padding: 14px 16px; margin-bottom: 20px; font-size: 14px; color: #dc2626;
        }
        .error-box ul { padding-left: 18px; margin: 4px 0 0; }

        /* Success */
        .success-card {
            background: #fff; border-radius: 16px; padding: 48px 32px;
            border: 1px solid #eee; text-align: center;
        }
        .success-icon {
            width: 72px; height: 72px; background: #fff0f3; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #ff385c; margin: 0 auto 20px;
        }
        .success-card h2 { font-size: 22px; font-weight: 700; margin-bottom: 10px; }
        .success-card p  { color: #717171; font-size: 15px; margin-bottom: 28px; line-height: 1.6; }
        .btn-back {
            display: inline-block; background: #ff385c; color: #fff; text-decoration: none;
            padding: 13px 32px; border-radius: 10px; font-weight: 600; font-size: 15px;
        }
        .btn-back:hover { background: #e0314f; }

        /* Already reviewed */
        .already-card {
            background: #fff; border-radius: 16px; padding: 40px 32px;
            border: 1px solid #eee; text-align: center;
        }
        .already-card i { font-size: 40px; color: #ff385c; margin-bottom: 14px; }
        .already-card h2 { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .already-card p  { color: #717171; font-size: 14px; }
    </style>
</head>
<body>

<nav class="review-nav">
    <a href="index.php" class="logo">StayHub</a>
    <a href="listing.php?id=<?php echo $listing_id; ?>" class="nav-back">
        <i class="fas fa-arrow-left"></i> Back to listing
    </a>
</nav>

<div class="page-wrap">

    <!-- Property info -->
    <div class="property-card">
        <div class="property-icon"><i class="fas fa-home"></i></div>
        <div>
            <div class="property-title"><?php echo htmlspecialchars($res['listing_title']); ?></div>
            <div class="property-meta">
                <span><?php echo htmlspecialchars($res['listing_location']); ?></span>
                <span><?php echo $checkIn; ?> → <?php echo $checkOut; ?></span>
                <span><?php echo $res['guests']; ?> guest<?php echo $res['guests'] != 1 ? 's' : ''; ?></span>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
    <!-- ── Success state ── -->
    <div class="success-card">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2>Thank you for your review!</h2>
        <p>
            Your review has been submitted and will be reviewed by our team.<br>
            It may be featured on the listing once approved.
        </p>
        <a href="listing.php?id=<?php echo $listing_id; ?>" class="btn-back">
            <i class="fas fa-home"></i> Back to Listing
        </a>
    </div>

    <?php elseif ($alreadyReviewed): ?>
    <!-- ── Already reviewed ── -->
    <div class="already-card">
        <i class="fas fa-star"></i>
        <h2>You've already reviewed this stay</h2>
        <p>Thank you for your feedback! Your review has been submitted.</p>
        <br>
        <a href="listing.php?id=<?php echo $listing_id; ?>" class="btn-back" style="background:#717171;">
            View Listing
        </a>
    </div>

    <?php else: ?>
    <!-- ── Review form ── -->
    <div class="form-card">
        <h2>Share your experience</h2>
        <p class="sub">How was your stay? Your honest review helps other travelers make great choices.</p>

        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <strong><i class="fas fa-exclamation-triangle"></i> Please fix these issues:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form action="submit-review.php?reservation_id=<?php echo $reservation_id; ?>" method="POST"
              enctype="multipart/form-data" id="reviewForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Star Rating -->
            <span class="star-label">Your Rating <span style="color:#ff385c;">*</span></span>
            <div style="display:flex; align-items:center; margin-bottom: 28px;">
                <div class="stars-picker-wrap" id="starPicker">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <button type="button" class="star-btn" data-val="<?php echo $s; ?>">
                        <i class="fas fa-star"></i>
                    </button>
                    <?php endfor; ?>
                </div>
                <span class="rating-label" id="ratingLabel">Click to rate</span>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="0">

            <!-- Title (optional) -->
            <div class="field-group">
                <label for="reviewTitle">Review Title <span style="color:#aaa; font-weight:400;">(optional)</span></label>
                <input type="text" id="reviewTitle" name="title" maxlength="255"
                       placeholder="e.g. Amazing stay, highly recommend!"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <!-- Comment -->
            <div class="field-group">
                <label for="reviewComment">Your Review <span style="color:#ff385c;">*</span></label>
                <textarea id="reviewComment" name="comment" rows="5" maxlength="1000"
                          placeholder="Tell other guests about your stay — what did you love? Any tips?"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                <div class="char-count"><span id="charCount">0</span> / 1000 characters</div>
            </div>

            <!-- Photos (optional) -->
            <div class="field-group">
                <label>Photos <span style="color:#aaa; font-weight:400;">(optional, max 5)</span></label>
                <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                    <i class="fas fa-camera"></i>
                    <p>Click to add photos from your stay</p>
                    <small>JPEG, PNG or WebP · Max 5MB each</small>
                </div>
                <input type="file" id="photoInput" name="photos[]" multiple accept="image/*" onchange="previewPhotos(this)">
                <div class="photo-preview" id="photoPreview"></div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" disabled>
                <i class="fas fa-paper-plane"></i> Submit Review
            </button>
            <p style="text-align:center; font-size:12px; color:#aaa; margin-top:10px;">
                Your review will be published after a quick moderation check.
            </p>
        </form>
    </div>
    <?php endif; ?>

</div>

<script>
// ── Star picker ──
const stars   = document.querySelectorAll('.star-btn');
const ratingIn = document.getElementById('ratingInput');
const ratingLbl = document.getElementById('ratingLabel');
const submitBtn = document.getElementById('submitBtn');
const labels  = ['','Terrible','Poor','Average','Good','Excellent'];

stars.forEach((btn, idx) => {
    btn.addEventListener('mouseenter', () => highlightStars(idx + 1));
    btn.addEventListener('mouseleave', () => highlightStars(parseInt(ratingIn.value)));
    btn.addEventListener('click', () => {
        ratingIn.value = idx + 1;
        highlightStars(idx + 1);
        ratingLbl.textContent = labels[idx + 1];
        checkFormReady();
    });
});
function highlightStars(n) {
    stars.forEach((b, i) => b.classList.toggle('active', i < n));
}

// ── Char counter ──
const commentEl = document.getElementById('reviewComment');
const charCount = document.getElementById('charCount');
if (commentEl) {
    commentEl.addEventListener('input', () => {
        charCount.textContent = commentEl.value.length;
        checkFormReady();
    });
}

function checkFormReady() {
    const rated     = parseInt(ratingIn.value) >= 1;
    const hasText   = commentEl && commentEl.value.trim().length >= 10;
    if (submitBtn) submitBtn.disabled = !(rated && hasText);
}

// ── Photo preview ──
function previewPhotos(input) {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    const files = Array.from(input.files).slice(0, 5);
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>
