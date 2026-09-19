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
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f3; color: #111; padding: 24px; }
        .wrap { max-width: 1200px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .link { display: inline-block; padding: 10px 14px; border-radius: 10px; text-decoration: none; background: #fff; border: 1px solid rgba(17,17,17,0.08); color: #111; font-weight: 700; }
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
        @media (max-width: 800px) { .form-grid { grid-template-columns: 1fr; } }
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
</body>
</html>
