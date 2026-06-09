<?php
// Shared CSS — included via inline <?php include 'admin-style.php'; ?>
// This file just returns a string, so we echo nothing on its own.
?>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family:'Inter','Segoe UI',sans-serif; background:#0f1117; color:#e2e8f0; display:flex; min-height:100vh; }

/* ── Sidebar ── */
.sidebar {
    width: 240px; flex-shrink: 0;
    background: #181c27;
    border-right: 1px solid #252836;
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; height: 100vh;
    z-index: 200;
}
.sidebar-logo {
    padding: 28px 24px 20px;
    font-size: 20px; font-weight: 800; color: #ff385c;
    border-bottom: 1px solid #252836;
    display: flex; align-items: center; gap: 10px;
}
.sidebar-logo span { font-size: 12px; font-weight: 500; color: #666; margin-top: 2px; }
.sidebar-logo div { display: flex; flex-direction: column; }
.sidebar-nav { padding: 16px 0; flex: 1; }
.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 24px; color: #8892a4;
    text-decoration: none; font-size: 14px; font-weight: 500;
    transition: all 0.2s; border-left: 3px solid transparent;
}
.nav-item:hover { background: #1e2235; color: #e2e8f0; }
.nav-item.active { background: #1e2235; color: #ff385c; border-left-color: #ff385c; }
.nav-item i { width: 18px; text-align: center; font-size: 15px; }
.sidebar-footer { padding: 16px 24px; border-top: 1px solid #252836; }
.sidebar-footer a { color: #8892a4; font-size: 13px; text-decoration: none; display:flex; align-items:center; gap:8px; }
.sidebar-footer a:hover { color: #e2e8f0; }

/* ── Main Content ── */
.main-content { margin-left: 240px; flex: 1; padding: 36px 40px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom: 32px; }
.page-header h1 { font-size: 26px; font-weight: 700; margin-bottom: 4px; }
.page-header p { color: #8892a4; font-size: 14px; }

/* ── Stats grid ── */
.stats-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(170px,1fr)); gap: 18px; margin-bottom: 32px; }
.stat-card { background: #181c27; border: 1px solid #252836; border-radius: 14px; padding: 22px 20px; display:flex; align-items:center; gap: 16px; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-size: 20px; flex-shrink: 0; }
.stat-number { font-size: 24px; font-weight: 700; color: #fff; }
.stat-label { font-size: 12px; color: #8892a4; margin-top: 2px; }

/* ── Section cards ── */
.section-card { background: #181c27; border: 1px solid #252836; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
.section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 20px; }
.section-header h2 { font-size: 16px; font-weight: 700; }
.view-all { font-size: 13px; color: #ff385c; text-decoration: none; display:flex; align-items:center; gap: 5px; }
.view-all:hover { opacity: 0.8; }

/* ── Table ── */
.admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.admin-table thead th { color: #8892a4; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #252836; text-align: left; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; }
.admin-table tbody tr { border-bottom: 1px solid #1a1e2e; transition: background 0.15s; }
.admin-table tbody tr:hover { background: #1e2235; }
.admin-table tbody td { padding: 13px 12px; color: #c8d0e0; vertical-align: middle; }
.admin-table tbody td a { color: #a0c4ff; text-decoration: none; }
.admin-table tbody td a:hover { text-decoration: underline; }

/* ── Badges ── */
.badge { display:inline-flex; align-items:center; gap:5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-success { background: #052e16; color: #4ade80; }
.badge-danger  { background: #450a0a; color: #f87171; }
.badge-warning { background: #422006; color: #fbbf24; }
.badge-info    { background: #0c1a3a; color: #93c5fd; }

/* ── Buttons ── */
.btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display:inline-block; transition: all 0.2s; }
.btn-outline { border: 1px solid #3a3f55; color: #c8d0e0; background: transparent; }
.btn-outline:hover { background: #252836; }
.btn-red { background: #7f1d1d; color: #fca5a5; }
.btn-red:hover { background: #991b1b; }
.btn-yellow { background: #422006; color: #fbbf24; }
.btn-yellow:hover { background: #78350f; }
.btn-green { background: #052e16; color: #4ade80; }
.btn-green:hover { background: #14532d; }
.btn-primary { background: #ff385c; color: #fff; }
.btn-primary:hover { background: #e31c5f; }

/* ── Search/filter bar ── */
.filter-bar { display:flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-bar input, .filter-bar select {
    background: #0f1117; border: 1px solid #252836; color: #c8d0e0;
    padding: 9px 14px; border-radius: 8px; font-size: 13px; font-family: inherit;
}
.filter-bar input:focus, .filter-bar select:focus { outline: none; border-color: #ff385c; }
.filter-bar input { flex: 1; min-width: 200px; }

/* ── Modal ── */
.adm-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9000; align-items:center; justify-content:center; }
.adm-overlay.open { display:flex; }
.adm-modal { background: #181c27; border: 1px solid #252836; border-radius: 18px; padding: 32px; max-width: 440px; width: 90%; animation: fadeScale .25s ease; }
@keyframes fadeScale { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }
.adm-modal h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
.adm-modal p  { color: #8892a4; font-size: 14px; margin-bottom: 20px; line-height: 1.6; }
.adm-modal textarea, .adm-modal input[type=text] {
    width: 100%; background: #0f1117; border: 1px solid #252836; color: #c8d0e0;
    border-radius: 8px; padding: 10px 14px; font-size: 13px; font-family: inherit;
    resize: vertical; margin-bottom: 16px;
}
.adm-modal textarea:focus, .adm-modal input[type=text]:focus { outline:none; border-color:#ff385c; }
.modal-actions { display:flex; gap: 10px; justify-content:flex-end; }

/* ── Avatar ── */
.user-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; background: #252836; }

/* ── Listing thumbnail ── */
.listing-thumb { width: 52px; height: 38px; border-radius: 6px; object-fit: cover; background: #252836; }

/* ── Alerts ── */
.adm-alert { padding: 12px 18px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display:flex; gap:10px; align-items:center; font-weight:500; }
.adm-alert-success { background: #052e16; color: #4ade80; border: 1px solid #14532d; }
.adm-alert-error   { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }
