<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_cart') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
    $cart = $_SESSION['cart'] ?? [];

    if ($productId > 0 && $quantity > 0) {
        $cart[$productId] = $quantity;
    } elseif ($productId > 0) {
        unset($cart[$productId]);
    }

    $_SESSION['cart'] = $cart;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_item') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        unset($_SESSION['cart'][$productId]);
    }
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;

if ($cart) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $pdo = getDbConnection();
    $sql = 'SELECT id, name, price, image FROM products WHERE id IN (' . $placeholders . ') AND status = "active"';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = (int) ($cart[$product['id']] ?? 0);
        if ($qty <= 0) {
            continue;
        }

        $lineTotal = $qty * (float) $product['price'];
        $items[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'image' => $product['image'],
            'quantity' => $qty,
            'line_total' => $lineTotal,
        ];
        $total += $lineTotal;
    }
}

$pageTitle = 'Cart';
$activePage = 'cart.php';

ob_start();
?>
<div class="section-panel">
    <h1 class="page-header">My cart</h1>

    <?php if ($items): ?>
        <div class="cart-grid">
            <div class="cart-list">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item">
                        <div class="thumb" style="background-image:url('<?php echo htmlspecialchars($item['image'] ?: 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=800&q=80'); ?>');"></div>
                        <div>
                            <div style="font-weight:800; margin-bottom:6px;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="color:#7d7274;">Kes.<?php echo number_format((float)$item['price'], 0); ?></div>
                        </div>
                        <div class="qty-box">
                            <form method="post" action="cart.php" style="display:flex; align-items:center; gap:10px;">
                                <input type="hidden" name="action" value="update_cart" />
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>" />
                                <input type="hidden" name="quantity" value="<?php echo max(0, (int) $item['quantity'] - 1); ?>" />
                                <button type="submit" aria-label="Decrease quantity">−</button>
                            </form>
                            <span class="qty-value"><?php echo (int) $item['quantity']; ?></span>
                            <form method="post" action="cart.php" style="display:flex; align-items:center; gap:10px;">
                                <input type="hidden" name="action" value="update_cart" />
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>" />
                                <input type="hidden" name="quantity" value="<?php echo (int) $item['quantity'] + 1; ?>" />
                                <button type="submit" aria-label="Increase quantity">+</button>
                            </form>
                        </div>
                        <div class="item-total">Kes.<?php echo number_format((float)$item['line_total'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="summary-card">
                <h3>Order summary</h3>
                <div class="summary-list">
                    <div class="summary-row"><span>Subtotal</span><strong>Kes.<?php echo number_format((float)$total, 0); ?></strong></div>
                    <div class="summary-row"><span>Delivery</span><strong>Kes.250</strong></div>
                    <div class="summary-row"><span>VAT</span><strong>Kes.180</strong></div>
                </div>
                <div class="summary-totals">
                    <div class="summary-row"><span>Total</span><strong class="amount">Kes.<?php echo number_format((float)($total + 250 + 180), 0); ?></strong></div>
                    <a href="checkout.php" class="primary-btn" style="display:inline-block; text-align:center; text-decoration:none;">Proceed to checkout</a>
                </div>
            </aside>
        </div>
    <?php else: ?>
        <p>Your cart is empty.</p>
        <a href="menu.php" class="primary-btn" style="display:inline-block; text-decoration:none; text-align:center;">Browse menu</a>
    <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/customer-layout.php';
