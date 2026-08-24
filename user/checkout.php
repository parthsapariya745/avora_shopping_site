<?php
$activeNav = '';
$pageTitle = 'Checkout - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../config/db.php";

if (empty($_SESSION['cart'])) {
    setFlashMessage("Your shopping bag is empty. Please add items before checking out.", "error");
    header("Location: cart.php");
    exit;
}

// Fetch Cart items and calculate totals
$cartItems = [];
$subtotal = 0.00;
$ids = array_map('intval', array_keys($_SESSION['cart']));
$idList = implode(',', $ids);

$sql = "SELECT p.*, (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
        FROM products p 
        WHERE p.id IN ($idList)";
$res = $conn->query($sql);
if ($res) {
    while ($prod = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['cart'][$prod['id']]['quantity'] ?? 1);
        $itemTotal = (float)$prod['price'] * $qty;
        $subtotal += $itemTotal;

        $cartItems[] = [
            'id' => $prod['id'],
            'name' => $prod['name'],
            'price' => (float)$prod['price'],
            'stock' => $prod['stock'],
            'image' => getProductImageUrl($prod['primary_image'] ?? ''),
            'quantity' => $qty,
            'total' => $itemTotal
        ];
    }
}

// Discount & shipping
$promoDiscountPercent = (float)($_SESSION['promo_discount'] ?? 0);
$promoCode = $_SESSION['promo_code'] ?? '';
$discountAmount = ($promoDiscountPercent > 0) ? ($subtotal * ($promoDiscountPercent / 100)) : 0;
$afterDiscount = max(0, $subtotal - $discountAmount);

// Pre-fill user data if logged in
$user = null;
if (isUserLoggedIn()) {
    $uStmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $uId = getCurrentUserId();
    $uStmt->bind_param("i", $uId);
    $uStmt->execute();
    $uRes = $uStmt->get_result();
    if ($uRes && $uRes->num_rows > 0) {
        $user = $uRes->fetch_assoc();
    }
    $uStmt->close();
}

$errorMsg = '';

// Handle Checkout Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $shippingTier = trim($_POST['shipping_method'] ?? 'standard');
    $paymentMethod = trim($_POST['payment_method'] ?? 'cod');

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city)) {
        $errorMsg = "Please fill in all mandatory delivery details.";
    } else {
        // Calculate dynamic shipping cost
        $shippingCost = 0.00;
        if ($shippingTier === 'express') {
            $shippingCost = 150.00;
        } elseif ($shippingTier === 'overnight') {
            $shippingCost = 250.00;
        } else {
            // standard
            $shippingCost = ($afterDiscount >= 750) ? 0.00 : 150.00;
        }

        $tax = $afterDiscount * 0.08;
        $orderGrandTotal = $afterDiscount + $shippingCost + $tax;

        // Determine user_id
        $userId = getCurrentUserId();
        if ($userId <= 0) {
            // Check if user with this email exists
            $checkU = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkU->bind_param("s", $email);
            $checkU->execute();
            $uRes = $checkU->get_result();
            if ($uRes && $r = $uRes->fetch_assoc()) {
                $userId = (int)$r['id'];
            } else {
                // Create guest/registered user record
                $tempPass = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
                $insU = $conn->prepare("INSERT INTO users (name, email, phone, password, status) VALUES (?, ?, ?, ?, 'active')");
                $insU->bind_param("ssss", $name, $email, $phone, $tempPass);
                $insU->execute();
                $userId = $conn->insert_id;
                $insU->close();
            }
            $checkU->close();

            // Set session so they are logged in and can view their order
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }

        // Insert Order
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $orderStmt->bind_param("id", $userId, $orderGrandTotal);
        if ($orderStmt->execute()) {
            $orderId = $conn->insert_id;
            $orderStmt->close();

            // Insert Order Items
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $cItem) {
                $pId = $cItem['id'];
                $pQty = $cItem['quantity'];
                $pPrice = $cItem['price'];
                $itemStmt->bind_param("iiid", $orderId, $pId, $pQty, $pPrice);
                $itemStmt->execute();
            }
            $itemStmt->close();

            // Clear Cart & Promo
            $_SESSION['cart'] = [];
            unset($_SESSION['promo_code']);
            unset($_SESSION['promo_discount']);

            setFlashMessage("Thank you! Your order #STITCH-" . $orderId . " has been placed successfully.", "success");
            header("Location: orders.php?placed=1&order_id=" . $orderId);
            exit;
        } else {
            $errorMsg = "Database error placing order: " . $conn->error;
        }
    }
}

