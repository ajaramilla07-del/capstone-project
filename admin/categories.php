<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$message = '';
$messageType = 'success';

try {
    $pdo = getDbConnection();
    $categoryColumns = $pdo->query('SHOW COLUMNS FROM categories')->fetchAll(PDO::FETCH_COLUMN);
    $usesNorthwindCategories = in_array('CategoryID', $categoryColumns, true) && in_array('CategoryName', $categoryColumns, true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'create_category';
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if (($action === 'create_category' || $action === 'update_category') && ($name === '' || ($action === 'update_category' && $categoryId <= 0))) {
            $message = 'Category name is required.';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'delete_category') {
                    $deleteQuery = $usesNorthwindCategories ? 'DELETE FROM categories WHERE CategoryID = :id' : 'DELETE FROM categories WHERE id = :id';
                    $pdo->prepare($deleteQuery)->execute([':id' => $categoryId]);
                    $message = 'Category deleted.';
                } elseif ($action === 'update_category') {
                    $updateQuery = $usesNorthwindCategories ? 'UPDATE categories SET CategoryName = :name WHERE CategoryID = :id' : 'UPDATE categories SET name = :name WHERE id = :id';
                    $pdo->prepare($updateQuery)->execute([':name' => $name, ':id' => $categoryId]);
                    $message = 'Category updated.';
                } else {
                    $insertQuery = $usesNorthwindCategories ? 'INSERT INTO categories (CategoryName) VALUES (:name)' : 'INSERT INTO categories (name) VALUES (:name)';
                    $stmt = $pdo->prepare($insertQuery);
                    $stmt->execute([':name' => $name]);
                    $categoryId = (int) $pdo->lastInsertId();
                    header('Location: products.php?category_id=' . $categoryId . '&category_added=1#product-form');
                    exit;
                }
            } catch (PDOException $exception) {
                $message = $exception->getCode() === '23000'
                    ? 'That category already exists or is still used by products.'
                    : 'Unable to add the category right now.';
                $messageType = 'error';
            }
        }
    }

    $categoryQuery = $usesNorthwindCategories
        ? 'SELECT CategoryID AS id, CategoryName AS name FROM categories ORDER BY CategoryName ASC'
        : 'SELECT id, name FROM categories ORDER BY name ASC';
    $categories = $pdo->query($categoryQuery)->fetchAll();
} catch (Throwable $exception) {
    $categories = [];
    $message = 'Unable to load categories right now.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Categories</title>
    <style>
        :root {
            --sidebar-bg: #2e5d2f;
            --sidebar-dark: #234a28;
            --bg-green: #dcead9;
            --text: #1e1e1e;
            --line: rgba(58, 81, 58, 0.18);
            --white: #ffffff;
            --soft: #eef7f0;
            --error: #fff1f0;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: var(--bg-green); color: var(--text); }
        body { padding: 0; }
        .dashboard-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: linear-gradient(180deg, var(--sidebar-bg), var(--sidebar-dark)); color: #edf3f8; padding: 18px 0 10px; }
        .logo-wrap { display: flex; justify-content: center; padding: 12px 0 18px; }
        .logo-mark { width: 82px; height: 82px; border-radius: 50%; background: rgba(255,255,255,0.08); position: relative; }
        .logo-mark::before { content: ""; width: 38px; height: 38px; background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.3)); border-radius: 12px; transform: rotate(45deg); position: absolute; left: 22px; top: 22px; }
        .logo-mark::after { content: ""; width: 16px; height: 16px; border: 3px solid #2b3e4d; border-top-color: transparent; border-left-color: transparent; position: absolute; left: 31px; top: 31px; border-radius: 4px; transform: rotate(45deg); }
        .nav { display: flex; flex-direction: column; gap: 10px; padding: 0 10px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 12px; border-radius: 10px; color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 700; }
        .nav-item.active, .nav-item:hover { background: rgba(255,255,255,0.08); }
        .nav-icon { width: 12px; height: 12px; border-radius: 4px; background: rgba(255,255,255,0.7); display: inline-block; }
        .content { flex: 1; background: rgba(255,255,255,0.08); }
        .topbar { height: 78px; display: flex; align-items: center; justify-content: space-between; padding: 0 26px; background: rgba(255,255,255,0.75); border-bottom: 1px solid rgba(17,17,17,0.08); }
        .topbar-title { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .burger { width: 18px; height: 14px; display: grid; gap: 3px; }
        .burger span { display: block; height: 2px; background: #1a2321; border-radius: 2px; }
        .main-inner { padding: 24px; }
        .wrap { max-width: 760px; margin: 0 auto; }
        .panel { background: var(--white); border: 1px solid var(--line); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); margin-bottom: 20px; }
        .form-grid { display:grid; gap:14px; }
        label { display:grid; gap:6px; font-weight:700; }
        input { width:100%; border:1px solid var(--line); border-radius:10px; padding:12px 14px; font:inherit; }
        .message { border-radius:10px; padding:10px 12px; margin-bottom:16px; font-weight:600; }
        .message.success { background:var(--soft); color:#184d2d; }
        .message.error { background:var(--error); color:#8f2424; }
        ul { list-style:none; padding:0; margin:0; display:grid; gap:8px; }
        li { padding:12px 14px; background:#f7faf7; border:1px solid var(--line); border-radius:10px; }
        .btn { display:inline-block; background:linear-gradient(135deg, #5ca66e, #3f8a56); color:#fff; border:none; border-radius:10px; padding:10px 14px; cursor:pointer; text-decoration:none; font-weight:700; }
        @media (max-width: 900px) { .dashboard-shell { flex-direction: column; } .sidebar { width: 100%; } }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="logo-wrap"><div class="logo-mark" aria-label="Logo"></div></div>
            <nav class="nav" aria-label="Sidebar navigation">
                <a class="nav-item" href="dashboard.php"><span class="nav-icon"></span>Dashboard</a>
                <a class="nav-item" href="sales.php"><span class="nav-icon"></span>Sales</a>
                <a class="nav-item" href="growth.php"><span class="nav-icon"></span>Growth</a>
                <a class="nav-item" href="products.php"><span class="nav-icon"></span>Products</a>
                <a class="nav-item active" href="categories.php"><span class="nav-icon"></span>Categories</a>
                <a class="nav-item" href="locations.php"><span class="nav-icon"></span>Locations</a>
                <a class="nav-item" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger" aria-label="Menu button"><span></span><span></span><span></span></span><span>Categories</span></div>
                <div><a href="../logout.php" style="text-decoration:none; color:#111; font-weight:700;">Logout</a></div>
            </header>

            <main class="main-inner">
                <div class="wrap">
                    <section class="panel">
                        <h2>Add category</h2>
                        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                        <form method="post" action="categories.php" class="form-grid">
                            <label>
                                Category name
                                <input type="text" name="name" placeholder="e.g. Desserts" required autofocus />
                            </label>
                            <button class="btn" type="submit">Add category</button>
                        </form>
                    </section>

                    <section class="panel">
                        <h2>Existing categories</h2>
                        <?php if ($categories): ?>
                            <table>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <form method="post" action="categories.php" style="display:flex; gap:8px;">
                                                    <input type="hidden" name="action" value="update_category" />
                                                    <input type="hidden" name="category_id" value="<?php echo (int) $category['id']; ?>" />
                                                    <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required />
                                                    <button class="btn" type="submit">Update</button>
                                                </form>
                                            </td>
                                            <td style="width:120px;">
                                                <form method="post" action="categories.php" onsubmit="return confirm('Delete this category?');">
                                                    <input type="hidden" name="action" value="delete_category" />
                                                    <input type="hidden" name="category_id" value="<?php echo (int) $category['id']; ?>" />
                                                    <button class="btn" type="submit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No categories found.</p>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
