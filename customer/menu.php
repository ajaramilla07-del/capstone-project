<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDbConnection();
$products = [];
$categories = [];
$selectedCategoryId = max(0, (int) ($_GET['category_id'] ?? 0));

try {
    $categoryColumns = $pdo->query('SHOW COLUMNS FROM categories')->fetchAll(PDO::FETCH_COLUMN);
    $usesNorthwindCategories = in_array('CategoryID', $categoryColumns, true) && in_array('CategoryName', $categoryColumns, true);
    $categoryQuery = $usesNorthwindCategories
        ? 'SELECT CategoryID AS id, CategoryName AS name FROM categories ORDER BY CategoryName ASC'
        : 'SELECT id, name FROM categories ORDER BY name ASC';
    $categories = $pdo->query($categoryQuery)->fetchAll();

    if ($selectedCategoryId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE status = "active" AND category_id = :category_id ORDER BY id ASC');
        $stmt->execute([':category_id' => $selectedCategoryId]);
    } else {
        $stmt = $pdo->query('SELECT * FROM products WHERE status = "active" ORDER BY id ASC');
    }

    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

$pageTitle = 'Menu';
$activePage = 'index.php';
$pageBodyClass = 'full-width';

$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));

ob_start();
?>
<div class="menu-layout">
    <section class="section-panel">
        <h1 class="page-header">Our menu</h1>

    <form method="get" action="menu.php" style="display:flex; align-items:center; gap:12px; margin:0 0 20px; flex-wrap:wrap;">
        <label for="category-filter" style="font-weight:700;">Filter by category</label>
        <select id="category-filter" name="category_id" onchange="this.form.submit()" style="min-width:220px; padding:12px 14px; border:1px solid rgba(20,20,20,0.12); border-radius:12px; background:#fff; font:inherit;">
            <option value="0">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int) $category['id']; ?>" <?php echo ((int) $category['id'] === $selectedCategoryId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="menu-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <?php if (empty($products)): ?>
            <p>No products found in this category.</p>
        <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="menu-card" style="background: #ffffff; border: 1px solid rgba(20,20,20,0.08); border-radius: 22px; padding: 14px; box-shadow: 0 12px 24px rgba(30, 45, 35, 0.06);">
                <div class="menu-image" style="height: 180px; border-radius: 18px; background: #eef2ed; display: grid; place-items: center; color: #71806f; margin-bottom: 12px;">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:18px;" />
                    <?php else: ?>
                        <span>No image</span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: #4f5f4d; font-weight: 700; margin-bottom: 4px;">Popular</div>
                <h3 style="margin: 0 0 8px; font-size: 1.35rem; color: #111111;"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p style="margin: 0 0 12px; color: #4a4a4a; min-height: 44px;">
                    <?php echo htmlspecialchars($product['description'] ?: 'Fresh and delicious meal option.'); ?>
                </p>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top: 8px;">
                    <strong style="font-size: 1.2rem; color: #111111;">₱<?php echo number_format((float) $product['price'], 0); ?></strong>
                    <form method="post" action="cart.php">
                        <input type="hidden" name="action" value="update_cart" />
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>" />
                        <input type="hidden" name="quantity" value="1" />
                        <button type="submit" class="primary-btn" style="padding: 10px 16px; background: linear-gradient(135deg, #5ca66e, #3f8a56); color: #fff; border: none; border-radius: 12px; text-decoration: none;">Add to cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </section>

    <aside class="summary-card menu-checkout">
        <h3>Your order</h3>
        <div class="summary-list">
            <div class="summary-row"><span>Items in cart</span><strong><?php echo (int) $cartCount; ?></strong></div>
        </div>
        <a href="cart.php" class="primary-btn" style="display:inline-block; text-align:center; text-decoration:none; margin-top:18px;">View cart</a>
        <a href="checkout.php" class="secondary-btn" style="display:inline-block; width:100%; margin-top:10px; text-align:center; text-decoration:none;">Checkout</a>
    </aside>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/customer-layout.php';
