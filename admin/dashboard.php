<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$stats = [
    'users' => 0,
    'customers' => 0,
    'staff' => 0,
    'riders' => 0,
    'admins' => 0,
    'products' => 0,
    'orders' => 0,
    'revenue' => 0.0,
    'pending' => 0,
    'preparing' => 0,
    'ready_for_pickup' => 0,
    'out_for_delivery' => 0,
    'delivered' => 0,
    'areas' => 0,
];
$recentOrders = [];
$riderPerformance = [];
$deliveryAreas = [];
$revenueTrend = [];
$topProducts = [];
$activityFeed = [];
$todaySummary = [
    'orders' => 0,
    'revenue' => 0.0,
    'average' => 0.0,
];

try {
    $pdo = getDbConnection();

    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['customers'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();
    $stats['staff'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "staff"')->fetchColumn();
    $stats['riders'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "rider"')->fetchColumn();
    $stats['admins'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
    $stats['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE status = "active"')->fetchColumn();
    $stats['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $stats['revenue'] = (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders')->fetchColumn();
    $stats['pending'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Pending"')->fetchColumn();
    $stats['preparing'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Preparing"')->fetchColumn();
    $stats['ready_for_pickup'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Ready for pickup"')->fetchColumn();
    $stats['out_for_delivery'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Out for delivery"')->fetchColumn();
    $stats['delivered'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "Delivered"')->fetchColumn();
    $stats['areas'] = (int) $pdo->query('SELECT COUNT(*) FROM delivery_coverages WHERE status = "active"')->fetchColumn();

    $recentOrders = $pdo->query('SELECT o.id, o.total_amount, o.status, o.created_at, u.name AS customer_name, dc.area_name FROM orders o JOIN users u ON u.id = o.customer_id LEFT JOIN delivery_coverages dc ON dc.id = o.delivery_coverage_id ORDER BY o.created_at DESC LIMIT 5')->fetchAll();

    $riderPerformance = $pdo->query('SELECT u.name, COUNT(o.id) AS deliveries, COALESCE(SUM(o.total_amount), 0) AS revenue FROM users u LEFT JOIN orders o ON o.rider_id = u.id WHERE u.role = "rider" GROUP BY u.id, u.name ORDER BY deliveries DESC, revenue DESC LIMIT 5')->fetchAll();

    $deliveryAreas = $pdo->query('SELECT dc.area_name, dc.rider_id, dc.status, u.name AS rider_name FROM delivery_coverages dc LEFT JOIN users u ON u.id = dc.rider_id WHERE dc.status = "active" ORDER BY dc.area_name ASC')->fetchAll();

    $revenueTrend = $pdo->query('SELECT DATE(created_at) AS sale_day, COALESCE(SUM(total_amount), 0) AS revenue FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY sale_day ASC')->fetchAll();

    $topProducts = $pdo->query('SELECT p.name, COALESCE(SUM(od.quantity), 0) AS sold, COALESCE(SUM(od.quantity * od.price), 0) AS revenue FROM products p LEFT JOIN order_details od ON od.product_id = p.id GROUP BY p.id, p.name ORDER BY sold DESC, revenue DESC LIMIT 5')->fetchAll();

    $activityFeed = $pdo->query('SELECT o.id, u.name AS customer_name, o.status, o.created_at, o.total_amount FROM orders o JOIN users u ON u.id = o.customer_id ORDER BY o.created_at DESC LIMIT 5')->fetchAll();

    $todaySummary = $pdo->query('SELECT COUNT(*) AS orders_today, COALESCE(SUM(total_amount), 0) AS revenue_today, COALESCE(AVG(total_amount), 0) AS avg_order FROM orders WHERE DATE(created_at) = CURDATE()')->fetch();
    $todaySummary['orders'] = (int) ($todaySummary['orders_today'] ?? 0);
    $todaySummary['revenue'] = (float) ($todaySummary['revenue_today'] ?? 0.0);
    $todaySummary['average'] = (float) ($todaySummary['avg_order'] ?? 0.0);
} catch (Throwable $e) {
    $stats = [
        'users' => 0,
        'customers' => 0,
        'staff' => 0,
        'riders' => 0,
        'admins' => 0,
        'products' => 0,
        'orders' => 0,
        'revenue' => 0.0,
        'pending' => 0,
        'preparing' => 0,
        'ready_for_pickup' => 0,
        'out_for_delivery' => 0,
        'delivered' => 0,
        'areas' => 0,
    ];
    $recentOrders = [];
    $riderPerformance = [];
    $deliveryAreas = [];
    $revenueTrend = [];
    $topProducts = [];
    $activityFeed = [];
    $todaySummary = [
        'orders' => 0,
        'revenue' => 0.0,
        'average' => 0.0,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <style>
        :root {
            --sidebar-bg: #2e5d2f;
            --sidebar-dark: #234a28;
            --sidebar-accent: #ecf4e6;
            --green: #6ea35f;
            --green-dark: #467b44;
            --green-deep: #2e5d2f;
            --bg-green: #dcead9;
            --soft-green: #ecf4e6;
            --panel-bg: #f7f8f2;
            --card-bg: #f9f9f9;
            --text: #1e1e1e;
            --muted: #4b4e4a;
            --line: rgba(58, 81, 58, 0.18);
            --white: #ffffff;
            --cyan: #54d0df;
            --red: #ea4f4f;
            --amber: #f7b35d;
            --blue: #5bb1e7;
            --danger: #f17070;
            --shadow: 0 18px 40px rgba(51, 81, 44, 0.08);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            width: 100%;
            min-height: 100%;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-green);
            color: var(--text);
        }

        body {
            padding: 0;
        }

        .dashboard-shell {
            display: flex;
            min-height: 100vh;
            background: rgba(255,255,255,0.08);
        }

        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, var(--sidebar-bg), var(--sidebar-dark));
            color: #edf3f8;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            padding: 18px 0 10px;
            box-shadow: inset -1px 0 0 rgba(255,255,255,0.08);
        }

        .logo-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 12px 0 18px;
        }

        .logo-mark {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            position: relative;
            display: grid;
            place-items: center;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,0.08);
        }

        .logo-mark::before {
            content: "";
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.3));
            border-radius: 12px;
            transform: rotate(45deg);
            display: block;
            position: absolute;
        }

        .logo-mark::after {
            content: "";
            width: 16px;
            height: 16px;
            border: 3px solid #2b3e4d;
            border-top-color: transparent;
            border-left-color: transparent;
            position: absolute;
            transform: rotate(45deg);
            border-radius: 4px;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 8px;
            padding: 0 10px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 14px;
            border-radius: 10px;
            font-size: 1.05rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: 0.2s ease;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.06);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            display: inline-block;
            border-radius: 50%;
            position: relative;
            opacity: 0.9;
        }

        .nav-icon.speed::before,
        .nav-icon.location::before,
        .nav-icon.project::before,
        .nav-icon.user::before,
        .nav-icon.growth::before,
        .nav-icon.sales::before,
        .nav-icon.comments::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
            opacity: 0.9;
        }

        .nav-icon.speed::after {
            content: "";
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 -5px 0 rgba(255,255,255,0.8), 0 5px 0 rgba(255,255,255,0.8), 5px 0 0 rgba(255,255,255,0.8), -5px 0 0 rgba(255,255,255,0.8);
        }

        .nav-icon.location::before {
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            inset: 2px 2px 2px 2px;
        }

        .nav-icon.project::before {
            border: none;
            background: linear-gradient(135deg, transparent 45%, rgba(255,255,255,0.8) 45%, rgba(255,255,255,0.8) 55%, transparent 55%);
            border-radius: 0;
        }

        .nav-icon.user::before {
            border-radius: 50% 50% 35% 35%;
        }

        .nav-icon.user::after {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.8);
            left: 50%;
            top: 3px;
            transform: translateX(-50%);
        }

        .nav-icon.growth::before {
            border-left: 0;
            border-top: 0;
            transform: rotate(45deg);
        }

        .nav-icon.sales::before {
            border-radius: 0;
            border: 2px solid rgba(255,255,255,0.8);
            clip-path: polygon(25% 100%, 25% 25%, 75% 25%, 75% 100%);
        }

        .nav-icon.comments::before {
            border-radius: 4px;
        }

        .content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: linear-gradient(90deg, var(--green), var(--green-dark));
            height: 96px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px 0 28px;
            box-shadow: 0 10px 20px rgba(70, 123, 68, 0.18);
        }

        .topbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .burger {
            width: 16px;
            height: 12px;
            position: relative;
            display: inline-block;
        }

        .burger span {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: #fff;
            border-radius: 2px;
        }

        .burger span:nth-child(1) { top: 0; }
        .burger span:nth-child(2) { top: 5px; }
        .burger span:nth-child(3) { bottom: 0; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .icon-box {
            position: relative;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            opacity: 0.95;
        }

        .icon-box.search::before {
            content: "";
            width: 12px;
            height: 12px;
            border: 2px solid #fff;
            border-radius: 50%;
            display: block;
            position: absolute;
        }

        .icon-box.search::after {
            content: "";
            width: 7px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            position: absolute;
            transform: rotate(45deg);
            right: 2px;
            bottom: 4px;
        }

        .icon-box.bell::before {
            content: "";
            width: 14px;
            height: 12px;
            border: 2px solid #fff;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            position: absolute;
            top: 5px;
        }

        .icon-box.bell::after {
            content: "";
            width: 8px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            position: absolute;
            bottom: 5px;
        }

        .badge-counter {
            position: absolute;
            top: -6px;
            right: -8px;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            color: var(--orange);
            font-size: 0.68rem;
            font-weight: 700;
            display: grid;
            place-items: center;
            padding: 0 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .user-dot {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            position: relative;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.8);
        }

        .user-dot::before {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            left: 50%;
            top: 5px;
            transform: translateX(-50%);
        }

        .user-dot::after {
            content: "";
            position: absolute;
            width: 12px;
            height: 8px;
            border-radius: 8px 8px 4px 4px;
            background: rgba(255,255,255,0.9);
            left: 50%;
            bottom: 4px;
            transform: translateX(-50%);
        }

        .main-inner {
            padding: 28px 28px 32px;
            background: rgba(255,255,255,0.08);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(150px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .performance-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 18px;
            margin: 20px 0 18px;
        }

        .chart-card {
            background: rgba(255,255,255,0.82);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
            padding: 20px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            font-weight: 700;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 170px;
            padding-top: 18px;
        }

        .chart-bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            height: 100%;
            gap: 8px;
        }

        .chart-bar {
            width: 100%;
            border-radius: 10px 10px 0 0;
            min-height: 12px;
            background: linear-gradient(180deg, #74b87d, #2f6b3f);
            box-shadow: inset 0 -8px 12px rgba(255,255,255,0.16);
        }

        .chart-day {
            font-size: 0.7rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .metric-card {
            background: rgba(255,255,255,0.82);
            border-radius: 12px;
            min-height: 150px;
            display: grid;
            place-items: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
            padding: 14px;
        }

        .metric-number {
            font-size: clamp(1.7rem, 2.4vw, 2.3rem);
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .metric-label {
            text-transform: uppercase;
            font-size: 0.74rem;
            letter-spacing: 0.12em;
            color: var(--muted);
            font-weight: 700;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 18px;
            margin-top: 18px;
        }

        .panel-card {
            background: rgba(255,255,255,0.82);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
            padding: 20px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(120px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .mini-stat {
            background: rgba(255,255,255,0.8);
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .mini-stat strong {
            display: block;
            font-size: 1.8rem;
            margin-bottom: 4px;
            color: var(--text);
        }

        .mini-stat span {
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            font-size: 0.7rem;
            color: var(--muted);
            font-weight: 700;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .order-list, .rider-list, .area-list, .activity-list, .product-list {
            display: grid;
            gap: 12px;
        }

        .product-item, .activity-item, .area-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.65);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .activity-meta {
            display: grid;
            gap: 3px;
        }

        .product-item strong,
        .activity-item strong {
            font-size: 0.96rem;
        }

        .meta-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef7f0;
            color: #184d2d;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .area-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.65);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .order-row, .rider-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.65);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .order-main, .rider-main {
            display: grid;
            gap: 4px;
        }

        .muted {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dff3ff;
            color: #0b5f89;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pill.pending { background: #fff1bf; color: #7a5a00; }
        .status-pill.preparing { background: #dff7eb; color: #1d6d40; }
        .status-pill.ready-for-pickup { background: #d9f0ff; color: #0f5279; }
        .status-pill.out-for-delivery { background: #efebff; color: #4b3ea8; }
        .status-pill.delivered { background: #ddf7df; color: #1a6f3d; }

        .rider-name {
            font-weight: 700;
        }

        .empty-box {
            border: 1px dashed rgba(0,0,0,0.12);
            background: rgba(255,255,255,0.4);
            border-radius: 10px;
            padding: 18px;
            color: var(--muted);
            text-align: center;
        }

        .topbar-right .icon-box.mail::before {
            content: "";
            width: 14px;
            height: 10px;
            border: 2px solid #fff;
            border-radius: 2px;
            position: absolute;
        }

        .topbar-right .icon-box.mail::after {
            content: "";
            width: 9px;
            height: 9px;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            position: absolute;
            transform: skewY(-30deg) rotate(-45deg);
            top: 7px;
            left: 8px;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }
            .quick-grid {
                grid-template-columns: repeat(2, minmax(140px, 1fr));
            }
            .panel-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .dashboard-shell {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding-bottom: 0;
            }

            .nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(130px, 1fr));
                gap: 8px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 0 14px;
            }

            .main-inner {
                padding: 18px 14px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="logo-wrap">
                <div class="logo-mark" aria-label="Logo"></div>
            </div>

            <nav class="nav" aria-label="Sidebar navigation">
                <a class="nav-item active" href="dashboard.php"><span class="nav-icon speed"></span>Dashboard</a>
                <a class="nav-item" href="sales.php"><span class="nav-icon sales"></span>Sales</a>
                <a class="nav-item" href="growth.php"><span class="nav-icon growth"></span>Growth</a>
                <a class="nav-item" href="products.php"><span class="nav-icon project"></span>Products</a>
                <a class="nav-item" href="orders.php"><span class="nav-icon sales"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon user"></span>Users</a>
                <a class="nav-item" href="settings.php"><span class="nav-icon comments"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title">
                    <span class="burger" aria-label="Menu button">
                        <span></span><span></span><span></span>
                    </span>
                    <span>Dashboard</span>
                </div>

                <div class="topbar-right">
                    <span class="icon-box search" aria-label="Search"></span>
                    <span class="icon-box bell" aria-label="Notifications"></span>
                    <span class="icon-box mail" aria-label="Messages"></span>
                    <span class="user-dot" aria-label="User profile"></span>
                </div>
            </header>

            <main class="main-inner">
                <section class="stats-grid">
                    <div class="metric-card">
                        <div class="metric-number"><?php echo (int) $stats['users']; ?></div>
                        <div class="metric-label">Users</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-number"><?php echo (int) $stats['products']; ?></div>
                        <div class="metric-label">Products</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-number"><?php echo (int) $stats['orders']; ?></div>
                        <div class="metric-label">Orders</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-number"><?php echo (int) $stats['riders']; ?></div>
                        <div class="metric-label">Riders</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-number">₱<?php echo number_format((float) $stats['revenue'], 0); ?></div>
                        <div class="metric-label">Revenue</div>
                    </div>
                </section>

                <section class="quick-grid" aria-label="Order status and user role summary">
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['customers']; ?></strong>
                        <span>Customers</span>
                    </div>
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['staff']; ?></strong>
                        <span>Staff</span>
                    </div>
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['riders']; ?></strong>
                        <span>Riders</span>
                    </div>
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['admins']; ?></strong>
                        <span>Admins</span>
                    </div>
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['pending']; ?></strong>
                        <span>Pending</span>
                    </div>
                    <div class="mini-stat">
                        <strong><?php echo (int) $stats['areas']; ?></strong>
                        <span>Areas</span>
                    </div>
                </section>

                <section class="performance-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <span>7-day revenue</span>
                            <strong>₱<?php echo number_format((float) array_sum(array_map(static fn($day) => (float) ($day['revenue'] ?? 0), $revenueTrend)), 0); ?></strong>
                        </div>
                        <?php
                        $maxRevenue = 0;
                        foreach ($revenueTrend as $day) {
                            $value = (float) ($day['revenue'] ?? 0);
                            if ($value > $maxRevenue) {
                                $maxRevenue = $value;
                            }
                        }
                        ?>
                        <div class="chart-bars">
                            <?php foreach ($revenueTrend as $day): ?>
                                <?php $barHeight = $maxRevenue > 0 ? ((float) ($day['revenue'] ?? 0) / $maxRevenue) * 100 : 0; ?>
                                <div class="chart-bar-wrap">
                                    <div class="chart-bar" style="height: <?php echo (int) max(8, round($barHeight)); ?>%;"></div>
                                    <div class="chart-day"><?php echo htmlspecialchars(date('D', strtotime($day['sale_day']))); ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($revenueTrend)): ?>
                                <div class="empty-box" style="width:100%;">No revenue data available for the last 7 days.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <span>Today</span>
                            <strong>₱<?php echo number_format((float) $todaySummary['revenue'], 0); ?></strong>
                        </div>
                        <div class="order-list">
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Orders</strong>
                                    <span class="muted">Started today</span>
                                </div>
                                <strong><?php echo (int) $todaySummary['orders']; ?></strong>
                            </div>
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Average order</strong>
                                    <span class="muted">Per transaction</span>
                                </div>
                                <strong>₱<?php echo number_format((float) $todaySummary['average'], 0); ?></strong>
                            </div>
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Revenue</strong>
                                    <span class="muted">Total today</span>
                                </div>
                                <strong>₱<?php echo number_format((float) $todaySummary['revenue'], 0); ?></strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="info-grid">
                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Recent orders</span>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-box">No orders have been placed yet.</div>
                        <?php else: ?>
                            <div class="order-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <?php
                                    $statusValue = normalizeOrderStatus((string) ($order['status'] ?? 'Pending'));
                                    $statusClass = strtolower(str_replace(' ', '-', $statusValue));
                                    ?>
                                    <div class="order-row">
                                        <div class="order-main">
                                            <strong>#<?php echo (int) $order['id']; ?> · <?php echo htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></strong>
                                            <span class="muted"><?php echo htmlspecialchars($order['area_name'] ?: 'Delivery area'); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                            <strong>₱<?php echo number_format((float) $order['total_amount'], 0); ?></strong>
                                            <span class="status-pill <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusValue); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Rider performance</span>
                        </div>

                        <?php if (empty($riderPerformance)): ?>
                            <div class="empty-box">No rider data is available yet.</div>
                        <?php else: ?>
                            <div class="rider-list">
                                <?php foreach ($riderPerformance as $rider): ?>
                                    <div class="rider-row">
                                        <div class="rider-main">
                                            <div class="rider-name"><?php echo htmlspecialchars($rider['name'] ?? 'Rider'); ?></div>
                                            <span class="muted"><?php echo (int) $rider['deliveries']; ?> deliveries</span>
                                        </div>
                                        <strong>₱<?php echo number_format((float) $rider['revenue'], 0); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="info-grid" style="margin-top: 18px;">
                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Top products</span>
                        </div>

                        <?php if (empty($topProducts)): ?>
                            <div class="empty-box">No product performance data available yet.</div>
                        <?php else: ?>
                            <div class="product-list">
                                <?php foreach ($topProducts as $product): ?>
                                    <div class="product-item">
                                        <div class="activity-meta">
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <span class="muted"><?php echo (int) $product['sold']; ?> sold</span>
                                        </div>
                                        <span class="meta-tag">₱<?php echo number_format((float) $product['revenue'], 0); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Recent activity</span>
                        </div>

                        <?php if (empty($activityFeed)): ?>
                            <div class="empty-box">No recent admin activity yet.</div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($activityFeed as $event): ?>
                                    <?php $statusValue = normalizeOrderStatus((string) ($event['status'] ?? 'Pending')); ?>
                                    <div class="activity-item">
                                        <div class="activity-meta">
                                            <strong>#<?php echo (int) $event['id']; ?> · <?php echo htmlspecialchars($event['customer_name'] ?? 'Customer'); ?></strong>
                                            <span class="muted"><?php echo htmlspecialchars(date('d M Y · H:i', strtotime($event['created_at']))); ?></span>
                                        </div>
                                        <span class="meta-tag"><?php echo htmlspecialchars($statusValue); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="info-grid" style="margin-top: 18px;">
                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Delivery coverage</span>
                        </div>

                        <?php if (empty($deliveryAreas)): ?>
                            <div class="empty-box">No active delivery zones have been configured.</div>
                        <?php else: ?>
                            <div class="area-list">
                                <?php foreach ($deliveryAreas as $area): ?>
                                    <div class="area-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($area['area_name']); ?></strong>
                                            <span class="muted"><?php echo htmlspecialchars($area['rider_name'] ?? 'Unassigned'); ?></span>
                                        </div>
                                        <span class="status-pill preparing">Active</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <span>Operations snapshot</span>
                        </div>
                        <div class="order-list">
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Completed deliveries</strong>
                                    <span class="muted">Last 7 days</span>
                                </div>
                                <strong><?php echo (int) $stats['delivered']; ?></strong>
                            </div>
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Open orders</strong>
                                    <span class="muted">Awaiting attention</span>
                                </div>
                                <strong><?php echo (int) ($stats['pending'] + $stats['preparing'] + $stats['ready_for_pickup'] + $stats['out_for_delivery']); ?></strong>
                            </div>
                            <div class="order-row">
                                <div class="order-main">
                                    <strong>Revenue total</strong>
                                    <span class="muted">All recorded orders</span>
                                </div>
                                <strong>₱<?php echo number_format((float) $stats['revenue'], 0); ?></strong>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
