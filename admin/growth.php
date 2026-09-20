<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

try {
    $pdo = getDbConnection();
    $growth = $pdo->query('SELECT DATE(created_at) AS date, COUNT(*) AS new_users FROM users WHERE role = "customer" GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7')->fetchAll();
    $totalCustomers = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();
    $totalRevenue = (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders')->fetchColumn();
} catch (Throwable $e) {
    $growth = [];
    $totalCustomers = 0;
    $totalRevenue = 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Growth</title>
    <style>
        :root {
            --sidebar-bg: #2e5d2f;
            --sidebar-dark: #234a28;
            --bg-green: #dcead9;
            --text: #1e1e1e;
            --line: rgba(58, 81, 58, 0.18);
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: var(--bg-green); color: var(--text); }
        body { padding: 0; }
        .dashboard-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: linear-gradient(180deg, var(--sidebar-bg), var(--sidebar-dark)); color: #edf3f8; padding: 18px 0 10px; }
        .logo-wrap { display: flex; justify-content: center; padding: 12px 0 18px; }
        .logo-mark { width: 82px; height: 82px; border-radius: 50%; background: rgba(255,255,255,0.08); position: relative; }
        .logo-mark::before { content: ""; width: 38px; height: 38px; background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.3)); border-radius: 12px; transform: rotate(45deg); position: absolute; left: 22px; top: 22px; }
        .logo-mark::after { content: ""; width: 16px; height: 16px; border: 3px solid #2b3e4d; border-top-color: transparent; border-left-color: transparent; position: absolute; left: 31px; top: 31px; border-radius: 4px; transform: rotate(45deg); }
        .nav { display: flex; flex-direction: column; gap: 10px; padding: 0 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 12px; border-radius: 10px; color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 700; }
        .nav-item.active, .nav-item:hover { background: rgba(255,255,255,0.08); }
        .nav-icon { width: 12px; height: 12px; border-radius: 4px; background: rgba(255,255,255,0.7); display: inline-block; }
        .content { flex: 1; background: rgba(255,255,255,0.08); }
        .topbar { height: 78px; display: flex; align-items: center; justify-content: space-between; padding: 0 26px; background: rgba(255,255,255,0.75); border-bottom: 1px solid rgba(17,17,17,0.08); }
        .topbar-title { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .burger { width: 18px; height: 14px; display: grid; gap: 3px; }
        .burger span { display: block; height: 2px; background: #1a2321; border-radius: 2px; }
        .main-inner { padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .panel { background: var(--white); border: 1px solid var(--line); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 16px; margin-bottom: 18px; }
        .stat { background: #f7faf7; border: 1px solid rgba(17,17,17,0.06); border-radius: 12px; padding: 18px; }
        .stat strong { display: block; font-size: 2rem; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid rgba(17,17,17,0.08); text-align: left; }
        @media (max-width: 900px) { .dashboard-shell { flex-direction: column; } .sidebar { width: 100%; } .stat-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="logo-wrap"><div class="logo-mark" aria-label="Logo"></div></div>
            <nav class="nav" aria-label="Sidebar navigation">
                <a class="nav-item" href="dashboard.php"><span class="nav-icon"></span>Dashboard</a>
                <a class="nav-item" href="sales.php"><span class="nav-icon"></span>Sales</a>
                <a class="nav-item active" href="growth.php"><span class="nav-icon"></span>Growth</a>
                <a class="nav-item" href="products.php"><span class="nav-icon"></span>Products</a>
                <a class="nav-item" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger" aria-label="Menu button"><span></span><span></span><span></span></span><span>Growth</span></div>
                <div><a href="../logout.php" style="text-decoration:none; color:#111; font-weight:700;">Logout</a></div>
            </header>

            <main class="main-inner">
                <div class="wrap">
                    <div class="panel">
                        <div class="stat-grid">
                            <div class="stat">
                                <strong><?php echo number_format($totalCustomers); ?></strong>
                                <span>Total customers</span>
                            </div>
                            <div class="stat">
                                <strong><?php echo number_format((float) $totalRevenue, 0); ?></strong>
                                <span>Total revenue</span>
                            </div>
                            <div class="stat">
                                <strong><?php echo count($growth) > 0 ? (int) array_sum(array_map(static fn($row) => (int) ($row['new_users'] ?? 0), $growth)) : 0; ?></strong>
                                <span>New users</span>
                            </div>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>New customers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($growth)): ?>
                                    <tr><td colspan="2">No growth data available yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($growth as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['date']))); ?></td>
                                            <td><?php echo (int) $row['new_users']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
