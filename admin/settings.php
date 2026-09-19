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
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f3; color: #111; padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .link { display: inline-block; padding: 10px 14px; border-radius: 10px; text-decoration: none; background: #fff; border: 1px solid rgba(17,17,17,0.08); color: #111; font-weight: 700; }
        .panel { background: #fff; border: 1px solid rgba(17,17,17,0.08); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        .settings-list { display: grid; gap: 14px; }
        .setting { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px 16px; border: 1px solid rgba(17,17,17,0.08); border-radius: 12px; }
        .status { display: inline-block; background: #dff7eb; color: #1d6d40; border-radius: 999px; padding: 6px 10px; font-size: 0.72rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <h1>Settings</h1>
            <div class="nav">
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="sales.php">Sales</a>
                <a class="link" href="users.php">Users</a>
                <a class="link" href="../logout.php">Logout</a>
            </div>
        </div>

        <div class="panel">
            <div class="settings-list">
                <div class="setting">
                    <div>
                        <strong>Business profile</strong><br>
                        <small>Store name, contact details, and branding</small>
                    </div>
                    <span class="status">Configured</span>
                </div>
                <div class="setting">
                    <div>
                        <strong>Delivery coverage</strong><br>
                        <small>Active regions and rider assignment rules</small>
                    </div>
                    <span class="status">Active</span>
                </div>
                <div class="setting">
                    <div>
                        <strong>Notifications</strong><br>
                        <small>Email and in-app alerts for orders and updates</small>
                    </div>
                    <span class="status">Enabled</span>
                </div>
                <div class="setting">
                    <div>
                        <strong>Security</strong><br>
                        <small>Admin access and session controls</small>
                    </div>
                    <span class="status">Protected</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
