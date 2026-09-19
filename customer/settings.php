<?php
require_once __DIR__ . '/../includes/auth.php';

$customer = currentUser() ?? [];
$customerName = htmlspecialchars((string) ($customer['name'] ?? ''));
$customerEmail = htmlspecialchars((string) ($customer['email'] ?? ''));

$pageTitle = 'Settings';
$activePage = 'settings.php';

$pageContent = <<<HTML
<div class="section-panel">
    <h1 class="page-header">Settings</h1>

    <div class="form-card">
        <div class="form-grid">
            <label>
                Full name
                <input type="text" value="$customerName" />
            </label>
            <label>
                Email address
                <input type="email" value="$customerEmail" />
            </label>
            <label>
                Phone number
                <input type="text" value="+254 712 345 678" />
            </label>
            <button class="primary-btn" type="button">Save changes</button>
        </div>
    </div>
</div>
HTML;

include __DIR__ . '/../includes/customer-layout.php';
