<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/db.php";

// Ensure standard and featured tier products exist in products table
function getOrCreatePlanProduct($conn, $slug, $name, $price, $isFeatured, $description) {
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }
    $stmt->close();

    // Fetch first valid category_id if available
    $catId = 1;
    $catRes = $conn->query("SELECT id FROM categories WHERE status = 'active' LIMIT 1");
    if ($catRes && $catRow = $catRes->fetch_assoc()) {
        $catId = (int)$catRow['id'];
    }

    $insert = $conn->prepare("INSERT INTO products (category_id, name, slug, price, stock, is_featured, status, description) VALUES (?, ?, ?, ?, 9999, ?, 'active', ?)");
    $insert->bind_param("issdis", $catId, $name, $slug, $price, $isFeatured, $description);
    $insert->execute();
    $newId = $insert->insert_id;
    $insert->close();
    return $newId;
}

$standardId = getOrCreatePlanProduct(
    $conn,
    'standard-privilege-member',
    'Standard Privilege Member',
    999.00,
    0,
    'Standard luxury privilege membership plan with 10% catalog discounts and express shipping perks.'
);

$featuredId = getOrCreatePlanProduct(
    $conn,
    'featured-vip-elite-pass',
    'Featured VIP Elite Pass',
    2499.00,
    1,
    'Featured VIP luxury membership plan with 25% catalog discounts, priority concierge, and quarterly gifts.'
);

// Standard & Featured Tier Data API Payload
$plans = [
    [
        'id' => $standardId,
        'product_id' => $standardId,
        'slug' => 'standard',
        'title' => 'Standard Member Plan',
        'subtitle' => 'For conscious shoppers seeking everyday luxury perks & standard catalog savings',
        'price' => 999,
        'original_price' => 1499,
        'period' => 'year',
        'badge' => 'STANDARD TIER',
        'is_featured' => false,
        'color_theme' => 'standard',
        'features' => [
            '10% Flat Discount on all catalog purchases',
            'Free Standard Shipping on orders over ₹750',
            'Early Sale Access (12 Hours before public launch)',
            'Standard Customer Support via Email & Chat'
        ],
        'cta_text' => 'Join Standard Tier'
    ],
    [
        'id' => $featuredId,
        'product_id' => $featuredId,
        'slug' => 'featured',
        'title' => 'Featured VIP Elite Plan',
        'subtitle' => 'Our flagship premium privilege membership with ultimate rewards, zero fees & luxury gifts',
        'price' => 2499,
        'original_price' => 3999,
        'period' => 'year',
        'badge' => '★ MOST POPULAR & FEATURED',
        'is_featured' => true,
        'color_theme' => 'gold-highlight',
        'features' => [
            '25% Flat Discount on all luxury & new arrival collections',
            'Unlimited Free Priority Overnight Shipping (No Minimum)',
            'Priority 24/7 Personal Stylist & VIP Concierge Line',
            'Complimentary Seasonal Luxury Gift Box (Valued at ₹3,500)',
            'VIP Exclusive Access to Limited Drops (48 Hours Prior)'
        ],
        'cta_text' => 'Claim Featured VIP Membership'
    ]
];

echo json_encode([
    'status' => 'success',
    'message' => 'Membership plans retrieved successfully',
    'count' => count($plans),
    'data' => $plans
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