// Default values for display
$shippingDisplay = ($afterDiscount >= 750) ? 0.00 : 150.00;
$taxDisplay = $afterDiscount * 0.08;
$grandTotalDisplay = $afterDiscount + $shippingDisplay + $taxDisplay;

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <a href="cart.php">Shopping Bag</a>
    <span>/</span>
    <span class="active">Checkout</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">Finalize Your Order</h1>
      <p class="section-subtitle">Complete your delivery address and choose payment method.</p>
    </div>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="alert alert-error">
      <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
      <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" action="checkout.php" class="cart-layout" autocomplete="off">
    <!-- Dummy fields to stop browser autofill -->
    <input type="text" name="fake_username_remember" style="display:none;" tabindex="-1" autocomplete="off" />
    <input type="password" name="fake_password_remember" style="display:none;" tabindex="-1" autocomplete="new-password" />

    <!-- Left Column: Shipping & Payment Details -->
    <div>
      <!-- Shipping Address Box -->
      <div class="summary-card" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="map-pin" style="color: var(--primary-color); width: 20px; height: 20px;"></i>
          Shipping & Contact Details
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input
              type="text"
              name="full_name"
              class="form-input"
              required
              placeholder="e.g. Rahul Sharma"
              autocomplete="off"
              value="<?= htmlspecialchars($_POST['full_name'] ?? $user['name'] ?? '') ?>"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input
              type="tel"
              name="phone"
              class="form-input"
              required
              placeholder="e.g. 9876543210"
              autocomplete="off"
              value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>"
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input
            type="email"
            name="email"
            class="form-input"
            required
            placeholder="e.g. rahul@example.com"
            autocomplete="off"
            value="<?= htmlspecialchars($_POST['email'] ?? $user['email'] ?? '') ?>"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Street Address *</label>
          <input
            type="text"
            name="address"
            class="form-input"
            required
            placeholder="House / Flat No., Apartment, Street, Landmark"
            autocomplete="off"
            value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
          />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label class="form-label">City *</label>
            <input
              type="text"
              name="city"
              class="form-input"
              required
              placeholder="e.g. Mumbai"
              value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
            />
          </div>

          <div class="form-group">
            <label class="form-label">State</label>
            <input
              type="text"
              name="state"
              class="form-input"
              placeholder="e.g. Maharashtra"
              value="<?= htmlspecialchars($_POST['state'] ?? '') ?>"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Postal / ZIP Code</label>
            <input
              type="text"
              name="zip"
              class="form-input"
              placeholder="e.g. 400001"
              value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>"
            />
          </div>
        </div>
      </div>

      <!-- Delivery Method Box -->
      <div class="summary-card" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="truck" style="color: var(--primary-color); width: 20px; height: 20px;"></i>
          Choose Delivery Speed
        </h3>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
          <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <input type="radio" name="shipping_method" value="standard" checked style="accent-color: var(--primary-color);" />
              <div>
                <strong style="font-size: 0.95rem;">Standard Delivery</strong>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Delivered in 3-5 business days</p>
              </div>
            </div>
            <strong style="color: var(--success-color);">
              <?= ($afterDiscount >= 750) ? 'FREE' : '₹150.00' ?>
            </strong>
          </label>

          <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <input type="radio" name="shipping_method" value="express" style="accent-color: var(--primary-color);" />
              <div>
                <strong style="font-size: 0.95rem;">Express Priority Shipping</strong>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Delivered in 1-2 business days</p>
              </div>
            </div>
            <strong>₹150.00</strong>
          </label>

          <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <input type="radio" name="shipping_method" value="overnight" style="accent-color: var(--primary-color);" />
              <div>
                <strong style="font-size: 0.95rem;">Same-Day / Overnight Courier</strong>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Delivered next morning</p>
              </div>
            </div>
            <strong>₹250.00</strong>
          </label>
        </div>
      </div>

      <!-- Payment Method Box -->
      <div class="summary-card">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="credit-card" style="color: var(--primary-color); width: 20px; height: 20px;"></i>
          Payment Options
        </h3>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <input type="radio" name="payment_method" value="cod" checked style="accent-color: var(--primary-color);" />
            <div>
              <strong style="font-size: 0.95rem;">Cash on Delivery (COD) / Pay on Delivery</strong>
              <p style="font-size: 0.8rem; color: var(--text-muted);">Pay securely in cash or UPI upon package arrival.</p>
            </div>
          </label>

          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <input type="radio" name="payment_method" value="upi" style="accent-color: var(--primary-color);" />
            <div>
              <strong style="font-size: 0.95rem;">Instant UPI / QR Code</strong>
              <p style="font-size: 0.8rem; color: var(--text-muted);">Google Pay, PhonePe, Paytm, BHIM</p>
            </div>
          </label>

          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; background: var(--bg-main);">
            <input type="radio" name="payment_method" value="card" style="accent-color: var(--primary-color);" />
            <div>
              <strong style="font-size: 0.95rem;">Debit / Credit Card & Net Banking</strong>
              <p style="font-size: 0.8rem; color: var(--text-muted);">Visa, MasterCard, RuPay, Corporate Banking</p>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Right Column: Order Items & Summary -->
    <div>
      <div class="summary-card" style="position: sticky; top: 100px;">
        <h3 class="summary-title">Order Overview (<?= count($cartItems) ?> items)</h3>

        <!-- Mini items list -->
        <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 260px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 0.5rem;">
          <?php foreach ($cartItems as $item): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover;" />
                <div>
                  <h4 style="font-size: 0.85rem; font-weight: 700; max-width: 170px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($item['name']) ?></h4>
                  <span style="font-size: 0.75rem; color: var(--text-muted);">Qty: <?= $item['quantity'] ?> • <?= formatPrice($item['price']) ?></span>
                </div>
              </div>
              <span style="font-size: 0.9rem; font-weight: 700;"><?= formatPrice($item['total']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="summary-row">
          <span>Bag Subtotal</span>
          <span><?= formatPrice($subtotal) ?></span>
        </div>

        <?php if ($discountAmount > 0): ?>
          <div class="summary-row" style="color: var(--success-color);">
            <span>Discount (<?= htmlspecialchars($promoCode) ?>)</span>
            <span>-<?= formatPrice($discountAmount) ?></span>
          </div>
        <?php endif; ?>

        <div class="summary-row">
          <span>Standard Shipping</span>
          <span>
            <?= ($shippingDisplay == 0) ? '<strong style="color: var(--success-color);">FREE</strong>' : formatPrice($shippingDisplay) ?>
          </span>
        </div>

        <div class="summary-row">
          <span>Estimated Tax (8%)</span>
          <span><?= formatPrice($taxDisplay) ?></span>
        </div>

        <div class="summary-row total">
          <span>Total to Pay</span>
          <span style="color: var(--primary-color); font-size: 1.35rem;"><?= formatPrice($grandTotalDisplay) ?></span>
        </div>

        <button type="submit" name="place_order" value="1" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: center;">
          <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
          Place Order Now
        </button>

        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 1rem;">
          By placing your order you agree to AVORA's terms of sale and privacy policy.
        </p>
      </div>
    </div>
  </form>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
