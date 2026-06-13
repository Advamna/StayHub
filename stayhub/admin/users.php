<?php
$activePage = 'users';
require_once 'guard.php';
require_once '../config.php';

$msg = '';
$msgType = '';
if (isset($_GET['done'])) {
    $map = [
        'banned'   => ['Account has been banned successfully.',         'success'],
        'unbanned' => ['Account has been reinstated.',                   'success'],
        'deleted'  => ['Account and all associated data were deleted.',  'success'],
        'error'    => ['Something went wrong. Please try again.',        'error'],
    ];
    [$msg, $msgType] = $map[$_GET['done']] ?? ['', ''];
}

$highlight = (int)($_GET['highlight'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$filter    = $_GET['filter'] ?? 'all'; // all | banned | hosts

$where  = ["u.is_admin = 0"]; // never show the admin itself
$params = [];
if ($search !== '') {
    $where[]  = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter === 'banned') { $where[] = "u.is_banned = 1"; }
if ($filter === 'hosts')  { $where[] = "u.is_host = 1"; }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT u.id, u.name, u.email, u.phone, u.is_host, u.is_banned, u.ban_reason,
               u.created_at, u.avatar,
               (SELECT COUNT(*) FROM listings l WHERE l.user_id = u.id) AS listing_count,
               (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id) AS booking_count
        FROM users u
        $whereSql
        ORDER BY u.created_at DESC";

$stmt  = $params ? sqlsrv_query($conn, $sql, $params) : sqlsrv_query($conn, $sql);
$users = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $users[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – StayHub Admin</title>
    <link rel="icon" type="image/png" href="../StayHubIcon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        <?php include 'admin-style.php'; ?>
        .highlight-row { background: #1a1f35 !important; box-shadow: inset 3px 0 0 #ff385c; }
        
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
        /* admin users extras */
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
            <strong>Admin &mdash; Users Report</strong><br>
            <?php echo count($users); ?> user<?php echo count($users)!=1?'s':''; ?> total<br>
            Printed: <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <div class="page-header">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div>
                <h1>Users</h1>
                <p>Manage, ban, or remove user accounts</p>
            </div>
            <div>
                <span class="badge badge-info" style="font-size:13px; padding:6px 14px; margin-right:10px;"><?php echo count($users); ?> user<?php echo count($users)!=1?'s':''; ?></span>
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

    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search by name or email…" value="<?php echo htmlspecialchars($search); ?>">
        <select name="filter">
            <option value="all"    <?php echo $filter==='all'    ?'selected':''; ?>>All users</option>
            <option value="hosts"  <?php echo $filter==='hosts'  ?'selected':''; ?>>Hosts only</option>
            <option value="banned" <?php echo $filter==='banned' ?'selected':''; ?>>Banned only</option>
        </select>
        <button type="submit" class="btn-sm btn-primary">Filter</button>
        <?php if ($search || $filter!=='all'): ?>
            <a href="users.php" class="btn-sm btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <div class="section-card" style="padding:0; overflow:hidden;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Listings</th>
                    <th>Bookings</th>
                    <th>Status</th>
                    <th>Ban reason</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" style="text-align:center; padding:40px; color:#8892a4;">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u):
                $avatarSrc = !empty($u['avatar']) ? 'data:image/jpeg;base64,'.base64_encode($u['avatar']) : '../img/default-avatar.png';
                $isHighlight = ($highlight === (int)$u['id']);
            ?>
            <tr id="user-<?php echo $u['id']; ?>" class="<?php echo $isHighlight ? 'highlight-row' : ''; ?>">
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img class="user-avatar" src="<?php echo $avatarSrc; ?>" alt="">
                        <div>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div style="color:#8892a4; font-size:11px;">ID #<?php echo $u['id']; ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;"><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <?php if ($u['is_host']): ?>
                        <span class="badge badge-info"><i class="fas fa-home"></i> Host</span>
                    <?php else: ?>
                        <span class="badge" style="background:#1a1e2e; color:#8892a4;">Guest</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?php echo $u['listing_count']; ?></td>
                <td style="text-align:center;"><?php echo $u['booking_count']; ?></td>
                <td>
                    <?php if ($u['is_banned']): ?>
                        <span class="badge badge-danger"><i class="fas fa-ban"></i> Banned</span>
                    <?php else: ?>
                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:150px; white-space:normal; font-size:12px; color:#fbbf24;">
                    <?php echo $u['is_banned'] ? htmlspecialchars($u['ban_reason'] ?? '—') : '<span style="color:#8892a4;">—</span>'; ?>
                </td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn-sm btn-outline" style="margin-right:4px;" onclick="printSingle(<?php echo $u['id']; ?>)">
                        <i class="fas fa-print"></i>
                    </button>
                    <?php if (!$u['is_banned']): ?>
                    <button class="btn-sm btn-red" onclick="openBanModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')">
                        <i class="fas fa-ban"></i> Ban
                    </button>
                    <?php else: ?>
                    <a href="actions/unban-user.php?id=<?php echo $u['id']; ?>" class="btn-sm btn-green">
                        <i class="fas fa-check"></i> Unban
                    </a>
                    <?php endif; ?>

                    <?php if ($u['listing_count'] > 0): ?>
                    <a href="listings.php?search=<?php echo urlencode($u['email']); ?>" class="btn-sm btn-outline" style="margin-left:4px;">
                        <i class="fas fa-home"></i> Listings
                    </a>
                    <?php endif; ?>

                    <button class="btn-sm btn-red" style="margin-left:4px;" onclick="openDeleteUserModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ban Modal -->
<div class="adm-overlay" id="banOverlay">
    <div class="adm-modal">
        <h3>🚫 Ban Account</h3>
        <p>Banning <strong id="banUserName"></strong> will immediately block their access and show a ban message on login. Provide a reason below.</p>
        <form method="POST" action="actions/ban-user.php">
            <input type="hidden" name="user_id" id="banUserId">
            <textarea name="reason" rows="3" placeholder="e.g. Repeated misleading listings, discrimination, payment fraud…" required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('banOverlay').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-sm btn-red"><i class="fas fa-ban"></i> Ban Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div class="adm-overlay" id="deleteUserOverlay">
    <div class="adm-modal">
        <h3>🗑️ Delete Account</h3>
        <p>Permanently delete <strong id="deleteUserName"></strong>? This removes their account, all listings, bookings and cannot be undone.</p>
        <form method="POST" action="actions/delete-user.php">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('deleteUserOverlay').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-sm btn-red"><i class="fas fa-trash"></i> Delete Forever</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(id, name) {
    document.getElementById('banUserId').value  = id;
    document.getElementById('banUserName').textContent = name;
    document.getElementById('banOverlay').classList.add('open');
}
function openDeleteUserModal(id, name) {
    document.getElementById('deleteUserId').value  = id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserOverlay').classList.add('open');
}
document.querySelectorAll('.adm-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// Auto-scroll to the highlighted user
const hlRow = document.querySelector('.highlight-row');
if (hlRow) hlRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

function printAll() {
    document.body.classList.remove('print-single');
    window.print();
}
function printSingle(id) {
    document.body.classList.add('print-single');
    document.querySelectorAll('tbody tr').forEach(row => row.classList.remove('print-target'));
    document.getElementById('user-' + id).classList.add('print-target');
    window.print();
}
</script>

    <div class="print-footer" style="display:none;">
        StayHub Admin &bull; Users Report &bull; Printed <?php echo date('d/m/Y'); ?>
    </div>
</body>
</html>
