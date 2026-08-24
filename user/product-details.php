<?php
$activeNav = 'products';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$productSlug = trim($_GET['slug'] ?? '');

$product = null;
if ($productId > 0) {
    $stmt = $conn->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.id = ? AND p.status = 'active' LIMIT 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
    }
    $stmt->close();
} elseif (!empty($productSlug)) {
    $stmt = $conn->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
    $stmt->bind_param("s", $productSlug);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    setFlashMessage("The requested product could not be found.", "error");
    header("Location: products.php");
    exit;
}

$pageTitle = htmlspecialchars($product['name']) . ' - AVORA';

// Fetch product gallery images
$galleryImages = [];
$imgStmt = $conn->prepare("SELECT image FROM products_images WHERE product_id = ? ORDER BY id ASC");
$imgStmt->bind_param("i", $product['id']);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
if ($imgRes) {
    while ($row = $imgRes->fetch_assoc()) {
        $galleryImages[] = getProductImageUrl($row['image']);
    }
}
$imgStmt->close();

// Fallback if no gallery image
if (empty($galleryImages)) {
    $galleryImages[] = getProductImageUrl('');
}

// Fetch related products
$relatedProducts = [];
$relStmt = $conn->prepare("SELECT p.*, c.name AS category_name, 
                           (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
                           ORDER BY p.id DESC 
                           LIMIT 4");
$relStmt->bind_param("ii", $product['category_id'], $product['id']);
$relStmt->execute();
$relRes = $relStmt->get_result();
if ($relRes) {
    while ($r = $relRes->fetch_assoc()) {
        $relatedProducts[] = $r;
    }
}
$relStmt->close();

$stockRaw = trim((string)($product['stock'] ?? ''));
$isInStock = !empty($stockRaw) && $stockRaw !== '0' && strtolower($stockRaw) !== 'out of stock';
$maxStock = 100;
if (preg_match('/(\d+)/', $stockRaw, $m)) {
    $maxStock = (int)$m[1];
} elseif (!$isInStock) {
    $maxStock = 0;
}
$isWishlisted = isset($_SESSION['wishlist'][$product['id']]);

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <a href="products.php">Products</a>
    <?php if (!empty($product['category_name'])): ?>
      <span>/</span>
      <a href="products.php?category=<?= htmlspecialchars($product['category_slug'] ?? '') ?>"><?= htmlspecialchars($product['category_name']) ?></a>
    <?php endif; ?>
    <span>/</span>
    <span class="active"><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <!-- Product Details Layout -->
  <div class="product-details-container">
    <!-- Image Gallery Section -->
    <div class="product-gallery">
      <img
        src="<?= $galleryImages[0] ?>"
        alt="<?= htmlspecialchars($product['name']) ?>"
        class="main-gallery-img"
        id="mainGalleryImg"
      />

      <?php if (count($galleryImages) > 1): ?>
        <div class="thumbnail-row">
          <?php foreach ($galleryImages as $idx => $imgSrc): ?>
            <img
              src="<?= $imgSrc ?>"
              alt="Thumbnail <?= $idx + 1 ?>"
              class="thumbnail-img <?= $idx === 0 ? 'active' : '' ?>"
            />
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Product Info Section -->
    <div class="details-content">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span class="badge badge-primary"><?= htmlspecialchars($product['category_name'] ?? 'General') ?></span>
        
        <form action="wishlist-action.php" method="POST" style="margin: 0;">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
          <input type="hidden" name="action" value="<?= $isWishlisted ? 'remove' : 'add' ?>" />
          <button type="submit" class="btn btn-secondary btn-sm" style="display: flex; gap: 0.35rem; align-items: center;">
            <i data-lucide="heart" style="<?= $isWishlisted ? 'fill: #ef4444; color: #ef4444;' : '' ?> width: 16px; height: 16px;"></i>
            <?= $isWishlisted ? 'Saved' : 'Wishlist' ?>
          </button>
        </form>
      </div>

      <h1 class="details-title"><?= htmlspecialchars($product['name']) ?></h1>

      <div class="details-price-row">
        <span class="details-price"><?= formatPrice($product['price']) ?></span>
      </div>

      <!-- Stock Status -->
      <div class="details-stock-badge">
        <?php if ($isInStock): ?>
          <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
            In Stock: <?= htmlspecialchars($stockRaw) ?>
          </span>
        <?php else: ?>
          <span class="badge badge-danger" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
            Currently Out of Stock
          </span>
        <?php endif; ?>
      </div>

      <!-- Product Description -->
      <div class="details-description">
        <p><?= nl2br(htmlspecialchars($product['description'] ?? 'No detailed description available for this item.')) ?></p>
      </div>

      <!-- Add to Cart Form with Interactive Quantity Selector -->
      <form action="cart-action.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1rem;">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
        <input type="hidden" name="action" value="add" />

        <div class="modern-qty-selector-container">
          <label class="modern-qty-label">SELECT QUANTITY</label>
          <div class="modern-qty-control-wrap">
            <div class="modern-qty-pill <?= !$isInStock ? 'disabled-control' : '' ?>">
              <button 
                type="button" 
                class="qty-btn qty-btn-minus" 
                id="qtyMinusBtn" 
                onclick="decrementQty(<?= $maxStock ?>)" 
                <?= !$isInStock ? 'disabled' : '' ?>
                aria-label="Decrease quantity"
              >
                <i data-lucide="minus" style="width: 14px; height: 14px;"></i>
              </button>

              <input 
                type="number" 
                id="qtyInput" 
                name="quantity" 
                value="1" 
                min="1" 
                max="<?= $maxStock ?>" 
                oninput="validateQtyInput(this, <?= $maxStock ?>)"
                class="qty-number-input"
                <?= !$isInStock ? 'disabled' : '' ?>
              />

              <button 
                type="button" 
                class="qty-btn qty-btn-plus" 
                id="qtyPlusBtn" 
                onclick="incrementQty(<?= $maxStock ?>)" 
                <?= (!$isInStock || $maxStock <= 1) ? 'disabled' : '' ?>
                aria-label="Increase quantity"
              >
                <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
              </button>
            </div>

            <div class="qty-stock-indicator" id="qtyStockNotice">
              <?php if ($isInStock): ?>
                <span class="stock-available-text">✓ <?= $maxStock ?> units available in stock</span>
              <?php else: ?>
                <span class="stock-limit-alert out-of-stock-alert">❌ Currently Out of Stock</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <button
          type="submit"
          class="btn btn-primary btn-lg"
          id="addToBagBtn"
          style="display: flex; gap: 0.75rem; justify-content: center; align-items: center; width: 100%; padding: 0.85rem 1.5rem; font-weight: 700; border-radius: 50px;"
          <?= !$isInStock ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '' ?>
        >
          <i data-lucide="shopping-bag" style="width: 20px; height: 20px;"></i>
          <?= $isInStock ? 'Add to Bag' : 'Out of Stock' ?>
        </button>
      </form>

      <script>
      function decrementQty(maxStock) {
        const input = document.getElementById('qtyInput');
        const plusBtn = document.getElementById('qtyPlusBtn');
        const notice = document.getElementById('qtyStockNotice');
        
        let current = parseInt(input.value) || 1;
        if (current > 1) {
          current--;
          input.value = current;
        }
        
        if (current < maxStock) {
          if (plusBtn) plusBtn.disabled = false;
          if (notice) notice.innerHTML = `<span class="stock-available-text">✓ ${maxStock} units available in stock</span>`;
        }
      }

      function incrementQty(maxStock) {
        const input = document.getElementById('qtyInput');
        const plusBtn = document.getElementById('qtyPlusBtn');
        const notice = document.getElementById('qtyStockNotice');
        
        let current = parseInt(input.value) || 1;
        if (current < maxStock) {
          current++;
          input.value = current;
        }
        
        if (current >= maxStock) {
          if (plusBtn) plusBtn.disabled = true;
          if (notice) notice.innerHTML = `<span class="stock-limit-alert">⚠️ Maximum available units (${maxStock}) reached! Out of stock for additional units.</span>`;
        } else {
          if (notice) notice.innerHTML = `<span class="stock-available-text">✓ ${maxStock} units available in stock</span>`;
        }
      }

      function validateQtyInput(inputEl, maxStock) {
        let val = parseInt(inputEl.value) || 1;
        const plusBtn = document.getElementById('qtyPlusBtn');
        const notice = document.getElementById('qtyStockNotice');
        
        if (val <= 1) {
          inputEl.value = 1;
          if (plusBtn) plusBtn.disabled = false;
          if (notice) notice.innerHTML = `<span class="stock-available-text">✓ ${maxStock} units available in stock</span>`;
        } else if (val >= maxStock) {
          inputEl.value = maxStock;
          if (plusBtn) plusBtn.disabled = true;
          if (notice) notice.innerHTML = `<span class="stock-limit-alert">⚠️ Maximum available units (${maxStock}) reached! Out of stock for additional units.</span>`;
        } else {
          if (plusBtn) plusBtn.disabled = false;
          if (notice) notice.innerHTML = `<span class="stock-available-text">✓ ${maxStock} units available in stock</span>`;
        }
      }
      </script>

      <!-- Trust highlights -->
      <div style="
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 1rem;
      ">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="truck" style="color: var(--primary-color); width: 18px; height: 18px;"></i>
          <span>Free delivery over ₹750</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="shield-check" style="color: var(--primary-color); width: 18px; height: 18px;"></i>
          <span>100% Genuine Quality</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Related Products Section -->
  <?php if (!empty($relatedProducts)): ?>
    <section style="margin: 4rem 0 2rem 0;">
      <div class="section-header">
        <div>
          <h2 class="section-title">Related Items</h2>
          <p class="section-subtitle">Customers also looked at these popular selections.</p>
        </div>
        <a href="products.php?category=<?= htmlspecialchars($product['category_slug'] ?? '') ?>" class="btn btn-outline btn-sm">View More in Category</a>
      </div>

      <div class="products-grid">
        <?php foreach ($relatedProducts as $rel): ?>
          <?php
            $rImg = getProductImageUrl($rel['primary_image'] ?? '');
            $rStock = trim((string)($rel['stock'] ?? ''));
          ?>
          <div class="product-card">
            <div class="product-image-wrap">
              <a href="product-details.php?id=<?= $rel['id'] ?>">
                <img src="<?= $rImg ?>" alt="<?= htmlspecialchars($rel['name']) ?>" class="product-image" loading="lazy" />
              </a>
            </div>
            <div class="product-card-info">
              <span class="product-category-tag"><?= htmlspecialchars($rel['category_name'] ?? 'General') ?></span>
              <a href="product-details.php?id=<?= $rel['id'] ?>" class="product-card-title">
                <?= htmlspecialchars($rel['name']) ?>
              </a>
              <div class="product-stock-tag">
                <?php if (!empty($rStock) && $rStock !== '0'): ?>
                  <span style="color: var(--success-color); font-weight: 600;">Stock: <?= htmlspecialchars($rStock) ?></span>
                <?php else: ?>
                  <span style="color: var(--danger-color); font-weight: 600;">Out of Stock</span>
                <?php endif; ?>
              </div>
              <div class="product-price-row">
                <span class="current-price"><?= formatPrice($rel['price']) ?></span>
                <a href="product-details.php?id=<?= $rel['id'] ?>" class="btn btn-secondary btn-sm">View</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
