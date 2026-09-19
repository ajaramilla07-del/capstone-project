<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$message = '';
$messageType = 'success';

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '') {
            $message = 'Category name is required.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO categories (CategoryName) VALUES (:name)');
                $stmt->execute([':name' => $name]);
                $categoryId = (int) $pdo->lastInsertId();
                header('Location: products.php?category_id=' . $categoryId . '&category_added=1#product-form');
                exit;
            } catch (PDOException $exception) {
                $message = $exception->getCode() === '23000'
                    ? 'That category already exists.'
                    : 'Unable to add the category right now.';
                $messageType = 'error';
            }
        }
    }

    $categories = $pdo->query('SELECT CategoryID AS id, CategoryName AS name FROM categories ORDER BY CategoryName ASC')->fetchAll();
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
        :root { --bg:#f5f7f3; --panel:#ffffff; --line:rgba(17,17,17,0.08); --green:#5ca66e; --green-dark:#3f8a56; --text:#111; --muted:#5b5b5b; --soft:#eef7f0; --error:#fff1f0; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; background:var(--bg); color:var(--text); padding:24px; }
        .wrap { max-width:760px; margin:0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .link, .btn { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; }
        .link { background:#fff; border:1px solid var(--line); color:var(--text); }
        .btn { background:linear-gradient(135deg, var(--green), var(--green-dark)); color:#fff; border:0; cursor:pointer; }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:20px; box-shadow:0 12px 32px rgba(17,17,17,0.04); margin-bottom:20px; }
        .form-grid { display:grid; gap:14px; }
        label { display:grid; gap:6px; font-weight:700; }
        input { width:100%; border:1px solid var(--line); border-radius:10px; padding:12px 14px; font:inherit; }
        .message { border-radius:10px; padding:10px 12px; margin-bottom:16px; font-weight:600; }
        .message.success { background:var(--soft); color:#184d2d; }
        .message.error { background:var(--error); color:#8f2424; }
        ul { list-style:none; padding:0; margin:0; display:grid; gap:8px; }
        li { padding:12px 14px; background:#f7faf7; border:1px solid var(--line); border-radius:10px; }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="topbar">
            <h1>Categories</h1>
            <nav class="nav" aria-label="Admin navigation">
                <a class="link" href="products.php">Products</a>
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="../logout.php">Logout</a>
            </nav>
        </div>

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
                <ul>
                    <?php foreach ($categories as $category): ?>
                        <li><?php echo htmlspecialchars($category['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
