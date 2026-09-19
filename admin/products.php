<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$message = '';

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_product') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $image = trim((string) ($_POST['image'] ?? ''));
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($categoryId <= 0 || $name === '' || $price < 0) {
            $message = 'Category, name, and valid price are required.';
        } else {
            if ($productId > 0) {
                $stmt = $pdo->prepare('UPDATE products SET category_id = :category_id, name = :name, description = :description, price = :price, image = :image, status = :status WHERE id = :id');
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image' => $image,
                    ':status' => $status,
                    ':id' => $productId,
                ]);
                $message = 'Product updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, image, status) VALUES (:category_id, :name, :description, :price, :image, :status)');
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image' => $image,
                    ':status' => $status,
                ]);
                $message = 'Product added successfully.';
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $pdo->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $productId]);
            $message = 'Product deleted successfully.';
        }
    }

    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    $products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();
} catch (Throwable $e) {
    $categories = [];
    $products = [];
    $message = 'Unable to load products right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Products</title>
    <style>
        :root { --bg:#f5f7f3; --panel:#ffffff; --line:rgba(17,17,17,0.08); --green:#5ca66e; --green-dark:#3f8a56; --text:#111; --muted:#5b5b5b; --soft:#eef7f0; --warning:#fff4d6; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; background:var(--bg); color:var(--text); padding:24px; }
        .wrap { max-width:1200px; margin:0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .btn, .link { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; }
        .btn { background:linear-gradient(135deg, var(--green), var(--green-dark)); color:#fff; border:none; cursor:pointer; }
        .link { background:#fff; border:1px solid var(--line); color:var(--text); }
        .grid { display:grid; grid-template-columns:1.1fr 1.7fr; gap:20px; }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:20px; box-shadow:0 12px 32px rgba(17,17,17,0.04); }
        .form-grid { display:grid; gap:14px; }
        label { display:grid; gap:6px; font-weight:700; }
        input, select, textarea { width:100%; border:1px solid var(--line); border-radius:10px; padding:12px 14px; font:inherit; }
        textarea { min-height:110px; resize:vertical; }
        .message { background:var(--soft); color:#184d2d; border:1px solid rgba(92,166,110,0.2); border-radius:10px; padding:10px 12px; margin-bottom:16px; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:12px 8px; border-bottom:1px solid var(--line); vertical-align:top; }
        th { font-size:0.8rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }
        .badge { display:inline-block; padding:5px 9px; border-radius:999px; font-size:0.75rem; font-weight:700; }
        .badge.active { background:var(--soft); color:#184d2d; }
        .badge.inactive { background:var(--warning); color:#7a5a00; }
        .mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .small-btn { background:transparent; border:1px solid var(--line); padding:7px 10px; border-radius:8px; cursor:pointer; font-weight:700; }
        .danger { color:#a91b1b; }
        @media (max-width:900px){ .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <div>
                <h1>Admin Products</h1>
            </div>
            <div class="nav">
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="orders.php">Orders</a>
                <a class="link" href="../logout.php">Logout</a>
            </div>
        </div>

        <?php if ($message): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="grid">
            <section class="panel">
                <h2>Add / Update Product</h2>
                <form method="post" action="products.php" class="form-grid">
                    <input type="hidden" name="action" value="save_product" />
                    <input type="hidden" name="product_id" value="0" />

                    <label>
                        Category
                        <select name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Product name
                        <input type="text" name="name" placeholder="e.g. Pizza" required />
                    </label>

                    <label>
                        Description
                        <textarea name="description" placeholder="Short description"></textarea>
                    </label>

                    <label>
                        Price (Kes)
                        <input type="number" name="price" min="0" step="0.01" placeholder="0.00" required />
                    </label>

                    <label>
                        Image URL
                        <input type="url" name="image" placeholder="https://example.com/image.jpg" />
                    </label>

                    <label>
                        Status
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>

                    <button class="btn" type="submit">Save Product</button>
                </form>
            </section>

            <section class="panel">
                <h2>Current Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <span style="color:var(--muted); font-size:0.85rem;"><?php echo htmlspecialchars($product['description'] ?: 'No description'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></td>
                                    <td>Kes.<?php echo number_format((float) $product['price'], 0); ?></td>
                                    <td><span class="badge <?php echo htmlspecialchars((string) $product['status']); ?>"><?php echo htmlspecialchars($product['status']); ?></span></td>
                                    <td>
                                        <div class="mini-actions">
                                            <button class="small-btn" type="button" onclick="fillProductForm(<?php echo (int) $product['id']; ?>, <?php echo (int) $product['category_id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', '<?php echo addslashes(htmlspecialchars($product['description'] ?? '')); ?>', <?php echo (float) $product['price']; ?>, '<?php echo addslashes(htmlspecialchars($product['image'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($product['status'])); ?>')">Edit</button>
                                            <form method="post" action="products.php" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                                <input type="hidden" name="action" value="delete_product" />
                                                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>" />
                                                <button class="small-btn danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <script>
        function fillProductForm(id, categoryId, name, description, price, image, status) {
            const form = document.querySelector('form');
            if (!form) return;
            form.querySelector('input[name="product_id"]').value = id;
            form.querySelector('select[name="category_id"]').value = categoryId;
            form.querySelector('input[name="name"]').value = name;
            form.querySelector('textarea[name="description"]').value = description || '';
            form.querySelector('input[name="price"]').value = Number(price).toFixed(2);
            form.querySelector('input[name="image"]').value = image || '';
            form.querySelector('select[name="status"]').value = status || 'active';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
