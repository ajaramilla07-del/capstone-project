<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['rider']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_delivery') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = normalizeOrderStatus(trim($_POST['status'] ?? ''));

    if ($orderId > 0 && in_array($status, ['Ready for pickup', 'Out for delivery', 'Delivered'], true)) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id AND rider_id = :rider_id');
        $stmt->execute([
            ':status' => $status,
            ':id' => $orderId,
            ':rider_id' => (int) currentUser()['id'],
        ]);
        $message = 'Delivery status updated.';
    }
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT o.id, o.status, o.total_amount, o.delivery_address, c.name AS customer_name, dc.area_name FROM orders o JOIN users c ON c.id = o.customer_id LEFT JOIN delivery_coverages dc ON dc.id = o.delivery_coverage_id WHERE o.rider_id = :rider_id ORDER BY o.created_at DESC');
$stmt->execute([':rider_id' => (int) currentUser()['id']]);
$deliveries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rider Deliveries</title>
    <style>
        :root {
            --bg: #ffffff;
            --panel: #f7f8f3;
            --green: #5ca66e;
            --green-dark: #3f8a56;
            --text: #111111;
            --muted: #5c5c5c;
            --line: rgba(17,17,17,0.08);
            --white: #ffffff;
            --soft: #eef7f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 24px;
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 28px;
        }
        h1 { margin: 0; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .welcome {
            color: var(--muted);
            margin-top: 8px;
        }
        .message {
            margin: 0 0 20px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--soft);
            color: #1d4c2d;
            border: 1px solid rgba(92, 166, 110, 0.2);
            font-weight: 600;
        }
        .list {
            display: grid;
            gap: 18px;
        }
        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 24px rgba(17,17,17,0.02);
        }
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .meta strong { font-size: 1.08rem; }
        .meta span, .meta p { color: var(--muted); margin: 4px 0 0; }
        form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        select {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            min-width: 200px;
            font-size: 0.96rem;
            background: #fff;
            color: var(--text);
        }
        button {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: var(--white);
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
        }
        .logout {
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
        }
        .empty {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <div>
                <h1>Rider Deliveries</h1>
                <p class="welcome">Welcome, <?php echo htmlspecialchars(currentUser()['name'] ?? 'Rider'); ?></p>
            </div>
            <a class="logout" href="../logout.php">Logout</a>
        </div>

        <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <?php if (empty($deliveries)): ?>
            <div class="empty">No deliveries assigned yet.</div>
        <?php else: ?>
            <div class="list">
                <?php foreach ($deliveries as $delivery): ?>
                    <div class="card">
                        <div class="meta">
                            <div>
                                <strong>Delivery #<?php echo (int) $delivery['id']; ?></strong>
                                <p><?php echo htmlspecialchars($delivery['customer_name'] ?? 'Customer'); ?></p>
                                <p><?php echo htmlspecialchars($delivery['area_name'] ?: 'Delivery area'); ?> · <?php echo htmlspecialchars($delivery['delivery_address'] ?: 'No address'); ?></p>
                            </div>
                            <div>
                                <strong>₱<?php echo number_format((float) $delivery['total_amount'], 0); ?></strong>
                                <p><?php echo htmlspecialchars($delivery['status'] ?? 'Pending'); ?></p>
                            </div>
                        </div>

                        <form method="post" action="deliveries.php">
                            <input type="hidden" name="action" value="update_delivery" />
                            <input type="hidden" name="order_id" value="<?php echo (int) $delivery['id']; ?>" />
                            <select name="status">
                                <?php foreach (['Ready for pickup', 'Out for delivery', 'Delivered'] as $status): ?>
                                    <?php $optionValue = normalizeOrderStatus($status); ?>
                                    <option value="<?php echo htmlspecialchars($optionValue); ?>" <?php echo ($optionValue === normalizeOrderStatus((string) ($delivery['status'] ?? 'Ready for pickup'))) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Update status</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
