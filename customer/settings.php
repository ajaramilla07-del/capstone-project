<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requireLogin();

$customer = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':id' => $customer['id'],
            ]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            header('Location: settings.php?updated=1');
            exit;
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000'
                ? 'That email address is already in use.'
                : 'Unable to update your profile right now.';
        }
    }
}

$customerName = htmlspecialchars((string) ($customer['name'] ?? ''));
$customerEmail = htmlspecialchars((string) ($customer['email'] ?? ''));
$errorMessage = htmlspecialchars($error);
$successMessage = isset($_GET['updated']) ? 'Profile updated successfully.' : '';

$pageTitle = 'Settings';
$activePage = 'settings.php';

$pageContent = <<<HTML
<div class="section-panel">
    <h1 class="page-header">Settings</h1>

    <div class="form-card">
        <div class="form-grid">
            <?php if ($errorMessage !== ''): ?>
                <div class="error"><?php echo $errorMessage; ?></div>
            <?php elseif ($successMessage !== ''): ?>
                <div class="success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <label>
                Full name
                <input type="text" name="name" value="$customerName" required />
            </label>
            <label>
                Email address
                <input type="email" name="email" value="$customerEmail" required />
            </label>
            <button class="primary-btn" type="submit">Save changes</button>
        </div>
    </div>
</div>
HTML;

include __DIR__ . '/../includes/customer-layout.php';
