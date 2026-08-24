<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$redirect = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    if ($productId > 0) {
        $_SESSION['wishlist'][$productId] = true;
        setFlashMessage("Product saved to your wishlist.", "success");
    }
} elseif ($action === 'remove') {
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    if ($productId > 0 && isset($_SESSION['wishlist'][$productId])) {
        unset($_SESSION['wishlist'][$productId]);
        setFlashMessage("Item removed from your wishlist.", "info");
    }
} elseif ($action === 'move_to_cart') {
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    if ($productId > 0) {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'quantity' => 1
            ];
        }
        unset($_SESSION['wishlist'][$productId]);
        setFlashMessage("Item moved to your shopping bag.", "success");
    }
} elseif ($action === 'clear') {
    $_SESSION['wishlist'] = [];
    setFlashMessage("Wishlist cleared.", "info");
}

header("Location: " . $redirect);
exit;
