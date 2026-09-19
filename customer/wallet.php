<?php
$pageTitle = 'Wallet';
$activePage = 'wallet.php';

$pageContent = <<<'HTML'
<div class="section-panel">
    <h1 class="page-header">Wallet</h1>

    <div class="wallet-panel">
        <div class="wallet-note">Balance</div>
        <div class="wallet-balance">Kes.15,000.48</div>
        <div class="wallet-note">Top up to order more food</div>
        <button class="primary-btn" type="button">Top up</button>
    </div>
</div>
HTML;

include __DIR__ . '/../includes/customer-layout.php';
