<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../user/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$categoryId = (int)($_GET['category_id'] ?? 0);
$productId = (int)($_GET['product_id'] ?? 0);

if ($categoryId > 0) {
    $stmt = $conn->prepare("SELECT p.*, c.name AS category_name, 
            (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND p.category_id = ? AND p.id != ? 
            ORDER BY p.id DESC LIMIT 6");
    $stmt->bind_param("ii", $categoryId, $productId);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT p.*, c.name AS category_name, 
            (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' 
            ORDER BY RAND() LIMIT 4");
}

$related = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['image_url'] = getProductImageUrl($row['primary_image'] ?? '');
        $row['formatted_price'] = formatPrice($row['price']);
        $related[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Related products retrieved successfully from database',
    'count' => count($related),
    'data' => $related
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
