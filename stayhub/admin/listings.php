<?php
$activePage = 'listings';
require_once 'guard.php';
require_once '../config.php';

$msg = '';
$msgType = '';
if (isset($_GET['done'])) {
    $map = [
        'flagged'   => ['Listing has been flagged as a rule violation.',  'success'],
        'unflagged' => ['Flag removed — listing is now active.',           'success'],
        'approved'  => ['Listing has been approved and published.',        'success'],
        'declined'  => ['Listing has been declined and deleted.',          'success'],
        'deleted'   => ['Listing and all its data have been deleted.',     'success'],
        'error'     => ['Something went wrong. Please try again.',         'error'],
    ];
    [$msg, $msgType] = $map[$_GET['done']] ?? ['', ''];
}

// ── Filters ──
$search  = trim($_GET['search'] ?? '');
$filter  = $_GET['filter'] ?? 'all';  // all | flagged | active | pending

$where   = [];
$params  = [];

if ($search !== '') {
    $where[]   = "(l.title LIKE ? OR l.location LIKE ? OR u.name LIKE ?)";
    $params[]  = "%$search%";
    $params[]  = "%$search%";
    $params[]  = "%$search%";
}
if ($filter === 'flagged') { $where[] = "l.is_flagged = 1"; }
if ($filter === 'active')  { $where[] = "l.status = 'active' AND l.is_flagged = 0"; }
if ($filter === 'pending') { $where[] = "l.status = 'pending'"; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT l.id, l.title, l.location, l.price, l.is_flagged, l.flag_reason,
               l.max_guests, l.bed_count, l.created_at, l.status,
               u.id AS user_id, u.name AS host, u.email AS host_email, u.is_banned,
               i.image_url
        FROM listings l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
        $whereSql
        ORDER BY l.created_at DESC";

$stmt = $params ? sqlsrv_query($conn, $sql, $params) : sqlsrv_query($conn, $sql);
$listings = [];
if ($stmt === false) {
    // Query failed — show error details for debugging
    $errDetails = sqlsrv_errors();
    error_log('admin/listings.php query error: ' . print_r($errDetails, true));
    // Display a visible admin error notice
    echo '<div style="background:#f8d7da;color:#842029;padding:12px 18px;border-radius:8px;margin:16px 0;font-size:13px;"><strong>SQL Error:</strong> ' . htmlspecialchars(print_r($errDetails, true)) . '</div>';
} else {
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $listings[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listings – StayHub Admin</title>
    <link rel="icon" type="image/png" href="../StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include 'admin-style.php'; ?>
        /* ══════════════════════════════════════════
           STAYHUB UNIVERSAL PRINT STYLES
           ══════════════════════════════════════════ */
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #fff !important; margin: 0; padding: 0; }
            @page { margin: 14mm 12mm 14mm 12mm; size: A4 portrait; }

            /* ── Hide all screen chrome ── */
            .top-nav, .nav-bar, .sidebar, .filter-bar,
            .btn-print, .btn-add-listing, .btn-sm,
            .action-buttons, .stats-bar, .bookings-toggle,
            .alert, .delete-overlay, .adm-overlay,
            .no-print { display: none !important; }

            /* ── Page layout ── */
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .container    { margin: 0; padding: 0; max-width: 100%; }
            .page-header  { padding: 0 0 16px 0 !important; }

            /* ── Letterhead (hidden on screen, shown on print) ── */
            .print-header {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 12px;
                margin-bottom: 18px;
                border-bottom: 2.5px solid #ff385c;
            }
            .print-logo { font-size: 28px; font-weight: 900; color: #ff385c !important; letter-spacing: -0.5px; }
            .print-logo span { color: #222 !important; }
            .print-meta { text-align: right; font-size: 11px; color: #555 !important; line-height: 1.8; }
            .print-meta strong { color: #111 !important; font-size: 12px; }

            /* ── Cards & tables ── */
            .section-card, .listing-block, .notification-card {
                box-shadow: none !important; border: 1px solid #ddd !important;
                break-inside: avoid; margin-bottom: 12px !important; border-radius: 8px !important;
            }
            .notification-card { border-bottom: 1px solid #eee !important; border-radius: 0 !important; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #e0e0e0; padding: 8px 10px; }
            th { background: #f9f9f9 !important; font-weight: 600; font-size: 11px; letter-spacing: 0.4px; text-transform: uppercase; }
            .listing-img { width: 120px !important; min-height: 80px !important; }
            .bookings-table-wrap { display: block !important; }
            .bookings-section { page-break-inside: auto; }

            /* ── Print footer ── */
            .print-footer {
                display: block !important; text-align: center;
                margin-top: 28px; padding-top: 12px;
                border-top: 1px solid #eee;
                font-size: 10px; color: #aaa !important; letter-spacing: 0.3px;
            }
        }
        /* admin listings extras */
        @media print {
            td:last-child, th:last-child { display: none !important; }
            body.print-single tbody tr { display: none !important; }
            body.print-single tbody tr.print-target { display: table-row !important; }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?><!-- sidebar hidden on print -->
<div class="main-content">

    <!-- Print letterhead -->
    <div class="print-header" style="display:none;">
        <div class="print-logo">Stay<span>Hub</span></div>
        <div class="print-meta">
            <strong>Admin &mdash; Listings Report</strong><br>
            <?php echo count($listings); ?> listing<?php echo count($listings)!=1?'s':''; ?> total<br>
            Printed: <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <div class="page-header">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div>
                <h1>Listings</h1>
                <p>Review, flag, or remove property listings</p>
            </div>
            <div>
                <span class="badge badge-info" style="font-size:13px; padding:6px 14px; margin-right:10px;"><?php echo count($listings); ?> listing<?php echo count($listings)!=1?'s':''; ?></span>
                <button onclick="printAll()" class="btn-sm btn-outline"><i class="fas fa-print"></i> Print All</button>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="adm-alert adm-alert-<?php echo $msgType; ?>">
        <i class="fas fa-<?php echo $msgType==='success'?'check-circle':'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search by title, location, or host…" value="<?php echo htmlspecialchars($search); ?>">
        <select name="filter">
            <option value="all"     <?php echo $filter==='all'     ?'selected':''; ?>>All listings</option>
            <option value="pending" <?php echo $filter==='pending' ?'selected':''; ?>>Pending Approval</option>
            <option value="flagged" <?php echo $filter==='flagged' ?'selected':''; ?>>Flagged only</option>
            <option value="active"  <?php echo $filter==='active'  ?'selected':''; ?>>Active only</option>
        </select>
        <button type="submit" class="btn-sm btn-primary">Filter</button>
        <?php if ($search || $filter!=='all'): ?>
            <a href="listings.php" class="btn-sm btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <div class="section-card" style="padding:0; overflow:hidden;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Host</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Flag reason</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($listings)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#8892a4;">No listings found.</td></tr>
            <?php endif; ?>
            <?php foreach ($listings as $l): 
                $imgSrc = !empty($l['image_url'])
                    ? (strpos($l['image_url'],'http')===0 ? $l['image_url'] : '../uploads/'.$l['image_url'])
                    : 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=120';
            ?>
            <tr id="row-<?php echo $l['id']; ?>">
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img class="listing-thumb" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="">
                        <div>
                            <a href="../listing.php?id=<?php echo $l['id']; ?>" target="_blank"><?php echo htmlspecialchars($l['title']); ?></a>
                            <div style="color:#8892a4; font-size:11px; margin-top:2px;">
                                <?php echo $l['max_guests']; ?> guests · <?php echo $l['bed_count']; ?> beds
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div><?php echo htmlspecialchars($l['host']); ?></div>
                    <div style="color:#8892a4; font-size:11px;"><?php echo htmlspecialchars($l['host_email']); ?></div>
                    <?php if ($l['is_banned']): ?>
                        <span class="badge badge-danger" style="margin-top:3px;">Host banned</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($l['location']); ?></td>
                <td><?php echo number_format($l['price']); ?> MAD</td>
                <td>
                    <?php if ($l['status'] === 'pending'): ?>
                        <span class="badge badge-info"><i class="fas fa-clock"></i> Pending</span>
                    <?php elseif ($l['is_flagged']): ?>
                        <span class="badge badge-danger"><i class="fas fa-flag"></i> Flagged</span>
                    <?php else: ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:140px; white-space:normal; font-size:12px; color:#fbbf24;">
                    <?php echo $l['is_flagged'] ? htmlspecialchars($l['flag_reason'] ?? '—') : '<span style="color:#8892a4;">—</span>'; ?>
                </td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn-sm btn-outline" style="margin-right:4px;" onclick="printSingle(<?php echo $l['id']; ?>)">
                        <i class="fas fa-print"></i>
                    </button>
                    <?php if ($l['status'] === 'pending'): ?>
                        <a href="actions/approve-listing.php?id=<?php echo $l['id']; ?>" class="btn-sm btn-green">
                            <i class="fas fa-check"></i> Approve
                        </a>
                        <button class="btn-sm btn-red" style="margin-left:4px;" onclick="openDeclineModal(<?php echo $l['id']; ?>)">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    <?php else: ?>
                        <?php if (!$l['is_flagged']): ?>
                        <button class="btn-sm btn-yellow" onclick="openFlagModal(<?php echo $l['id']; ?>)">
                            <i class="fas fa-flag"></i> Flag
                        </button>
                        <?php else: ?>
                        <a href="actions/unflag-listing.php?id=<?php echo $l['id']; ?>" class="btn-sm btn-green">
                            <i class="fas fa-check"></i> Unflag
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="users.php?highlight=<?php echo $l['user_id']; ?>" class="btn-sm btn-outline" style="margin-left:4px;">
                        <i class="fas fa-user"></i> Host
                    </a>
                    <button class="btn-sm btn-red" style="margin-left:4px;" onclick="openDeleteModal(<?php echo $l['id']; ?>, '<?php echo addslashes($l['title']); ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Flag Modal -->
<div class="adm-overlay" id="flagOverlay">
    <div class="adm-modal">
        <h3>⚠️ Flag Listing</h3>
        <p>Briefly describe why this listing violates StayHub's host rules. The reason will be visible to admin staff.</p>
        <form method="POST" action="actions/flag-listing.php">
            <input type="hidden" name="listing_id" id="flagListingId">
            <textarea name="reason" rows="4" placeholder="e.g. Misleading photos, fake amenities, illegal property…" required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-outline" onclick="closeFlagModal()">Cancel</button>
                <button type="submit" class="btn-sm btn-yellow"><i class="fas fa-flag"></i> Flag Listing</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="adm-overlay" id="deleteOverlay">
    <div class="adm-modal">
        <h3>🗑️ Delete Listing</h3>
        <p>Are you sure you want to permanently delete <strong id="deleteTitle"></strong>? This will also remove all bookings and images. This cannot be undone.</p>
        <form method="POST" action="actions/delete-listing.php">
            <input type="hidden" name="listing_id" id="deleteListingId">
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('deleteOverlay').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-sm btn-red"><i class="fas fa-trash"></i> Delete Forever</button>
            </div>
        </form>
    </div>
</div>

<!-- Decline Modal -->
<div class="adm-overlay" id="declineOverlay">
    <div class="adm-modal">
        <h3>❌ Decline Listing</h3>
        <p>Please provide a reason for declining this listing. This will be sent to the host, and the listing will be deleted.</p>
        <form method="POST" action="actions/decline-listing.php">
            <input type="hidden" name="listing_id" id="declineListingId">
            <textarea name="reason" rows="4" placeholder="e.g. Incomplete information, fake photos..." required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-outline" onclick="closeDeclineModal()">Cancel</button>
                <button type="submit" class="btn-sm btn-red"><i class="fas fa-times"></i> Decline & Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFlagModal(id) {
    document.getElementById('flagListingId').value = id;
    document.getElementById('flagOverlay').classList.add('open');
}
function closeFlagModal() {
    document.getElementById('flagOverlay').classList.remove('open');
}
function openDeleteModal(id, title) {
    document.getElementById('deleteListingId').value = id;
    document.getElementById('deleteTitle').textContent = '"' + title + '"';
    document.getElementById('deleteOverlay').classList.add('open');
}
function openDeclineModal(id) {
    document.getElementById('declineListingId').value = id;
    document.getElementById('declineOverlay').classList.add('open');
}
function closeDeclineModal() {
    document.getElementById('declineOverlay').classList.remove('open');
}
document.querySelectorAll('.adm-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function printAll() {
    document.body.classList.remove('print-single');
    window.print();
}
function printSingle(id) {
    document.body.classList.add('print-single');
    document.querySelectorAll('tbody tr').forEach(row => row.classList.remove('print-target'));
    document.getElementById('row-' + id).classList.add('print-target');
    window.print();
}
</script>

    <div class="print-footer" style="display:none;">
        StayHub Admin &bull; Listings Report &bull; Printed <?php echo date('d/m/Y \\a\\t H:i'); ?>
    </div>
</body>
</html>