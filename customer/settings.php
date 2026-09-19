<?php
$pageTitle = 'Settings';
$activePage = 'settings.php';

$pageContent = <<<'HTML'
<div class="section-panel">
    <h1 class="page-header">Settings</h1>

    <div class="form-card">
        <div class="form-grid">
            <label>
                Full name
                <input type="text" value="Alia Mukami" />
            </label>
            <label>
                Email address
                <input type="email" value="alia@example.com" />
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
