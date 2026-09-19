<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDbConnection();
$products = [];

try {
    $stmt = $pdo->query('SELECT * FROM products WHERE status = "active" ORDER BY id ASC');
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

$pageTitle = 'Menu';
$activePage = 'index.php';

ob_start();
?>
<div class="section-panel">
    <h1 class="page-header">Our menu</h1>

    <div class="menu-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <?php foreach ($products as $product): ?>
            <div class="menu-card" style="background: #ffffff; border: 1px solid rgba(20,20,20,0.08); border-radius: 22px; padding: 14px; box-shadow: 0 12px 24px rgba(30, 45, 35, 0.06);">
                <div class="menu-image" style="height: 180px; border-radius: 18px; background-image: url('<?php echo htmlspecialchars($product['image'] ?: 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=800&q=80'); ?>'); background-size: cover; background-position: center; margin-bottom: 12px;"></div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: #4f5f4d; font-weight: 700; margin-bottom: 4px;">Popular</div>
                <h3 style="margin: 0 0 8px; font-size: 1.35rem; color: #111111;"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p style="margin: 0 0 12px; color: #4a4a4a; min-height: 44px;">
                    <?php echo htmlspecialchars($product['description'] ?: 'Fresh and delicious meal option.'); ?>
                </p>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top: 8px;">
                    <strong style="font-size: 1.2rem; color: #111111;">Kes.<?php echo number_format((float) $product['price'], 0); ?></strong>
                    <form method="post" action="cart.php">
                        <input type="hidden" name="action" value="update_cart" />
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>" />
                        <input type="hidden" name="quantity" value="1" />
                        <button type="submit" class="primary-btn" style="padding: 10px 16px; background: linear-gradient(135deg, #5ca66e, #3f8a56); color: #fff; border: none; border-radius: 12px; text-decoration: none;">Add to cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/customer-layout.php';
