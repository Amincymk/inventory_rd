<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT
            p.id,
            p.name,
            c.name AS category,
            COALESCE(ws.quantity, 0) AS stock,
            p.reorder_level AS reorder,
            p.unit_price AS price
         FROM products AS p
         LEFT JOIN categories AS c ON c.id = p.category_id
         JOIN warehouses AS w ON w.name = 'Main Warehouse'
         LEFT JOIN warehouse_stock AS ws
            ON ws.product_id = p.id
            AND ws.warehouse_id = w.id
         WHERE p.is_active = 1
         ORDER BY p.name"
    );

    $stmt->execute();
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product['id'] = (string)$product['id'];
        $product['stock'] = (int)$product['stock'];
        $product['reorder'] = (int)$product['reorder'];
        $product['price'] = (float)$product['price'];
    }
    unset($product);

    echo json_encode($products, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('Products API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not load products.']);
}
