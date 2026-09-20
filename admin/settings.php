<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$message = '';
$messageType = 'success';
$riders = [];
$coverages = [];

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $areaName = trim((string) ($_POST['area_name'] ?? ''));
        $riderId = (int) ($_POST['rider_id'] ?? 0);
        $coverageId = (int) ($_POST['coverage_id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if (($action === 'create_coverage' && $areaName === '') || ($action === 'update_coverage' && $coverageId <= 0)) {
            $message = 'Enter a coverage location and choose a valid rider assignment.';
            $messageType = 'error';
        } elseif ($riderId > 0) {
            $riderCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "rider" LIMIT 1');
            $riderCheck->execute([':id' => $riderId]);
            if (!$riderCheck->fetchColumn()) {
                $message = 'Please choose a valid delivery rider.';
                $messageType = 'error';
            }
        }

        if ($message === '') {
            try {
                $pdo->beginTransaction();
                if ($action === 'create_coverage') {
                    $stmt = $pdo->prepare('INSERT INTO delivery_coverages (area_name, rider_id, status) VALUES (:area_name, :rider_id, :status)');
                    $stmt->execute([
                        ':area_name' => $areaName,
                        ':rider_id' => $riderId > 0 ? $riderId : null,
                        ':status' => $status,
                    ]);
                    $coverageId = (int) $pdo->lastInsertId();
                    $message = 'Coverage location added and rider assigned.';
                } elseif ($action === 'update_coverage') {
                    $stmt = $pdo->prepare('UPDATE delivery_coverages SET rider_id = :rider_id, status = :status WHERE id = :id');
                    $stmt->execute([
                        ':rider_id' => $riderId > 0 ? $riderId : null,
                        ':status' => $status,
                        ':id' => $coverageId,
                    ]);
                    $message = 'Coverage rider assignment updated.';
                }

                if (in_array($action, ['create_coverage', 'update_coverage'], true) && $riderId > 0) {
                    $orderUpdate = $pdo->prepare('UPDATE orders SET rider_id = :rider_id WHERE delivery_coverage_id = :coverage_id AND status <> "Delivered"');
                    $orderUpdate->execute([
                        ':rider_id' => $riderId,
                        ':coverage_id' => $coverageId,
                    ]);
                } elseif ($action === 'update_coverage') {
                    $orderUpdate = $pdo->prepare('UPDATE orders SET rider_id = NULL WHERE delivery_coverage_id = :coverage_id AND status <> "Delivered"');
                    $orderUpdate->execute([':coverage_id' => $coverageId]);
                }
                $pdo->commit();
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = $exception->getCode() === '23000'
                    ? 'That coverage location already exists.'
                    : 'Unable to save the coverage assignment right now.';
                $messageType = 'error';
            }
        }
    }

    $riders = $pdo->query('SELECT id, name, email FROM users WHERE role = "rider" ORDER BY name ASC')->fetchAll();
    $coverages = $pdo->query('SELECT dc.id, dc.area_name, dc.rider_id, dc.status, u.name AS rider_name FROM delivery_coverages dc LEFT JOIN users u ON u.id = dc.rider_id ORDER BY dc.area_name ASC')->fetchAll();
} catch (Throwable $exception) {
    $message = 'Unable to load delivery coverage settings right now.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Settings</title>
    <style>
        :root {
            --sidebar-bg: #2e5d2f;
            --sidebar-dark: #234a28;
            --bg-green: #dcead9;
            --text: #1e1e1e;
            --line: rgba(58, 81, 58, 0.18);
            --white: #ffffff;
            --soft: #edf8f0;
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
        .wrap { max-width: 1200px; margin: 0 auto; }
        .panel { background: var(--white); border: 1px solid var(--line); border-radius: 18px; padding: 20px; box-shadow: 0 12px 32px rgba(17,17,17,0.04); }
        .coverage-form { display: grid; grid-template-columns: 1.4fr 1fr 130px 150px; gap: 12px; align-items: end; margin-bottom: 22px; }
        .coverage-form label { display: grid; gap: 6px; font-weight: 700; }
        input, select, button { width: 100%; padding: 11px 12px; border: 1px solid var(--line); border-radius: 10px; font: inherit; }
        button { background: linear-gradient(135deg, #5ca66e, #3f8a56); color: #fff; border: none; font-weight: 700; cursor: pointer; }
        .message { padding: 10px 12px; border-radius: 10px; margin-bottom: 16px; font-weight: 600; }
        .message.success { background: var(--soft); color: #184d2d; }
        .message.error { background: #fff1f0; color: #8f2424; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; color: #4b4e4a; }
        .status { display: inline-block; background: var(--soft); color: #1d6d40; border-radius: 999px; padding: 6px 10px; font-size: 0.72rem; font-weight: 700; }
        .status.inactive { background: #fff4d6; color: #7a5a00; }
        .muted { color: #4b4e4a; font-size: 0.88rem; }
        @media (max-width: 900px) { .dashboard-shell { flex-direction: column; } .sidebar { width: 100%; } .coverage-form { grid-template-columns: 1fr; } table { display: block; overflow-x: auto; } }
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
                <a class="nav-item" href="orders.php"><span class="nav-icon"></span>Orders</a>
                <a class="nav-item" href="users.php"><span class="nav-icon"></span>Users</a>
                <a class="nav-item active" href="settings.php"><span class="nav-icon"></span>Settings</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar-title"><span class="burger" aria-label="Menu button"><span></span><span></span><span></span></span><span>Settings</span></div>
                <div><a href="../logout.php" style="text-decoration:none; color:#111; font-weight:700;">Logout</a></div>
            </header>

            <main class="main-inner">
                <div class="wrap">
                    <?php if ($message): ?><div class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                    <div class="panel">
                        <h2>Delivery coverage and rider assignment</h2>
                        <p class="muted">New orders in an active location are automatically assigned to its selected rider.</p>
                        <form method="post" action="settings.php" class="coverage-form">
                            <input type="hidden" name="action" value="create_coverage" />
                            <label>
                                Coverage location
                                <input type="text" name="area_name" placeholder="e.g. Downtown" required />
                            </label>
                            <label>
                                Delivery rider
                                <select name="rider_id">
                                    <option value="0">Unassigned</option>
                                    <?php foreach ($riders as $rider): ?>
                                        <option value="<?php echo (int) $rider['id']; ?>"><?php echo htmlspecialchars($rider['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Status
                                <select name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </label>
                            <button type="submit">Add location</button>
                        </form>

                        <table>
                            <thead>
                                <tr>
                                    <th>Coverage location</th>
                                    <th>Assigned rider</th>
                                    <th>Status</th>
                                    <th>Save assignment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$coverages): ?>
                                    <tr><td colspan="4">No coverage locations configured yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($coverages as $coverage): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($coverage['area_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($coverage['rider_name'] ?? 'Unassigned'); ?></td>
                                            <td><span class="status <?php echo $coverage['status'] === 'inactive' ? 'inactive' : ''; ?>"><?php echo htmlspecialchars(ucfirst($coverage['status'])); ?></span></td>
                                            <td>
                                                <form method="post" action="settings.php" class="coverage-form" style="grid-template-columns: 1fr 130px 110px; margin: 0;">
                                                    <input type="hidden" name="action" value="update_coverage" />
                                                    <input type="hidden" name="coverage_id" value="<?php echo (int) $coverage['id']; ?>" />
                                                    <select name="rider_id" aria-label="Rider for <?php echo htmlspecialchars($coverage['area_name']); ?>">
                                                        <option value="0">Unassigned</option>
                                                        <?php foreach ($riders as $rider): ?>
                                                            <option value="<?php echo (int) $rider['id']; ?>" <?php echo ((int) $coverage['rider_id'] === (int) $rider['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rider['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select name="status" aria-label="Status for <?php echo htmlspecialchars($coverage['area_name']); ?>">
                                                        <option value="active" <?php echo $coverage['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $coverage['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                    <button type="submit">Save</button>
                                                </form>
                                            </td>
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
