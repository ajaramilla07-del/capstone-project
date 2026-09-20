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
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !in_array($role, ['staff', 'rider'], true)) {
            $message = 'Enter a valid name, email, password of at least 6 characters, and role.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role,
                ]);
                $message = ucfirst($role) . ' account created successfully.';
            } catch (PDOException $exception) {
                $message = $exception->getCode() === '23000'
                    ? 'That email address is already registered.'
                    : 'Unable to create the account right now.';
                $messageType = 'error';
            }
        }
    }

    $users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $e) {
    $users = [];
    $message = 'Unable to load users right now.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Users</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #eef2ec; color: #111; }
        .dashboard-shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #1f2e2b, #1a2321);
            color: #fff;
            padding: 24px 18px;
            border-right: 1px solid rgba(255,255,255,0.08);
        }
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .logo-mark {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7bc1a6, #77c1fc);
            position: relative;
            box-shadow: 0 8px 22px rgba(123, 193, 166, 0.35);
        }
        .logo-mark::before {
            content: "";
            position: absolute;
            inset: 8px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
        }
        .nav { display: grid; gap: 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 12px; border-radius: 12px; color: #dfeae4; text-decoration: none; font-weight: 700;
            border: 1px solid transparent;
        }
        .nav-item.active, .nav-item:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.12);
        }
        .nav-icon { width: 12px; height: 12px; border-radius: 4px; background: rgba(255,255,255,0.7); display: inline-block; }
        .content { flex: 1; background: #f5f7f3; }
        .topbar {
            height: 78px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 26px; background: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(17,17,17,0.08);
        }
        .topbar-title { display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 700; }
        .burger { width: 18px; height: 14px; display: grid; gap: 3px; }
        .burger span { display: block; height: 2px; background: #1a2321; border-radius: 2px; }
        .main-inner { padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .panel { background: #fff; border: 1px solid rgba(17,17,17,0.08); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        .form-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; align-items: end; margin-bottom: 20px; }
        label { display: grid; gap: 6px; font-weight: 700; }
        input, select, button { width: 100%; padding: 11px 12px; border: 1px solid rgba(17,17,17,0.12); border-radius: 10px; font: inherit; }
        button { background: linear-gradient(135deg, #5ca66e, #3f8a56); color: #fff; border: none; font-weight: 700; cursor: pointer; }
        .message { padding: 10px 12px; border-radius: 10px; margin-bottom: 16px; font-weight: 600; }
        .message.success { background: #eef7f0; color: #184d2d; }
        .message.error { background: #fff1f0; color: #8f2424; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; border-bottom: 1px solid rgba(17,17,17,0.08); text-align: left; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
        .badge.admin { background: #dff7eb; color: #1d6d40; }
        .badge.customer { background: #e0f2fe; color: #0f4c81; }
        .badge.rider { background: #fef3c7; color: #7a5a00; }
        .badge.staff { background: #f3e8ff; color: #5b2fa4; }
        @media (max-width: 900px) { .dashboard-shell { flex-direction: column; } .sidebar { width: 100%; } .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="logo-wrap">
                <div class="logo-mark" aria-label="Logo"></div>
            </div>

            <nav class="nav" aria-label="Sidebar navigation">
                <a class="nav-item" href="dashboard.php"><span class="nav-icon"></span>Dashboard</a>
                <a class="nav-item" href="sales.php"><span class="nav-icon"></span>Sales</a>
                <a class="nav-item" href="growth.php"><span class="nav-icon"></span>Growth</a>
                <a class="nav-item" href="products.php"><span class="nav-icon"></span>Products</a>
                <a class="nav-item" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item active" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title">
                    <span class="burger" aria-label="Menu button"><span></span><span></span><span></span></span>
                    <span>Users</span>
                </div>
                <div>
                    <a href="../logout.php" style="text-decoration:none; color:#111; font-weight:700;">Logout</a>
                </div>
            </header>

            <main class="main-inner">
                <div class="wrap">
                    <div class="panel">
                        <h2>Create staff or rider account</h2>
                        <?php if ($message): ?><div class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                        <form method="post" action="users.php" class="form-grid">
                            <label>
                                Full name
                                <input type="text" name="name" required />
                            </label>
                            <label>
                                Email address
                                <input type="email" name="email" required />
                            </label>
                            <label>
                                Temporary password
                                <input type="password" name="password" minlength="6" required />
                            </label>
                            <label>
                                Role
                                <select name="role" required>
                                    <option value="staff">Staff</option>
                                    <option value="rider">Delivery rider</option>
                                </select>
                            </label>
                            <button type="submit">Create account</button>
                        </form>

                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4">No users found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="badge <?php echo htmlspecialchars((string) $user['role']); ?>"><?php echo htmlspecialchars(ucfirst((string) $user['role'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($user['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
