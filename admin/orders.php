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
        :root { --bg:#f5f7f3; --panel:#ffffff; --line:rgba(17,17,17,0.08); --green:#5ca66e; --green-dark:#3f8a56; --text:#111; --muted:#5b5b5b; --soft:#eef7f0; --warning:#fff4d6; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; background:var(--bg); color:var(--text); padding:24px; }
        .wrap { max-width:1200px; margin:0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .link { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; background:#fff; border:1px solid var(--line); color:var(--text); }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:20px; box-shadow:0 12px 32px rgba(17,17,17,0.04); }
        .message { background:var(--soft); color:#184d2d; border:1px solid rgba(92,166,110,0.2); border-radius:10px; padding:10px 12px; margin-bottom:16px; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:12px 8px; border-bottom:1px solid var(--line); vertical-align:top; }
        th { font-size:0.8rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-size:0.75rem; font-weight:700; }
        .badge.pending { background:var(--warning); color:#7a5a00; }
        .badge.preparing { background:var(--soft); color:#184d2d; }
        .badge.ready-for-pickup, .badge.out-for-delivery { background:#e0f2fe; color:#0f4c81; }
        .badge.delivered { background:#dcfce7; color:#166534; }
        @media (max-width:800px) { table { display:block; overflow-x:auto; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <div><h1>Admin Orders</h1></div>
            <div class="nav">
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="products.php">Products</a>
                <a class="link" href="../logout.php">Logout</a>
            </div>
        </div>

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
                                <td>Kes.<?php echo number_format((float) $order['total_amount'], 0); ?></td>
                                <td><span class="badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusValue); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
