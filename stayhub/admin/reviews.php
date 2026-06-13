<?php
// ── admin/reviews.php ─────────────────────────────────────
// Feature 3 & 4: Admin Manage Reviews panel
$activePage = 'reviews';
require_once 'guard.php';
require_once '../config.php';

// ── Handle actions ──
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reviewId) {
    switch ($action) {
        case 'approve':
            sqlsrv_query($conn, "UPDATE reviews SET status='approved' WHERE id=?", [$reviewId]);
            break;
        case 'reject':
            sqlsrv_query($conn, "UPDATE reviews SET status='rejected' WHERE id=?", [$reviewId]);
            break;
        case 'delete':
            sqlsrv_query($conn, "DELETE FROM reviews WHERE id=?", [$reviewId]);
            break;
        case 'feature':
            // Unfeature all for this listing first, then feature selected
            sqlsrv_query($conn, "UPDATE reviews SET is_featured=0 WHERE listing_id=?", [$listingId]);
            sqlsrv_query($conn, "UPDATE reviews SET is_featured=1 WHERE id=? AND listing_id=?", [$reviewId, $listingId]);
            break;
        case 'unfeature':
            sqlsrv_query($conn, "UPDATE reviews SET is_featured=0 WHERE id=?", [$reviewId]);
            break;
        case 'bulk':
            $ids = array_map('intval', $_POST['selected'] ?? []);
            $bulk = $_POST['bulk_action'] ?? '';
            foreach ($ids as $bid) {
                if     ($bulk === 'approve') sqlsrv_query($conn, "UPDATE reviews SET status='approved' WHERE id=?", [$bid]);
                elseif ($bulk === 'reject')  sqlsrv_query($conn, "UPDATE reviews SET status='rejected' WHERE id=?", [$bid]);
                elseif ($bulk === 'delete')  sqlsrv_query($conn, "DELETE FROM reviews WHERE id=?", [$bid]);
            }
            break;
    }
    header('Location: reviews.php' . ($_GET ? '?' . http_build_query(array_intersect_key($_GET, array_flip(['apt','status','rating']))) : ''));
    exit;
}

// ── Filters ──
$filterApt    = isset($_GET['apt'])    ? (int)$_GET['apt']         : 0;
$filterStatus = isset($_GET['status']) ? trim($_GET['status'])      : '';
$filterRating = isset($_GET['rating']) ? (int)$_GET['rating']       : 0;

// Build WHERE
$where  = [];
$params = [];
if ($filterApt)    { $where[] = 'rv.listing_id = ?'; $params[] = $filterApt; }
if ($filterStatus) { $where[] = 'rv.status = ?';     $params[] = $filterStatus; }
if ($filterRating) { $where[] = 'rv.rating = ?';     $params[] = $filterRating; }
$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT rv.*, u.name AS ReviewerName, l.title AS ListingTitle
        FROM reviews rv
        JOIN users u    ON rv.user_id    = u.id
        JOIN listings l ON rv.listing_id = l.id
        $whereStr
        ORDER BY rv.created_at DESC";
$stmt = sqlsrv_query($conn, $sql, $params ?: []);
$reviews = [];
if ($stmt) while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $reviews[] = $r;

// All listings for dropdown
$allListings = [];
$lsStmt = sqlsrv_query($conn, "SELECT id, title FROM listings ORDER BY title");
if ($lsStmt) while ($l = sqlsrv_fetch_array($lsStmt, SQLSRV_FETCH_ASSOC)) $allListings[] = $l;

