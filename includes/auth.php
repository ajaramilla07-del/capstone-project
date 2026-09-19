<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    $userRole = currentUser()['role'] ?? '';
    if (!in_array($userRole, $allowedRoles, true)) {
        header('Location: /login.php?error=unauthorized');
        exit;
    }
}

function redirectToRoleDashboard(string $role): void
{
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: /staff/orders.php');
    } elseif ($role === 'rider') {
        header('Location: /rider/deliveries.php');
    } else {
        header('Location: /customer/menu.php');
    }
    exit;
}

function normalizeOrderStatus(string $status): string
{
    $normalized = trim($status);
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    $aliases = [
        'Pending' => 'Pending',
        'pending' => 'Pending',
        'Preparing' => 'Preparing',
        'preparing' => 'Preparing',
        'Ready for pick up' => 'Ready for pickup',
        'Ready for Pick Up' => 'Ready for pickup',
        'Ready for Pick-up' => 'Ready for pickup',
        'Ready for Pickup' => 'Ready for pickup',
        'ready for pickup' => 'Ready for pickup',
        'ready for pick up' => 'Ready for pickup',
        'Out for delivery' => 'Out for delivery',
        'Out for Delivery' => 'Out for delivery',
        'out for delivery' => 'Out for delivery',
        'Delivered' => 'Delivered',
        'delivered' => 'Delivered',
    ];

    return $aliases[$normalized] ?? $normalized;
}
