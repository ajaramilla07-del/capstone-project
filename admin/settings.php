<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Settings</title>
    <style>
        :root { --sidebar-bg:#2e5d2f; --sidebar-dark:#234a28; --bg-green:#dcead9; --text:#1e1e1e; --line:rgba(58,81,58,.18); --white:#fff; --soft:#eef7f0; }
        * { box-sizing:border-box; }
        html, body { margin:0; min-height:100%; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background:var(--bg-green); color:var(--text); }
        .dashboard-shell { display:flex; min-height:100vh; }
        .sidebar { width:220px; background:linear-gradient(180deg,var(--sidebar-bg),var(--sidebar-dark)); color:#edf3f8; padding:18px 0 10px; }
        .logo-wrap { display:flex; justify-content:center; padding:12px 0 18px; }
        .logo-mark { width:82px; height:82px; border-radius:50%; background:rgba(255,255,255,.08); position:relative; }
        .logo-mark::before { content:""; width:38px; height:38px; background:linear-gradient(135deg,rgba(255,255,255,.8),rgba(255,255,255,.3)); border-radius:12px; transform:rotate(45deg); position:absolute; left:22px; top:22px; }
        .logo-mark::after { content:""; width:16px; height:16px; border:3px solid #2b3e4d; border-top-color:transparent; border-left-color:transparent; position:absolute; left:31px; top:31px; border-radius:4px; transform:rotate(45deg); }
        .nav { display:flex; flex-direction:column; gap:10px; padding:0 10px; }
        .nav-item { display:flex; align-items:center; gap:12px; padding:14px 12px; border-radius:10px; color:rgba(255,255,255,.9); text-decoration:none; font-weight:700; }
        .nav-item.active, .nav-item:hover { background:rgba(255,255,255,.08); }
        .nav-icon { width:12px; height:12px; border-radius:4px; background:rgba(255,255,255,.7); display:inline-block; }
        .content { flex:1; background:rgba(255,255,255,.08); }
        .topbar { height:78px; display:flex; align-items:center; justify-content:space-between; padding:0 26px; background:rgba(255,255,255,.75); border-bottom:1px solid rgba(17,17,17,.08); }
        .topbar-title { display:flex; align-items:center; gap:12px; font-weight:700; }
        .burger { width:18px; height:14px; display:grid; gap:3px; }
        .burger span { display:block; height:2px; background:#1a2321; border-radius:2px; }
        .main-inner { padding:24px; }
        .wrap { max-width:1200px; margin:0 auto; }
        .panel { background:var(--white); border:1px solid var(--line); border-radius:18px; padding:20px; box-shadow:0 12px 32px rgba(17,17,17,.04); }
        .settings-list { display:grid; gap:14px; }
        .setting { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:14px 16px; border:1px solid var(--line); border-radius:12px; }
        .status { display:inline-block; background:var(--soft); color:#1d6d40; border-radius:999px; padding:6px 10px; font-size:.72rem; font-weight:700; }
        .settings-link { display:inline-block; margin-top:18px; padding:10px 14px; border-radius:10px; color:#fff; background:linear-gradient(135deg,#5ca66e,#3f8a56); text-decoration:none; font-weight:700; }
        @media (max-width:900px) { .dashboard-shell { flex-direction:column; } .sidebar { width:100%; } }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="logo-wrap"><div class="logo-mark" aria-label="Logo"></div></div>
            <nav class="nav" aria-label="Sidebar navigation">
                <a class="nav-item" href="dashboard.php"><span class="nav-icon"></span>Dashboard</a>
                <a class="nav-item" href="sales.php"><span class="nav-icon"></span>Sales</a>
                <a class="nav-item" href="growth.php"><span class="nav-icon"></span>Growth</a>
                <a class="nav-item" href="products.php"><span class="nav-icon"></span>Products</a>
                <a class="nav-item" href="locations.php"><span class="nav-icon"></span>Locations</a>
                <a class="nav-item" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item active" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>
        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger"><span></span><span></span><span></span></span><span>Settings</span></div>
                <a href="../logout.php" style="text-decoration:none;color:#111;font-weight:700;">Logout</a>
            </header>
            <main class="main-inner">
                <div class="wrap">
                    <div class="panel">
                        <h2>System settings</h2>
                        <div class="settings-list">
                            <div class="setting"><div><strong>Business profile</strong><br><small>Store name, contact details, and branding</small></div><span class="status">Configured</span></div>
                            <div class="setting"><div><strong>Notifications</strong><br><small>Email and in-app alerts for orders and updates</small></div><span class="status">Enabled</span></div>
                            <div class="setting"><div><strong>Security</strong><br><small>Admin access and session controls</small></div><span class="status">Protected</span></div>
                        </div>
                        <a class="settings-link" href="locations.php">Manage delivery locations</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
