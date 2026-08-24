<?php
$activeNav = '';
$pageTitle = 'Shopping Bag - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$cartItems = [];
$subtotal = 0.00;

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $idList = implode(',', $ids);
    
    $sql = "SELECT p.*, c.name AS category_name, 
            (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
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
                'slug' => $prod['slug'],
                'category_name' => $prod['category_name'],
                'price' => (float)$prod['price'],
                'stock' => $prod['stock'],
                'image' => getProductImageUrl($prod['primary_image'] ?? ''),
                'quantity' => $qty,
                'total' => $itemTotal
            ];
        }
    }
}

// Calculations
$promoDiscountPercent = (float)($_SESSION['promo_discount'] ?? 0);
$promoCode = $_SESSION['promo_code'] ?? '';
$discountAmount = ($promoDiscountPercent > 0) ? ($subtotal * ($promoDiscountPercent / 100)) : 0;
$afterDiscount = max(0, $subtotal - $discountAmount);

// Free shipping if net subtotal >= 750
$shippingCost = ($afterDiscount >= 750 || $subtotal == 0) ? 0.00 : 150.00;
$taxAmount = $afterDiscount * 0.08; // 8% estimated tax
$grandTotal = $afterDiscount + $shippingCost + $taxAmount;

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">Shopping Bag</span>
  </div>

  <div class="section-header" style="margin-bottom: 1.5rem;">
    <div>
      <h1 class="section-title">Your Shopping Bag</h1>
      <p class="section-subtitle">Review your selected items before proceeding to checkout.</p>
    </div>
    <?php if (!empty($cartItems)): ?>
      <a href="cart-action.php?action=clear" class="btn btn-secondary btn-sm" onclick="return confirm('Are you sure you want to clear your entire bag?');">
        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Clear Bag
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($cartItems)): ?>
    <div style="text-align: center; padding: 5rem 2rem; background-color: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 3rem;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
        <i data-lucide="shopping-bag" style="width: 32px; height: 32px;"></i>
      </div>
      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">Your Bag is Empty</h2>
      <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
        Looks like you haven't added anything yet. Explore our curated catalog and find something exceptional today.
      </p>
      <a href="products.php" class="btn btn-primary btn-lg">
        Start Shopping <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
      </a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <!-- Items Table -->
      <div>
        <table class="cart-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th style="text-align: center;">Quantity</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cartItems as $item): ?>
              <tr>
                <td>
                  <div class="cart-item-cell">
                    <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img" />
                    <div>
                      <a href="product-details.php?id=<?= $item['id'] ?>" style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                        <?= htmlspecialchars($item['name']) ?>
                      </a>
                      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                        <?= htmlspecialchars($item['category_name'] ?? 'General') ?> 
                        <?php if (!empty($item['stock'])): ?>
                          • <span style="color: var(--success-color);">Stock: <?= htmlspecialchars($item['stock']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td style="font-weight: 600;">
                  <?= formatPrice($item['price']) ?>
                </td>
                <td>
                  <form action="cart-action.php" method="POST" style="display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>" />
                    
                    <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" style="width: 28px; height: 28px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-weight: 700; background: var(--bg-main);">-</button>
                    <span style="width: 32px; text-align: center; font-weight: 700; font-size: 0.95rem;"><?= $item['quantity'] ?></span>
                    <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" style="width: 28px; height: 28px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-weight: 700; background: var(--bg-main);">+</button>
                  </form>
                </td>
                <td style="font-weight: 800; color: var(--text-main);">
                  <?= formatPrice($item['total']) ?>
                </td>
                <td style="text-align: right;">
                  <form action="cart-action.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="remove" />
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>" />
                    <button type="submit" style="color: var(--text-muted); cursor: pointer;" title="Remove Item">
                      <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
          <a href="products.php" class="btn btn-outline btn-sm">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Continue Shopping
          </a>

          <!-- Promo code form -->
          <form action="cart-action.php" method="POST" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="action" value="promo" />
            <input
              type="text"
              name="promo_code"
              placeholder="Promo Code (STITCH15)"
              value="<?= htmlspecialchars($promoCode) ?>"
              style="padding: 0.5rem 0.85rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.85rem; text-transform: uppercase;"
            />
            <button type="submit" class="btn btn-secondary btn-sm">Apply Code</button>
          </form>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div>
        <div class="summary-card">
          <h2 class="summary-title">Order Summary</h2>

          <div class="summary-row">
            <span>Bag Subtotal</span>
            <span><?= formatPrice($subtotal) ?></span>
          </div>

          <?php if ($discountAmount > 0): ?>
            <div class="summary-row" style="color: var(--success-color);">
              <span>Discount (<?= htmlspecialchars($promoCode) ?> - 15%)</span>
              <span>-<?= formatPrice($discountAmount) ?></span>
            </div>
          <?php endif; ?>

          <div class="summary-row">
            <span>Standard Shipping</span>
            <span>
              <?php if ($shippingCost == 0): ?>
                <strong style="color: var(--success-color);">FREE</strong>
              <?php else: ?>
                <?= formatPrice($shippingCost) ?>
              <?php endif; ?>
            </span>
          </div>

          <div class="summary-row">
            <span>Estimated Tax (8%)</span>
            <span><?= formatPrice($taxAmount) ?></span>
          </div>

          <div class="summary-row total">
            <span>Grand Total</span>
            <span style="color: var(--primary-color);"><?= formatPrice($grandTotal) ?></span>
          </div>

          <a href="checkout.php" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: center;">
            Proceed to Checkout <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
          </a>

          <div style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
            <i data-lucide="lock" style="width: 14px; height: 14px;"></i>
            <span>Encrypted & Secured Checkout</span>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
