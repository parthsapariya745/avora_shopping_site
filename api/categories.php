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

$sql = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count 
        FROM categories c 
        WHERE c.status = 'active' 
        ORDER BY c.is_featured DESC, c.name ASC";

$res = $conn->query($sql);
$categories = [];

if ($res) {
    while ($cat = $res->fetch_assoc()) {
        $cat['image_url'] = getCategoryImageUrl($cat['image'] ?? '');
        $categories[] = $cat;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Categories retrieved successfully from database',
    'count' => count($categories),
    'data' => $categories
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
