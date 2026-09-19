<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireLogin();

$cart = $_SESSION['cart'] ?? [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    $area = trim($_POST['delivery_area'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');

    if (!$cart) {
        $error = 'Your cart is empty.';
    } elseif ($area === '' || $address === '') {
        $error = 'Please provide a valid delivery area and address.';
    } else {
        try {
            $pdo = getDbConnection();
            $coverage = $pdo->prepare('SELECT id, rider_id FROM delivery_coverages WHERE area_name = :area AND status = "active" LIMIT 1');
            $coverage->execute([':area' => $area]);
            $coverageRow = $coverage->fetch();

            if (!$coverageRow) {
                $error = 'Delivery is unavailable in this area.';
            } else {
                $productIds = array_keys($cart);
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id IN (' . $placeholders . ') AND status = "active"');
                $stmt->execute($productIds);
                $products = $stmt->fetchAll();

                $subtotal = 0;
                $itemRows = [];
                foreach ($products as $product) {
                    $qty = (int) ($cart[$product['id']] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $lineTotal = $qty * (float) $product['price'];
                    $subtotal += $lineTotal;
                    $itemRows[] = [
                        'id' => (int) $product['id'],
                        'qty' => $qty,
                        'price' => (float) $product['price'],
                        'subtotal' => $lineTotal,
                    ];
                }

                $total = $subtotal + 250 + 180;
                $orderStmt = $pdo->prepare('INSERT INTO orders (customer_id, delivery_coverage_id, delivery_address, total_amount, status, rider_id) VALUES (:customer_id, :delivery_coverage_id, :delivery_address, :total_amount, :status, :rider_id)');
                $orderStmt->execute([
                    ':customer_id' => (int) currentUser()['id'],
                    ':delivery_coverage_id' => (int) $coverageRow['id'],
                    ':delivery_address' => $address,
                    ':total_amount' => $total,
                    ':status' => 'Pending',
                    ':rider_id' => $coverageRow['rider_id'],
                ]);

                $orderId = (int) $pdo->lastInsertId();

                $detailStmt = $pdo->prepare('INSERT INTO order_details (order_id, product_id, quantity, price, subtotal) VALUES (:order_id, :product_id, :quantity, :price, :subtotal)');
                foreach ($itemRows as $row) {
                    $detailStmt->execute([
                        ':order_id' => $orderId,
                        ':product_id' => (int) $row['id'],
                        ':quantity' => (int) $row['qty'],
                        ':price' => $row['price'],
                        ':subtotal' => $row['subtotal'],
                    ]);
                }

                $_SESSION['cart'] = [];
                header('Location: orders.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Unable to place your order right now.';
        }
    }
}

$areas = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT area_name FROM delivery_coverages WHERE status = "active" ORDER BY area_name ASC');
    $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $areas = [];
}

$subtotal = 0;
if ($cart) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id IN (' . $placeholders . ') AND status = "active"');
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    foreach ($products as $product) {
        $qty = (int) ($cart[$product['id']] ?? 0);
        $subtotal += $qty * (float) $product['price'];
    }
}

$total = $subtotal + 250 + 180;
$pageTitle = 'Checkout';
$activePage = 'checkout.php';

ob_start();
?>
<div class="section-panel">
    <h1 class="page-header">Checkout</h1>

    <?php if ($error): ?>
        <div class="error-box" style="margin-bottom: 18px; color: #b42318; background: #fef3f2; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 12px; font-weight: 600;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!$cart): ?>
        <p>Your cart is empty.</p>
        <a href="menu.php" class="primary-btn" style="display:inline-block; text-decoration:none; text-align:center;">Browse menu</a>
    <?php else: ?>
        <div class="checkout-grid">
            <div class="form-card">
                <h3>Delivery details</h3>
                <form method="post" action="checkout.php" class="form-grid">
                    <input type="hidden" name="action" value="place_order" />

                    <label>
                        Full name
                        <input type="text" value="<?php echo htmlspecialchars(currentUser()['name'] ?? ''); ?>" readonly />
                    </label>

                    <label>
                        Delivery area
                        <select name="delivery_area" required>
                            <option value="">Select area</option>
                            <?php foreach ($areas as $areaName): ?>
                                <option value="<?php echo htmlspecialchars($areaName); ?>"><?php echo htmlspecialchars($areaName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Street address
                        <textarea name="delivery_address" rows="4" placeholder="Enter delivery address" required></textarea>
                    </label>

                    <button class="primary-btn" type="submit">Place order</button>
                </form>
            </div>

            <aside class="summary-card">
                <h3>Order summary</h3>
                <div class="summary-list">
                    <?php foreach ($cart as $productId => $qty): ?>
                        <?php
                        $productData = $pdo->prepare('SELECT name, price FROM products WHERE id = :id AND status = "active" LIMIT 1');
                        $productData->execute([':id' => (int) $productId]);
                        $productInfo = $productData->fetch();
                        if ($productInfo):
                            $itemTotal = (float) $productInfo['price'] * (int) $qty;
                        ?>
                            <div class="summary-row">
                                <span><?php echo htmlspecialchars($productInfo['name']); ?> x <?php echo (int) $qty; ?></span>
                                <strong>₱<?php echo number_format($itemTotal, 0); ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="summary-totals">
                    <div class="summary-row"><span>Subtotal</span><strong>₱<?php echo number_format((float)$subtotal, 0); ?></strong></div>
                    <div class="summary-row"><span>Delivery</span><strong>₱250</strong></div>
                    <div class="summary-row"><span>Total</span><strong class="amount">₱<?php echo number_format((float)$total, 0); ?></strong></div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/customer-layout.php';
