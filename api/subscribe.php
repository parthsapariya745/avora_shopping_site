<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../user/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$productId = (int)($input['product_id'] ?? 0);
$planId = trim($input['plan_id'] ?? '');

$planName = '';
$addedId = 0;

if ($productId > 0) {
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $prod = $res->fetch_assoc()) {
        $addedId = (int)$prod['id'];
        $planName = $prod['name'];
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$addedId])) {
            $_SESSION['cart'][$addedId]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$addedId] = [
                'product_id' => $addedId,
                'quantity' => 1
            ];
        }
    }
    $stmt->close();
}

if ($addedId === 0 && !empty($planId)) {
    // Fallback search by slug
    $slug = ($planId === 'featured' || $planId === 'plan_featured') ? 'featured-vip-elite-pass' : 'standard-privilege-member';
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $prod = $res->fetch_assoc()) {
        $addedId = (int)$prod['id'];
        $planName = $prod['name'];
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$addedId])) {
            $_SESSION['cart'][$addedId]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$addedId] = [
                'product_id' => $addedId,
                'quantity' => 1
            ];
        }
    }
    $stmt->close();
}

if ($addedId > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Added "' . $planName . '" to your shopping bag!',
        'product_id' => $addedId,
        'cart_count' => getCartCount()
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to subscribe. Plan product not found.'
    ]);
}
