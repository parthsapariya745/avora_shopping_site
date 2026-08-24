<?php
$activeNav = '';
$pageTitle = 'My Saved Wishlist - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$wishlistProducts = [];

if (!empty($_SESSION['wishlist'])) {
    $ids = array_map('intval', array_keys($_SESSION['wishlist']));
    $idList = implode(',', $ids);
    
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
            (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id IN ($idList)";
    $res = $conn->query($sql);
    if ($res) {
        while ($prod = $res->fetch_assoc()) {
            $wishlistProducts[] = $prod;
        }
    }
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">Saved Wishlist</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">Saved Wishlist (<?= count($wishlistProducts) ?>)</h1>
      <p class="section-subtitle">Items you've bookmarked for later purchase.</p>
    </div>
    <?php if (!empty($wishlistProducts)): ?>
      <a href="wishlist-action.php?action=clear" class="btn btn-secondary btn-sm" onclick="return confirm('Clear your entire wishlist?');">
        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Clear Wishlist
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($wishlistProducts)): ?>
    <div style="text-align: center; padding: 5rem 2rem; background-color: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 3rem;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
        <i data-lucide="heart" style="width: 32px; height: 32px;"></i>
      </div>
      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">Your Wishlist is Empty</h2>
      <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
        Browse our catalog and tap the heart icon on any product to save it here for later.
      </p>
      <a href="products.php" class="btn btn-primary btn-lg">
        Browse Collection <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
      </a>
    </div>
  <?php else: ?>
    <div class="products-grid">
      <?php foreach ($wishlistProducts as $prod): ?>
        <?php
          $imgUrl = getProductImageUrl($prod['primary_image'] ?? '');
          $stockRaw = trim((string)($prod['stock'] ?? ''));
          $isInStock = !empty($stockRaw) && $stockRaw !== '0' && strtolower($stockRaw) !== 'out of stock';
        ?>
        <div class="product-card">
          <form action="wishlist-action.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
            <input type="hidden" name="action" value="remove" />
            <button type="submit" class="wishlist-btn-card active" title="Remove from Wishlist">
              <i data-lucide="heart" style="fill: #ef4444; color: #ef4444; width: 18px; height: 18px;"></i>
            </button>
          </form>

          <div class="product-image-wrap">
            <a href="product-details.php?id=<?= $prod['id'] ?>">
              <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-image" loading="lazy" />
            </a>
          </div>

          <div class="product-card-info">
            <span class="product-category-tag"><?= htmlspecialchars($prod['category_name'] ?? 'General') ?></span>
            <a href="product-details.php?id=<?= $prod['id'] ?>" class="product-card-title">
              <?= htmlspecialchars($prod['name']) ?>
            </a>

            <div class="product-stock-tag">
              <?php if ($isInStock): ?>
                <span style="color: var(--success-color); font-weight: 600;">Stock: <?= htmlspecialchars($stockRaw) ?></span>
              <?php else: ?>
                <span style="color: var(--danger-color); font-weight: 600;">Out of Stock</span>
              <?php endif; ?>
            </div>

            <div class="product-price-row">
              <div class="price-box">
                <span class="current-price"><?= formatPrice($prod['price']) ?></span>
              </div>

              <form action="wishlist-action.php" method="POST" style="margin: 0;">
                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
                <input type="hidden" name="action" value="move_to_cart" />
                <button type="submit" class="btn btn-primary btn-sm" title="Move to Bag" style="display: flex; gap: 0.35rem; align-items: center;">
                  <i data-lucide="shopping-bag" style="width: 14px; height: 14px;"></i> Move to Bag
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
