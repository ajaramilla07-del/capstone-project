<?php
$pageTitle = 'Favourites';
$activePage = 'favorites.php';

$pageContent = <<<'HTML'
<div class="section-panel">
    <h1 class="page-header">Favourites</h1>

    <div class="food-grid">
        <div class="food-card">
            <div class="food-thumb" style="background-image:url('https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80');"></div>
            <div class="food-meta">
                <span>Rice bowl</span>
                <span class="food-price">Kes.900</span>
            </div>
        </div>

        <div class="food-card">
            <div class="food-thumb" style="background-image:url('https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80');"></div>
            <div class="food-meta">
                <span>Burger</span>
                <span class="food-price">Kes.500</span>
            </div>
        </div>

        <div class="food-card">
            <div class="food-thumb" style="background-image:url('https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=80');"></div>
            <div class="food-meta">
                <span>Pizza</span>
                <span class="food-price">Kes.1000</span>
            </div>
        </div>
    </div>
</div>
HTML;

include __DIR__ . '/../includes/customer-layout.php';
