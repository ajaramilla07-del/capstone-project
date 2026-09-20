<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$message = '';

try {
    $pdo = getDbConnection();
    $orders = $pdo->query('SELECT o.id, o.status, o.total_amount, o.created_at, o.delivery_address, u.name AS customer_name, dc.area_name, r.name AS rider_name FROM orders o JOIN users u ON u.id = o.customer_id LEFT JOIN delivery_coverages dc ON dc.id = o.delivery_coverage_id LEFT JOIN users r ON r.id = o.rider_id ORDER BY o.created_at DESC')->fetchAll();
} catch (Throwable $e) {
    $orders = [];
    $message = 'Unable to load orders right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Orders</title>
    <style>
        :root {
            --sidebar-bg: #2e5d2f;
            --sidebar-dark: #234a28;
            --bg-green: #dcead9;
            --text: #1e1e1e;
            --muted: #4b4e4a;
            --line: rgba(58, 81, 58, 0.18);
            --white: #ffffff;
            --soft: #eef7f0;
            --warning: #fff4d6;
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
        .message { background: var(--soft); color:#184d2d; border:1px solid rgba(92,166,110,0.2); border-radius:10px; padding:10px 12px; margin-bottom:16px; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:12px 8px; border-bottom:1px solid var(--line); vertical-align:top; }
        th { font-size:0.8rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-size:0.75rem; font-weight:700; }
        .badge.pending { background:var(--warning); color:#7a5a00; }
        .badge.preparing { background:var(--soft); color:#184d2d; }
        .badge.ready-for-pickup, .badge.out-for-delivery { background:#e0f2fe; color:#0f4c81; }
        .badge.delivered { background:#dcfce7; color:#166534; }
        @media (max-width: 900px) { .dashboard-shell { flex-direction: column; } .sidebar { width: 100%; } table { display:block; overflow-x:auto; } }
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
                <a class="nav-item active" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger" aria-label="Menu button"><span></span><span></span><span></span></span><span>Orders</span></div>
                <div><a href="../logout.php" style="text-decoration:none; color:#111; font-weight:700;">Logout</a></div>
            </header>

            <main class="main-inner">
                <div class="wrap">
                    <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Area</th>
                                    <th>Rider</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="6">No orders found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php $statusValue = normalizeOrderStatus((string) ($order['status'] ?? 'Pending')); $statusClass = strtolower(str_replace(' ', '-', $statusValue)); ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo (int) $order['id']; ?></strong><br>
                                                <span style="color:var(--muted); font-size:0.85rem;"><?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></td>
                                            <td><?php echo htmlspecialchars($order['area_name'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($order['rider_name'] ?: 'Unassigned'); ?></td>
                                            <td>₱<?php echo number_format((float) $order['total_amount'], 0); ?></td>
                                            <td><span class="badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusValue); ?></span></td>
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
