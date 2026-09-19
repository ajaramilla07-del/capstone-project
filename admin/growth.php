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
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f3; color: #111; padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .link { display: inline-block; padding: 10px 14px; border-radius: 10px; text-decoration: none; background: #fff; border: 1px solid rgba(17,17,17,0.08); color: #111; font-weight: 700; }
        .panel { background: #fff; border: 1px solid rgba(17,17,17,0.08); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 16px; margin-bottom: 18px; }
        .stat { background: #f7faf7; border: 1px solid rgba(17,17,17,0.06); border-radius: 12px; padding: 18px; }
        .stat strong { display: block; font-size: 2rem; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid rgba(17,17,17,0.08); text-align: left; }
        @media (max-width: 800px) { .stat-grid { grid-template-columns: 1fr; } table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <h1>Growth</h1>
            <div class="nav">
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="sales.php">Sales</a>
                <a class="link" href="users.php">Users</a>
                <a class="link" href="../logout.php">Logout</a>
            </div>
        </div>

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
</body>
</html>