// Stats
$statsStmt = sqlsrv_query($conn, "SELECT status, COUNT(*) AS cnt FROM reviews GROUP BY status");
$statsRaw = [];
if ($statsStmt) while ($s = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC)) $statsRaw[$s['status']] = $s['cnt'];
$totalPending  = $statsRaw['pending']  ?? 0;
$totalApproved = $statsRaw['approved'] ?? 0;
$totalRejected = $statsRaw['rejected'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews – StayHub Admin</title>
    <?php include 'admin-style.php'; ?>
    <style>
        .review-row td { vertical-align: top; padding: 14px 12px; }
        .star-display i { color: #ff385c; font-size: 13px; }
        .star-display i.empty { color: #e0e0e0; }
        .review-text { font-size: 13px; color: #444; line-height: 1.5; max-width: 280px; }
        .review-title-text { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
        .badge-pending  { background:#fff3cd; color:#856404; }
        .badge-approved { background:#d1e7dd; color:#0f5132; }
        .badge-rejected { background:#f8d7da; color:#842029; }
        .featured-badge { background:#fff0f3; color:#ff385c; border:1px solid #ffc0cc; }
        .review-thumb { width:44px; height:44px; object-fit:cover; border-radius:6px; margin-right:4px; }
        .filter-row { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
        .filter-row select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .filter-row button { padding:8px 16px; border:none; border-radius:8px; background:#ff385c; color:#fff; font-size:13px; cursor:pointer; }
        .stat-pills { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-pill { padding:8px 16px; border-radius:20px; font-size:13px; font-weight:600; }
        .actions-cell { display:flex; flex-direction:column; gap:6px; min-width:120px; }
        .btn-action { padding:6px 12px; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px; white-space:nowrap; }
        .btn-approve { background:#d1e7dd; color:#0f5132; }
        .btn-reject  { background:#f8d7da; color:#842029; }
        .btn-delete  { background:#f1f1f1; color:#666; }
        .btn-feature { background:#fff0f3; color:#ff385c; border:1px solid #ffc0cc; }
        .btn-unfeature { background:#eee; color:#555; }
        .bulk-bar { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
        .bulk-bar select { padding:7px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .bulk-bar button { padding:7px 16px; border:none; border-radius:8px; background:#222; color:#fff; font-size:13px; cursor:pointer; }
        .no-reviews { text-align:center; padding:40px; color:#717171; }
        .listing-link { color:#ff385c; font-weight:600; font-size:13px; text-decoration:none; }
        .listing-link:hover { text-decoration:underline; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div>
                <h1>Manage Reviews</h1>
                <p>Moderate, approve, and feature guest reviews</p>
            </div>
            <div class="stat-pills">
                <span class="stat-pill" style="background:#fff3cd; color:#856404;">⏳ <?php echo $totalPending; ?> Pending</span>
                <span class="stat-pill" style="background:#d1e7dd; color:#0f5132;">✅ <?php echo $totalApproved; ?> Approved</span>
                <span class="stat-pill" style="background:#f8d7da; color:#842029;">❌ <?php echo $totalRejected; ?> Rejected</span>
            </div>
        </div>
    </div>

    <div class="section-card">
        <!-- Filters -->
        <form method="GET" action="reviews.php">
            <div class="filter-row">
                <select name="apt">
                    <option value="">All Apartments</option>
                    <?php foreach ($allListings as $al): ?>
                    <option value="<?php echo $al['id']; ?>" <?php echo $filterApt == $al['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($al['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"  <?php echo $filterStatus==='pending'  ? 'selected':''; ?>>Pending</option>
                    <option value="approved" <?php echo $filterStatus==='approved' ? 'selected':''; ?>>Approved</option>
                    <option value="rejected" <?php echo $filterStatus==='rejected' ? 'selected':''; ?>>Rejected</option>
                </select>
                <select name="rating">
                    <option value="">All Ratings</option>
                    <?php for ($r=5;$r>=1;$r--): ?>
                    <option value="<?php echo $r; ?>" <?php echo $filterRating==$r?'selected':''; ?>>
                        <?php echo $r; ?> Star<?php echo $r>1?'s':''; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                <?php if ($filterApt || $filterStatus || $filterRating): ?>
                <a href="reviews.php" style="font-size:13px;color:#717171;">Clear filters</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($reviews)): ?>
        <div class="no-reviews">
            <i class="fas fa-star" style="font-size:36px;color:#ddd;margin-bottom:12px;display:block;"></i>
            <p>No reviews found.</p>
        </div>
        <?php else: ?>

        <!-- Bulk actions -->
        <form method="POST" id="bulkForm">
            <input type="hidden" name="action" value="bulk">
            <div class="bulk-bar">
                <strong style="font-size:13px;"><?php echo count($reviews); ?> review<?php echo count($reviews)!=1?'s':''; ?></strong>
                <select name="bulk_action">
                    <option value="">Bulk action…</option>
                    <option value="approve">Approve selected</option>
                    <option value="reject">Reject selected</option>
                    <option value="delete">Delete selected</option>
                </select>
                <button type="submit" onclick="return confirm('Apply bulk action to selected reviews?')">Apply</button>
            </div>

            <table width="100%" style="border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid #f0f0f0; font-size:11px; text-transform:uppercase; color:#888; letter-spacing:.5px;">
                        <th style="padding:10px 12px; text-align:left; width:30px;"><input type="checkbox" id="selectAll"></th>
                        <th style="padding:10px 12px; text-align:left;">Apartment</th>
                        <th style="padding:10px 12px; text-align:left;">Guest</th>
                        <th style="padding:10px 12px; text-align:left;">Rating</th>
                        <th style="padding:10px 12px; text-align:left;">Review</th>
                        <th style="padding:10px 12px; text-align:left;">Date</th>
                        <th style="padding:10px 12px; text-align:left;">Status</th>
                        <th style="padding:10px 12px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reviews as $rv): ?>
                <?php
                    $dateStr = $rv['created_at'] instanceof DateTime
                        ? $rv['created_at']->format('d M Y')
                        : substr($rv['created_at'] ?? '', 0, 10);
                    $photos = !empty($rv['photos']) ? json_decode($rv['photos'], true) : [];
                    $statusClass = 'badge-' . ($rv['status'] ?? 'pending');
                    $isFeatured  = !empty($rv['is_featured']);
                ?>
                <tr class="review-row" style="border-bottom:1px solid #f5f5f5;">
                    <td><input type="checkbox" name="selected[]" value="<?php echo $rv['id']; ?>"></td>
                    <td>
                        <a href="../listing.php?id=<?php echo $rv['listing_id']; ?>" target="_blank" class="listing-link">
                            <?php echo htmlspecialchars($rv['ListingTitle']); ?>
                        </a>
                        <?php if ($isFeatured): ?>
                        <br><span class="badge featured-badge" style="font-size:10px;padding:2px 7px;">⭐ Featured</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($rv['ReviewerName'] ?? 'Guest'); ?></td>
                    <td>
                        <div class="star-display">
                            <?php for ($s=1;$s<=5;$s++): ?>
                            <i class="fas fa-star <?php echo $s > $rv['rating'] ? 'empty' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span style="font-size:11px;color:#888;"><?php echo $rv['rating']; ?>/5</span>
                    </td>
                    <td>
                        <?php if (!empty($rv['title'])): ?>
                        <div class="review-title-text"><?php echo htmlspecialchars($rv['title']); ?></div>
                        <?php endif; ?>
                        <div class="review-text"><?php echo nl2br(htmlspecialchars(substr($rv['comment'] ?? '', 0, 180))); ?><?php echo strlen($rv['comment'] ?? '') > 180 ? '…' : ''; ?></div>
                        <?php if (!empty($photos)): ?>
                        <div style="margin-top:6px;">
                            <?php foreach (array_slice($photos, 0, 3) as $ph): ?>
                            <img src="../<?php echo htmlspecialchars($ph); ?>" class="review-thumb" alt="photo">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px; color:#717171; white-space:nowrap;"><?php echo $dateStr; ?></td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($rv['status'] ?? 'pending'); ?></span></td>
                    <td>
                        <div class="actions-cell">
                            <?php if (($rv['status'] ?? '') !== 'approved'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="review_id" value="<?php echo $rv['id']; ?>">
                                <?php if ($filterApt) echo '<input type="hidden" name="apt" value="'.$filterApt.'">'; ?>
                                <button type="submit" class="btn-action btn-approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <?php endif; ?>

                            <?php if (($rv['status'] ?? '') !== 'rejected'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="review_id" value="<?php echo $rv['id']; ?>">
                                <button type="submit" class="btn-action btn-reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                            <?php endif; ?>

                            <?php if (($rv['status'] ?? '') === 'approved'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action"     value="<?php echo $isFeatured ? 'unfeature' : 'feature'; ?>">
                                <input type="hidden" name="review_id"  value="<?php echo $rv['id']; ?>">
                                <input type="hidden" name="listing_id" value="<?php echo $rv['listing_id']; ?>">
                                <button type="submit" class="btn-action <?php echo $isFeatured ? 'btn-unfeature' : 'btn-feature'; ?>">
                                    <i class="fas fa-<?php echo $isFeatured ? 'star-half-alt' : 'star'; ?>"></i>
                                    <?php echo $isFeatured ? 'Unfeature' : 'Set as Best'; ?>
                                </button>
                            </form>
                            <?php endif; ?>

                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this review?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="review_id" value="<?php echo $rv['id']; ?>">
                                <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
</body>
</html>
