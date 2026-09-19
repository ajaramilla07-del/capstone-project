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
$schemaStatements = [];
$seedStatements = [];

foreach ($statements as $statement) {
    $trimmed = trim($statement);
    if ($trimmed === '') {
        continue;
    }

    if (stripos($trimmed, 'CREATE DATABASE') === 0 || stripos($trimmed, 'USE ') === 0) {
        continue;
    }

    if (stripos($trimmed, 'CREATE TABLE') === 0) {
        $schemaStatements[] = $trimmed;
    } else {
        $seedStatements[] = $trimmed;
    }
}

foreach ($schemaStatements as $statement) {
    $pdo->exec($statement);
}

$categoryColumns = $pdo->query('SHOW COLUMNS FROM categories')->fetchAll(PDO::FETCH_COLUMN);
$usesNorthwindCategories = in_array('CategoryID', $categoryColumns, true)
    && in_array('CategoryName', $categoryColumns, true);

$duplicateProducts = $pdo->query(
    'SELECT duplicate.id, MIN(original.id) AS original_id
     FROM products AS duplicate
     INNER JOIN products AS original
         ON original.category_id = duplicate.category_id
        AND original.name = duplicate.name
        AND original.id < duplicate.id
     GROUP BY duplicate.id'
)->fetchAll();

foreach ($duplicateProducts as $duplicateProduct) {
    $pdo->beginTransaction();
    try {
        $updateOrders = $pdo->prepare('UPDATE order_details SET product_id = :original_id WHERE product_id = :duplicate_id');
        $updateOrders->execute([
            ':original_id' => $duplicateProduct['original_id'],
            ':duplicate_id' => $duplicateProduct['id'],
        ]);

        $deleteProduct = $pdo->prepare('DELETE FROM products WHERE id = :duplicate_id');
        $deleteProduct->execute([':duplicate_id' => $duplicateProduct['id']]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

$duplicateAreas = $pdo->query(
    'SELECT duplicate.id, MIN(original.id) AS original_id
     FROM delivery_coverages AS duplicate
     INNER JOIN delivery_coverages AS original
         ON original.area_name = duplicate.area_name
        AND original.id < duplicate.id
     GROUP BY duplicate.id'
)->fetchAll();

foreach ($duplicateAreas as $duplicateArea) {
    $pdo->beginTransaction();
    try {
        $updateOrders = $pdo->prepare('UPDATE orders SET delivery_coverage_id = :original_id WHERE delivery_coverage_id = :duplicate_id');
        $updateOrders->execute([
            ':original_id' => $duplicateArea['original_id'],
            ':duplicate_id' => $duplicateArea['id'],
        ]);

        $deleteArea = $pdo->prepare('DELETE FROM delivery_coverages WHERE id = :duplicate_id');
        $deleteArea->execute([':duplicate_id' => $duplicateArea['id']]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

try {
    $pdo->exec('ALTER TABLE products ADD UNIQUE KEY uq_products_category_name (category_id, name)');
} catch (PDOException $exception) {
    if ($exception->getCode() !== '42000') {
        throw $exception;
    }
}

try {
    $pdo->exec('ALTER TABLE delivery_coverages ADD UNIQUE KEY uq_delivery_coverages_area (area_name)');
} catch (PDOException $exception) {
    if ($exception->getCode() !== '42000') {
        throw $exception;
    }
}

foreach ($seedStatements as $statement) {
    if ($usesNorthwindCategories && stripos($statement, 'INSERT INTO categories') === 0) {
        continue;
    }

    $pdo->exec($statement);
}

$categoryQuery = $usesNorthwindCategories
    ? 'SELECT CategoryID AS id, CategoryName AS name FROM categories ORDER BY CategoryID'
    : 'SELECT id, name FROM categories ORDER BY id';
$categories = $pdo->query($categoryQuery)->fetchAll();
$categoryProduct = $pdo->prepare(
    'INSERT IGNORE INTO products (category_id, name, description, price, status)
     VALUES (:category_id, :name, :description, :price, :status)'
);

foreach ($categories as $category) {
    for ($number = 1; $number <= 10; $number++) {
        $categoryProduct->execute([
            ':category_id' => $category['id'],
            ':name' => $category['name'] . ' Special ' . $number,
            ':description' => 'Fresh selection from ' . $category['name'],
            ':price' => 150 + ((int) $category['id'] * 25) + ($number * 10),
            ':status' => 'active',
        ]);
    }
}

$pdo->exec('UPDATE products SET image = NULL');

echo "DB_SETUP_OK";
