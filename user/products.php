<?php
$activeNav = 'products';
$pageTitle = 'Products Catalog - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

// Fetch all active categories with product counts
$categoriesList = [];
$catRes = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count 
                        FROM categories c 
                        WHERE c.status = 'active' 
                        ORDER BY c.name ASC");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

// Total active product count
$totalCount = 0;
$countRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status = 'active'");
if ($countRes && $row = $countRes->fetch_assoc()) {
    $totalCount = (int)$row['total'];
}

// Request parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$inStockOnly = isset($_GET['stock']) && $_GET['stock'] == '1';
$sort = trim($_GET['sort'] ?? 'featured');

// Find active category ID if categorySlug is provided
$currentCategory = null;
if (!empty($categorySlug) && $categorySlug !== 'all') {
    $catStmt = $conn->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
    $catStmt->bind_param("s", $categorySlug);
    $catStmt->execute();
    $cRes = $catStmt->get_result();
    if ($cRes && $cRes->num_rows > 0) {
        $currentCategory = $cRes->fetch_assoc();
    }
    $catStmt->close();
}

// Build query
$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
        (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.slug LIKE ?)";
    $searchWild = "%" . $search . "%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= "sss";
}

if ($currentCategory) {
    $sql .= " AND p.category_id = ?";
    $params[] = $currentCategory['id'];
    $types .= "i";
}

