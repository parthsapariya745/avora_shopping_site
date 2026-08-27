<?php
require_once __DIR__ . "/logo.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function getCurrentUserId() {
    return isUserLoggedIn() ? (int)$_SESSION['user_id'] : 0;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += (int)($item['quantity'] ?? 1);
        }
    }
    return $count;
}

function getWishlistCount() {
    return isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
}

function formatPrice($price) {
    return '₹' . number_format((float)$price, 2);
}

function formatStockDisplay($stock) {
    $stockTrim = trim((string)$stock);
    if ($stockTrim === '' || $stockTrim === '0' || strtolower($stockTrim) === 'out of stock') {
        return '<span class="badge badge-danger">Out of Stock</span>';
    }
    return '<span class="badge badge-success">' . htmlspecialchars($stockTrim) . '</span>';
}

function getProductImageUrl($imageName) {
    if (empty($imageName)) {
        return '';
    }
    if (strpos($imageName, 'http://') === 0 || strpos($imageName, 'https://') === 0) {
        return $imageName;
    }
    return '../uploads/products/' . htmlspecialchars($imageName);
}

function getCategoryImageUrl($imageName) {
    if (empty($imageName)) {
        return '';
    }
    if (strpos($imageName, 'http://') === 0 || strpos($imageName, 'https://') === 0) {
        return $imageName;
    }
    return '../uploads/categories/' . htmlspecialchars($imageName);
}

function setFlashMessage($msg, $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $msg,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}
