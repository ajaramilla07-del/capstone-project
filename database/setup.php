<?php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'Alfonj112504';

$serverDsn = sprintf('mysql:host=%s;charset=utf8mb4', $host);
$serverPdo = new PDO($serverDsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$serverPdo->exec("CREATE DATABASE IF NOT EXISTS lloyd_frontera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$schemaPath = __DIR__ . '/schema.sql';
$sql = file_get_contents($schemaPath);
if ($sql === false) {
    throw new RuntimeException('Schema file not found: ' . $schemaPath);
}

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, 'lloyd_frontera'), $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$statements = preg_split('/;\s*(?=(?:[^\'\"]|\'[^\']*\'|"[^"]*")*$)/', $sql);
foreach ($statements as $statement) {
    $trimmed = trim($statement);
    if ($trimmed === '') {
        continue;
    }

    if (stripos($trimmed, 'CREATE DATABASE') === 0 || stripos($trimmed, 'USE ') === 0) {
        continue;
    }

    $pdo->exec($trimmed);
}

echo "DB_SETUP_OK";
