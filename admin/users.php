<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

try {
    $pdo = getDbConnection();
    $users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $e) {
    $users = [];
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
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f3; color: #111; padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .link { display: inline-block; padding: 10px 14px; border-radius: 10px; text-decoration: none; background: #fff; border: 1px solid rgba(17,17,17,0.08); color: #111; font-weight: 700; }
        .panel { background: #fff; border: 1px solid rgba(17,17,17,0.08); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; border-bottom: 1px solid rgba(17,17,17,0.08); text-align: left; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
        .badge.admin { background: #dff7eb; color: #1d6d40; }
        .badge.customer { background: #e0f2fe; color: #0f4c81; }
        .badge.rider { background: #fef3c7; color: #7a5a00; }
        .badge.staff { background: #f3e8ff; color: #5b2fa4; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <h1>Users</h1>
            <div class="nav">
                <a class="link" href="dashboard.php">Dashboard</a>
                <a class="link" href="sales.php">Sales</a>
                <a class="link" href="growth.php">Growth</a>
                <a class="link" href="../logout.php">Logout</a>
            </div>
        </div>

        <div class="panel">
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
</body>
</html>
