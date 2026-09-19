<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Orders';
$activePage = 'orders.php';

$orders = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT o.id, o.total_amount, o.status, o.created_at, dc.area_name FROM orders o LEFT JOIN delivery_coverages dc ON dc.id = o.delivery_coverage_id WHERE o.customer_id = :customer_id ORDER BY o.created_at DESC');
    $stmt->execute([':customer_id' => (int) currentUser()['id']]);
    $orders = $stmt->fetchAll();
} catch (Throwable $e) {
    $orders = [];
}

ob_start();
?>
<div class="section-panel">
    <h1 class="page-header">My orders</h1>

    <?php if ($orders): ?>
        <div class="order-history">
            <?php foreach ($orders as $order): ?>
                <?php
                $statusValue = normalizeOrderStatus((string) ($order['status'] ?? 'Pending'));
                $statusClass = strtolower(str_replace(' ', '-', $statusValue));
                ?>
                <div class="order-history-item">
                    <div>
                        <strong>#<?php echo (int) $order['id']; ?></strong><br>
                        <span style="color:#7d7274;">
                            <?php echo htmlspecialchars($order['area_name'] ?: 'Delivery area'); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?>
                        </span>
                    </div>
                    <div><span class="status-badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusValue); ?></span></div>
                    <div><strong>Kes.<?php echo number_format((float) $order['total_amount'], 0); ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have no orders yet.</p>
        <a href="menu.php" class="primary-btn" style="display:inline-block; text-decoration:none; text-align:center;">Order food</a>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/customer-layout.php';
