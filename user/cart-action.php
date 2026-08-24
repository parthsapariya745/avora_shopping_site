<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$redirect = $_SERVER['HTTP_REFERER'] ?? 'cart.php';

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($productId > 0) {
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $stockStr = trim((string)($row['stock'] ?? ''));
            $maxAvailable = 100;
            if (preg_match('/(\d+)/', $stockStr, $m)) {
                $maxAvailable = (int)$m[1];
            } elseif (empty($stockStr) || strtolower($stockStr) === 'out of stock' || $stockStr === '0') {
                $maxAvailable = 0;
            }

            if ($maxAvailable <= 0) {
                setFlashMessage('Sorry, "' . htmlspecialchars($row['name']) . '" is currently out of stock.', 'error');
            } else {
                $currentQty = $_SESSION['cart'][$productId]['quantity'] ?? 0;
                $newTotal = $currentQty + $quantity;
                if ($newTotal > $maxAvailable) {
                    $_SESSION['cart'][$productId] = [
                        'product_id' => $productId,
                        'quantity' => $maxAvailable
                    ];
                    setFlashMessage('Maximum stock reached! Adjusted quantity to ' . $maxAvailable . ' available units.', 'error');
                } else {
                    $_SESSION['cart'][$productId] = [
                        'product_id' => $productId,
                        'quantity' => $newTotal
                    ];
                    setFlashMessage('Added "' . htmlspecialchars($row['name']) . '" to your bag.', 'success');
                }
            }
        } else {
            setFlashMessage('Product not available.', 'error');
        }
        $stmt->close();
    }
} elseif ($action === 'update') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($productId > 0) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            setFlashMessage('Item removed from cart.', 'info');
        } else {
            if (isset($_SESSION['cart'][$productId])) {
                $stmt = $conn->prepare("SELECT name, stock FROM products WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $res = $stmt->get_result();
                $maxAvailable = 100;
                if ($res && $r = $res->fetch_assoc()) {
                    $stockStr = trim((string)($r['stock'] ?? ''));
                    if (preg_match('/(\d+)/', $stockStr, $m)) {
                        $maxAvailable = (int)$m[1];
                    }
                }
                $stmt->close();

                if ($quantity > $maxAvailable) {
                    $_SESSION['cart'][$productId]['quantity'] = $maxAvailable;
                    setFlashMessage('Cart updated. Reached maximum available stock limit (' . $maxAvailable . ' units).', 'error');
                } else {
                    $_SESSION['cart'][$productId]['quantity'] = $quantity;
                    setFlashMessage('Cart updated successfully.', 'success');
                }
            }
        }
    }
} elseif ($action === 'remove') {
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        setFlashMessage('Item removed from your cart.', 'info');
    }
} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    unset($_SESSION['promo_code']);
    unset($_SESSION['promo_discount']);
    setFlashMessage('Your cart has been cleared.', 'info');
} elseif ($action === 'promo') {
    $code = strtoupper(trim($_POST['promo_code'] ?? ''));
    if ($code === 'STITCH15' || $code === 'SUMMER15' || $code === 'MITRAA15') {
        $_SESSION['promo_code'] = $code;
        $_SESSION['promo_discount'] = 15;
        setFlashMessage('Promo code applied! 15% discount granted.', 'success');
    } else {
        unset($_SESSION['promo_code']);
        unset($_SESSION['promo_discount']);
        setFlashMessage('Invalid promo code. Try "STITCH15"', 'error');
    }
} elseif ($action === 'remove_promo') {
    unset($_SESSION['promo_code']);
    unset($_SESSION['promo_discount']);
    setFlashMessage('Promo code removed.', 'info');
}

header("Location: " . $redirect);
exit;
