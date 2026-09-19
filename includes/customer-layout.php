<?php
require_once __DIR__ . '/auth.php';

$customer = currentUser() ?? [];
$customerName = trim((string) ($customer['name'] ?? 'Customer'));
$customerInitial = strtoupper(substr($customerName, 0, 1));

if (!isset($navItems)) {
    $navItems = [
        ['label' => 'Home', 'link' => 'menu.php', 'icon' => 'home'],
        ['label' => 'Cart', 'link' => 'cart.php', 'icon' => 'cart'],
        ['label' => 'Checkout', 'link' => 'checkout.php', 'icon' => 'checkout'],
        ['label' => 'Orders', 'link' => 'orders.php', 'icon' => 'orders'],
        ['label' => 'Log out', 'link' => '../logout.php', 'icon' => 'logout'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle ?? 'Food ordering'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customer.css" />
</head>
<body>
    <div class="customer-shell" style="background: #f7f8f2;">
        <aside class="sidebar" style="background: rgba(220,234,217,0.25);">
            <div class="brand" style="color: #2c5c32;">Foody.</div>

            <div class="profile-block">
                <div class="avatar"><?php echo htmlspecialchars($customerInitial); ?></div>
                <div class="profile-name"><?php echo htmlspecialchars($customerName); ?></div>
            </div>

            <nav class="nav-menu" aria-label="Customer navigation">
                <?php foreach ($navItems as $item): ?>
                    <?php
                    $isActive = ($item['link'] === ($activePage ?? 'index.php'));
                    $iconName = $item['icon'];
                    ?>
                    <a href="<?php echo htmlspecialchars($item['link']); ?>" class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <?php if ($iconName === 'home'): ?>⌂<?php elseif ($iconName === 'cart'): ?>▣<?php elseif ($iconName === 'checkout'): ?>✓<?php elseif ($iconName === 'orders'): ?>◫<?php elseif ($iconName === 'logout'): ?>↩<?php else: ?>▣<?php endif; ?>
                        </span>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div class="search-wrap">
                    <span class="search-ico">⌕</span>
                    <input type="text" placeholder="Search for food" />
                </div>
            </header>

            <div class="page-body">
                <?php echo $pageContent; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/customer.js"></script>
</body>
</html>
