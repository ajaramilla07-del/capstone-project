<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['staff']);

$message = '';
$messageType = 'success';
$selectedStatus = normalizeOrderStatus(trim((string) ($_GET['status'] ?? 'All')));
$search = trim((string) ($_GET['search'] ?? ''));
$allowedFilters = ['All', 'Pending', 'Preparing', 'Ready for pickup', 'Out for delivery'];
if (!in_array($selectedStatus, $allowedFilters, true)) {
    $selectedStatus = 'All';
}

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = normalizeOrderStatus(trim((string) ($_POST['status'] ?? '')));

        if ($orderId <= 0 || !in_array($status, ['Pending', 'Preparing', 'Ready for pickup'], true)) {
            $message = 'Choose a valid preparation status.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id AND status NOT IN ("Out for delivery", "Delivered")');
            $stmt->execute([':status' => $status, ':id' => $orderId]);
            $message = $stmt->rowCount() > 0 ? 'Order status updated.' : 'This order is already with a rider or completed.';
            $messageType = $stmt->rowCount() > 0 ? 'success' : 'error';
        }
    }

    $counts = [
        'all' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'pending' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Pending"')->fetchColumn(),
        'preparing' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Preparing"')->fetchColumn(),
        'ready' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Ready for pickup"')->fetchColumn(),
    ];

    $where = [];
    $params = [];
    if ($selectedStatus !== 'All') {
        $where[] = 'o.status = :status';
        $params[':status'] = $selectedStatus;
    }
    if ($search !== '') {
        $where[] = '(CAST(o.id AS CHAR) LIKE :search OR c.name LIKE :search OR o.delivery_address LIKE :search OR dc.area_name LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare('SELECT o.id, o.status, o.total_amount, o.created_at, o.delivery_address, c.name AS customer_name, dc.area_name, r.name AS rider_name, GROUP_CONCAT(CONCAT(od.quantity, "x ", p.name) ORDER BY p.name SEPARATOR ", ") AS items FROM orders o JOIN users c ON c.id = o.customer_id LEFT JOIN delivery_coverages dc ON dc.id = o.delivery_coverage_id LEFT JOIN users r ON r.id = o.rider_id LEFT JOIN order_details od ON od.order_id = o.id LEFT JOIN products p ON p.id = od.product_id' . $whereSql . ' GROUP BY o.id, o.status, o.total_amount, o.created_at, o.delivery_address, c.name, dc.area_name, r.name ORDER BY o.created_at DESC');
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Throwable $exception) {
    $orders = [];
    $counts = ['all' => 0, 'pending' => 0, 'preparing' => 0, 'ready' => 0];
    $message = 'Unable to load staff orders right now.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Orders</title>
    <style>
        :root { --sidebar-bg:#2e5d2f; --sidebar-dark:#234a28; --bg-green:#dcead9; --text:#1e1e1e; --muted:#4b4e4a; --line:rgba(58,81,58,.18); --white:#fff; --soft:#eef7f0; --warning:#fff4d6; --blue:#e0f2fe; }
        * { box-sizing:border-box; }
        html, body { margin:0; min-height:100%; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background:var(--bg-green); color:var(--text); }
        .shell { display:flex; min-height:100vh; }
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
        .wrap { max-width:1280px; margin:0 auto; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px; }
        .metric, .panel { background:var(--white); border:1px solid var(--line); border-radius:18px; padding:18px; box-shadow:0 12px 32px rgba(17,17,17,.04); }
        .metric strong { display:block; font-size:2rem; margin-bottom:4px; }
        .muted { color:var(--muted); font-size:.9rem; }
        .toolbar { display:flex; gap:12px; align-items:end; flex-wrap:wrap; margin-bottom:18px; }
        label { display:grid; gap:6px; font-weight:700; }
        input, select, button { padding:11px 12px; border:1px solid var(--line); border-radius:10px; font:inherit; }
        input { min-width:240px; }
        button { background:linear-gradient(135deg,#5ca66e,#3f8a56); color:#fff; border:0; font-weight:700; cursor:pointer; }
        .message { padding:10px 12px; border-radius:10px; margin-bottom:16px; font-weight:600; }
        .message.success { background:var(--soft); color:#184d2d; }
        .message.error { background:#fff1f0; color:#8f2424; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 8px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; }
        th { font-size:.8rem; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
        .status { display:inline-block; padding:6px 10px; border-radius:999px; font-size:.75rem; font-weight:700; background:var(--warning); color:#7a5a00; }
        .status.preparing { background:var(--soft); color:#184d2d; }
        .status.ready { background:var(--blue); color:#0f4c81; }
        .status.delivered, .status.out { background:#dcfce7; color:#166534; }
        .order-form { display:flex; gap:8px; align-items:center; }
        .order-form select { min-width:155px; }
        .items { margin-top:6px; color:var(--text); font-size:.9rem; }
        @media (max-width:900px) { .shell{flex-direction:column;} .sidebar{width:100%;} .nav{display:grid;grid-template-columns:repeat(2,minmax(130px,1fr));} .stats{grid-template-columns:1fr 1fr;} .main-inner{padding:18px 14px;} table{display:block;overflow-x:auto;} }
        @media (max-width:560px) { .stats{grid-template-columns:1fr;} input{min-width:100%;width:100%;} }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="logo-wrap"><div class="logo-mark" aria-label="Logo"></div></div>
            <nav class="nav" aria-label="Staff navigation">
                <a class="nav-item active" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="../logout.php"><span class="nav-icon"></span>Logout</a>
            </nav>
        </aside>
        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger"><span></span><span></span><span></span></span><span>Staff Orders</span></div>
                <span class="muted">Welcome, <?php echo htmlspecialchars(currentUser()['name'] ?? 'Staff'); ?></span>
            </header>
            <main class="main-inner">
                <div class="wrap">
                    <?php if ($message): ?><div class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                    <section class="stats">
                        <div class="metric"><strong><?php echo $counts['all']; ?></strong><span class="muted">All orders</span></div>
                        <div class="metric"><strong><?php echo $counts['pending']; ?></strong><span class="muted">Pending</span></div>
                        <div class="metric"><strong><?php echo $counts['preparing']; ?></strong><span class="muted">Preparing</span></div>
                        <div class="metric"><strong><?php echo $counts['ready']; ?></strong><span class="muted">Ready for pickup</span></div>
                    </section>
                    <section class="panel">
                        <form method="get" action="orders.php" class="toolbar">
                            <label>Search orders<input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order, customer, area, address" /></label>
                            <label>Filter status<select name="status"><option value="All">All statuses</option><?php foreach ($allowedFilters as $filter): if ($filter === 'All') continue; ?><option value="<?php echo htmlspecialchars($filter); ?>" <?php echo $selectedStatus === $filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($filter); ?></option><?php endforeach; ?></select></label>
                            <button type="submit">Filter orders</button>
                        </form>
                        <table>
                            <thead><tr><th>Order</th><th>Customer and delivery</th><th>Total</th><th>Status</th><th>Staff action</th></tr></thead>
                            <tbody>
                            <?php if (!$orders): ?><tr><td colspan="5">No orders match the current filter.</td></tr>
                            <?php else: foreach ($orders as $order): $statusValue = normalizeOrderStatus((string) ($order['status'] ?? 'Pending')); if ($statusValue === 'Preparing') { $statusClass = 'preparing'; } elseif ($statusValue === 'Ready for pickup') { $statusClass = 'ready'; } elseif ($statusValue === 'Out for delivery') { $statusClass = 'out'; } elseif ($statusValue === 'Delivered') { $statusClass = 'delivered'; } else { $statusClass = 'pending'; } ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $order['id']; ?></strong><br><span class="muted"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['created_at']))); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br><span class="items">Items: <?php echo htmlspecialchars($order['items'] ?: 'No item details'); ?></span><br><span class="muted"><?php echo htmlspecialchars(($order['area_name'] ?: 'Delivery area') . ' · ' . ($order['delivery_address'] ?: 'No address')); ?></span><?php if ($order['rider_name']): ?><br><span class="muted">Rider: <?php echo htmlspecialchars($order['rider_name']); ?></span><?php endif; ?></td>
                                    <td>₱<?php echo number_format((float) $order['total_amount'], 0); ?></td>
                                    <td><span class="status <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusValue); ?></span></td>
                                    <td><?php if (in_array($statusValue, ['Out for delivery', 'Delivered'], true)): ?><span class="muted">Rider handling</span><?php else: ?><form method="post" action="orders.php" class="order-form"><input type="hidden" name="action" value="update_status" /><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>" /><select name="status"><option value="Pending" <?php echo $statusValue === 'Pending' ? 'selected' : ''; ?>>Pending</option><option value="Preparing" <?php echo $statusValue === 'Preparing' ? 'selected' : ''; ?>>Preparing</option><option value="Ready for pickup" <?php echo $statusValue === 'Ready for pickup' ? 'selected' : ''; ?>>Ready for pickup</option></select><button type="submit">Save</button></form><?php endif; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