if ($maxPrice > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

if ($inStockOnly) {
    $sql .= " AND (p.stock IS NOT NULL AND p.stock != '' AND p.stock != '0' AND LOWER(p.stock) != 'out of stock')";
}

// Sorting logic
switch ($sort) {
    case 'price-asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price-desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.id DESC";
        break;
    case 'featured':
    default:
        $sql .= " ORDER BY p.is_featured DESC, p.id DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
if ($result) {
    while ($p = $result->fetch_assoc()) {
        $products[] = $p;
    }
}
$stmt->close();

require __DIR__ . "/includes/header.php";
?>

<div class="app-container" id="products-page">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <a href="products.php">Products</a>
    <?php if ($currentCategory): ?>
      <span>/</span>
      <span class="active"><?= htmlspecialchars($currentCategory['name']) ?></span>
    <?php elseif (!empty($search)): ?>
      <span>/</span>
      <span class="active">Search: "<?= htmlspecialchars($search) ?>"</span>
    <?php endif; ?>
  </div>

  <!-- Page Title & Header -->
  <div class="section-header" style="margin-bottom: 1.5rem;">
    <div>
      <h1 class="section-title">
        <?= $currentCategory ? htmlspecialchars($currentCategory['name']) : (!empty($search) ? 'Search Results' : 'All Products') ?>
      </h1>
      <p class="section-subtitle">
        Showing <strong><?= count($products) ?></strong> items from our catalog.
      </p>
    </div>
  </div>

  <div class="products-page-layout">
    <!-- Sidebar Filters -->
    <aside class="filter-sidebar">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="filter" style="width: 18px; height: 18px;"></i> Filters
        </h3>
        <a href="products.php" style="font-size: 0.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 0.25rem;">
          <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i> Reset
        </a>
      </div>

      <form id="filterForm" method="GET" action="products.php">
        <?php if (!empty($search)): ?>
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>" />
        <?php endif; ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />

        <!-- Category Filter -->
        <div class="filter-group">
          <h4 class="filter-group-title">Categories</h4>
          <ul class="filter-option-list">
            <li>
              <label class="filter-checkbox">
                <input type="radio" name="category" value="all" <?= (empty($categorySlug) || $categorySlug === 'all') ? 'checked' : '' ?> onchange="this.form.submit()" />
                <span>All Categories (<?= $totalCount ?>)</span>
              </label>
            </li>
            <?php foreach ($categoriesList as $c): ?>
              <li>
                <label class="filter-checkbox">
                  <input type="radio" name="category" value="<?= htmlspecialchars($c['slug']) ?>" <?= $categorySlug === $c['slug'] ? 'checked' : '' ?> onchange="this.form.submit()" />
                  <span><?= htmlspecialchars($c['name']) ?> (<?= (int)$c['product_count'] ?>)</span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Availability Filter -->
        <div class="filter-group">
          <h4 class="filter-group-title">Availability</h4>
          <label class="filter-checkbox">
            <input type="checkbox" name="stock" value="1" <?= $inStockOnly ? 'checked' : '' ?> onchange="this.form.submit()" />
            <span>In Stock Only</span>
          </label>
        </div>

        <!-- Price Range Slider -->
        <div class="filter-group">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <h4 class="filter-group-title" style="margin: 0;">Max Price</h4>
            <span id="priceDisplay" style="font-weight: 700; color: var(--primary-color); font-size: 0.9rem;">
              <?= $maxPrice > 0 ? formatPrice($maxPrice) : '₹5000' ?>
            </span>
          </div>
          <input
            type="range"
            name="max_price"
            id="priceSlider"
            min="50"
            max="5000"
            step="50"
            value="<?= $maxPrice > 0 ? (int)$maxPrice : 5000 ?>"
            style="width: 100%; accent-color: var(--primary-color);"
            oninput="document.getElementById('priceDisplay').textContent = '₹' + this.value"
            onchange="this.form.submit()"
          />
          <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            <span>₹50</span>
            <span>₹5000</span>
          </div>
        </div>
      </form>
    </aside>

    <!-- Main Catalog Content -->
    <section>
      <!-- Sort Toolbar -->
      <div class="products-toolbar">
        <div style="font-size: 0.9rem; color: var(--text-muted);">
          Catalog Display
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="sliders-horizontal" style="color: var(--text-muted); width: 16px; height: 16px;"></i>
          <span style="font-size: 0.85rem; font-weight: 600;">Sort by:</span>
          <select
            class="form-select"
            style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: auto;"
            onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); window.location.href = url.toString();"
          >
            <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured Picks</option>
            <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
          </select>
        </div>
      </div>

      <!-- Products Grid -->
      <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 4rem 2rem; background-color: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
          <div style="width: 56px; height: 56px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
            <i data-lucide="search-x" style="width: 28px; height: 28px;"></i>
          </div>
          <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">No Products Found</h3>
          <p style="color: var(--text-muted); margin-bottom: 1.5rem;">We couldn't find any products matching your chosen filters.</p>
          <a href="products.php" class="btn btn-primary">Clear All Filters</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $prod): ?>
            <?php
              $imgUrl = getProductImageUrl($prod['primary_image'] ?? '');
              $stockRaw = trim((string)($prod['stock'] ?? ''));
              $isWishlisted = isset($_SESSION['wishlist'][$prod['id']]);
            ?>
            <div class="product-card">
              <?php if (!empty($prod['is_featured'])): ?>
                <span class="badge badge-primary product-card-badge">FEATURED</span>
              <?php endif; ?>

              <form action="wishlist-action.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
                <input type="hidden" name="action" value="<?= $isWishlisted ? 'remove' : 'add' ?>" />
                <button type="submit" class="wishlist-btn-card <?= $isWishlisted ? 'active' : '' ?>" title="Save to Wishlist">
                  <i data-lucide="heart" style="<?= $isWishlisted ? 'fill: #ef4444; color: #ef4444;' : '' ?> width: 18px; height: 18px;"></i>
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
                  <?php if (!empty($stockRaw) && $stockRaw !== '0'): ?>
                    <span style="color: var(--success-color); font-weight: 600;">Stock: <?= htmlspecialchars($stockRaw) ?></span>
                  <?php else: ?>
                    <span style="color: var(--danger-color); font-weight: 600;">Out of Stock</span>
                  <?php endif; ?>
                </div>

                <div class="product-price-row">
                  <div class="price-box">
                    <span class="current-price"><?= formatPrice($prod['price']) ?></span>
                  </div>

                  <form action="cart-action.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
                    <input type="hidden" name="quantity" value="1" />
                    <input type="hidden" name="action" value="add" />
                    <button type="submit" class="add-cart-btn-sm" title="Add to Cart">
                      <i data-lucide="shopping-bag" style="width: 18px; height: 18px;"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
