<?php

function getDbConnection(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME');

    if ($dbName === false || $dbName === '' || $dbName === 'db_shop') {
        $dbName = 'lloyd_frontera';
    }

    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: 'Alfonj112504';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbName);

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
