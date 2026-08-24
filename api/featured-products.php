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

$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
        (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' AND p.is_featured = 1 
        ORDER BY p.id DESC";

$res = $conn->query($sql);
$products = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['image_url'] = getProductImageUrl($row['primary_image'] ?? '');
        $row['formatted_price'] = formatPrice($row['price']);
        $row['is_wishlisted'] = isset($_SESSION['wishlist'][$row['id']]);
        $products[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Featured products retrieved successfully from database',
    'count' => count($products),
    'data' => $products
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
