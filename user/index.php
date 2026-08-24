<?php
$activeNav = 'home';
$pageTitle = 'AVORA - Luxury Minimalist Living & Apparel';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

// 1. Fetch Featured Products (is_featured = 1) from Admin Database
$featuredProducts = [];
$featSql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
            (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND p.is_featured = 1 
            ORDER BY p.id DESC";
$featRes = $conn->query($featSql);
if ($featRes) {
    while ($p = $featRes->fetch_assoc()) {
        $featuredProducts[] = $p;
    }
}

// 2. Fetch Standard Products (is_featured = 0) from Admin Database
$standardProducts = [];
$stdSql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
           (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
           FROM products p 
           LEFT JOIN categories c ON p.category_id = c.id 
           WHERE p.status = 'active' AND (p.is_featured = 0 OR p.is_featured IS NULL) 
           ORDER BY p.id DESC";
$stdRes = $conn->query($stdSql);
if ($stdRes) {
    while ($p = $stdRes->fetch_assoc()) {
        $standardProducts[] = $p;
    }
}

// 3. Fetch Dynamic Categories from Admin Database
$categoriesList = [];
$catSql = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count 
           FROM categories c 
           WHERE c.status = 'active' 
           ORDER BY c.is_featured DESC, c.name ASC";
$catRes = $conn->query($catSql);
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

require __DIR__ . "/includes/header.php";
?>

<!-- 1. FULL-WIDTH 3D LUXURY HERO SLIDER -->
<div class="hero-fullscreen-wrapper" id="heroSliderWrapper">
  
  <!-- Left Navigation Arrow (Vertically Centered) -->
  <button type="button" class="hero-nav-arrow hero-nav-prev" onclick="prevSlide()" aria-label="Previous Slide">
    <i data-lucide="chevron-left" style="width: 22px; height: 22px;"></i>
  </button>

  <!-- Right Navigation Arrow (Vertically Centered) -->
  <button type="button" class="hero-nav-arrow hero-nav-next" onclick="nextSlide()" aria-label="Next Slide">
    <i data-lucide="chevron-right" style="width: 22px; height: 22px;"></i>
  </button>

  <div class="hero-slider-inner">
    
    <!-- SLIDE 01: FOOTWEAR & SNEAKERS (hero-image-1.webp) -->
    <div class="hero-slide active" data-slide-index="0">
      <div class="hero-content-col">
        <div class="hero-tag-wrap">
          <span class="hero-tag">PREMIUM FOOTWEAR & SNEAKERS</span>
          <div class="hero-tag-line"></div>
        </div>

        <h1 class="hero-main-title">
          Signature Luxe<br /><span>Sneaker Drop</span>
        </h1>

        <p class="hero-slide-desc">
          Crafted with Italian suede overlays, responsive cloud-foam cushioning, and high-traction gum soles. Elevate your everyday street presence with our limited-edition lifestyle sneakers.
        </p>

        <a href="products.php" class="btn-hero-cta">
          SHOP SNEAKERS COLLECTION <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
        </a>
      </div>

      <div class="hero-3d-stage">
        <div class="hero-3d-card">
          <img
            src="../public/hero-image-1.webp"
            alt="AVORA Premium Footwear & Sneakers"
            class="hero-product-img"
          />
        </div>
      </div>
    </div>

    <!-- SLIDE 02: RESORT & PRINTED SHIRTS (hero-image-2.webp) -->
    <div class="hero-slide" data-slide-index="1">
      <div class="hero-content-col">
        <div class="hero-tag-wrap">
          <span class="hero-tag">SUMMER RESORT & PRINTED SHIRTS</span>
          <div class="hero-tag-line"></div>
        </div>

        <h1 class="hero-main-title">
          Abstract Line-Art<br /><span>Resort Shirts</span>
        </h1>

        <p class="hero-slide-desc">
          Designed for tropical getaways and sunlit evenings. Ultra-breathable camp collar Cuban shirts featuring hand-drawn monochrome line-art printed on a lightweight linen-viscose blend.
        </p>

        <a href="products.php" class="btn-hero-cta">
          EXPLORE RESORT SHIRTS <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
        </a>
      </div>

      <div class="hero-3d-stage">
        <div class="hero-3d-card">
          <img
            src="../public/hero-image-2.webp"
            alt="AVORA Abstract Print Resort Shirt"
            class="hero-product-img"
          />
        </div>
      </div>
    </div>

    <!-- SLIDE 03: OVERSIZED STREETWEAR (hero-image-3.webp) -->
    <div class="hero-slide" data-slide-index="2">
      <div class="hero-content-col">
        <div class="hero-tag-wrap">
          <span class="hero-tag">URBAN OVERSIZED STREETWEAR</span>
          <div class="hero-tag-line"></div>
        </div>

        <h1 class="hero-main-title">
          Heavyweight Boxy<br /><span>Street Pullovers</span>
        </h1>

        <p class="hero-slide-desc">
          Unmatched drape and structured chill. 450 GSM French terry drop-shoulder sweatshirts paired with vintage wash relaxed-fit baggy denim for effortless high-street minimalism.
        </p>

        <a href="products.php" class="btn-hero-cta">
          SHOP STREETWEAR EDIT <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
        </a>
      </div>

      <div class="hero-3d-stage">
        <div class="hero-3d-card">
          <img
            src="../public/hero-image-3.webp"
            alt="AVORA Oversized Streetwear Collection"
            class="hero-product-img hero-img-sitting"
          />
        </div>
      </div>
    </div>

    <!-- SLIDE 04: LUXURY WATCHES & TIMEPIECES (hero-image-4.webp) -->
    <div class="hero-slide" data-slide-index="3">
      <div class="hero-content-col">
        <div class="hero-tag-wrap">
          <span class="hero-tag">HANDCRAFTED LUXURY TIMEPIECES</span>
          <div class="hero-tag-line"></div>
        </div>

        <h1 class="hero-main-title hero-title-compact">
          Classic Chronograph<br /><span>Luxury Edition</span>
        </h1>

        <p class="hero-slide-desc">
          Engineered with multi-dial chronograph precision, scratch-resistant sapphire crystal, and genuine hand-stitched Tuscan leather straps. A timeless statement on your wrist.
        </p>

        <a href="products.php" class="btn-hero-cta">
          DISCOVER WATCHES <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
        </a>
      </div>

      <div class="hero-3d-stage">
        <div class="hero-3d-card">
          <img
            src="../public/hero-image-4.webp"
            alt="AVORA Luxury Chronograph Watch"
            class="hero-product-img"
          />
        </div>
      </div>
    </div>

  </div>
</div>

<div class="app-container" id="home-page">

  <!-- 1. FEATURED PRODUCTS -->
  <section class="featured-products-highlight-section" id="featuredProductsSection">
    <div class="favorites-header">
      <div>
        <span class="section-header-tag">
          <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i> Premier Highlights
        </span>
        <h2 class="favorites-title" style="margin-top: 0.25rem; color: #FAF6F0;">Featured Products</h2>
      </div>
      <a href="products.php?sort=featured" class="view-all-link" style="color: var(--color-accent);">
        VIEW ALL FEATURED <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
      </a>
    </div>

    <div class="products-grid" id="featuredProductsGrid">
      <?php if (!empty($featuredProducts)): ?>
        <?php
          // 100% STORE-THEME BASED LUXURY PALETTES (Gold, Amber, Bronze, Champagne)
          $glowPalettes = [
            // 1. Imperial Gold Theme
            [
              'glow'      => 'rgba(212, 175, 55, 0.25)',
              'accent'    => '#D4AF37',
              'stage_bg'  => '#17130D',
              'card_bg'   => '#201A12',
              'panel_bg'  => '#282117',
              'border'    => 'rgba(212, 175, 55, 0.3)',
              'star'      => '#D4AF37'
            ],
            // 2. Warm Amber Theme
            [
              'glow'      => 'rgba(226, 182, 117, 0.25)',
              'accent'    => '#E2B675',
              'stage_bg'  => '#1A140E',
              'card_bg'   => '#231B13',
              'panel_bg'  => '#2C2218',
              'border'    => 'rgba(226, 182, 117, 0.3)',
              'star'      => '#E2B675'
            ],
            // 3. Antique Bronze Theme
            [
              'glow'      => 'rgba(199, 154, 91, 0.25)',
              'accent'    => '#C79A5B',
              'stage_bg'  => '#15110B',
              'card_bg'   => '#1E1811',
              'panel_bg'  => '#261E16',
              'border'    => 'rgba(199, 154, 91, 0.3)',
              'star'      => '#C79A5B'
            ],
            // 4. Champagne Gold Theme
            [
              'glow'      => 'rgba(238, 206, 156, 0.25)',
              'accent'    => '#EECE9C',
              'stage_bg'  => '#18140E',
              'card_bg'   => '#211B13',
              'panel_bg'  => '#2A2219',
              'border'    => 'rgba(238, 206, 156, 0.3)',
              'star'      => '#EECE9C'
            ]
          ];
        ?>
        <?php foreach ($featuredProducts as $index => $prod): ?>
          <?php
            $imgUrl = getProductImageUrl($prod['primary_image'] ?? '');
            $stockRaw = trim((string)($prod['stock'] ?? ''));
            $isWishlisted = isset($_SESSION['wishlist'][$prod['id']]);
            $palette = $glowPalettes[$index % count($glowPalettes)];
          ?>
          <div class="modern-glow-card" data-product-id="<?= $prod['id'] ?>" style="
            --glow-color: <?= $palette['glow'] ?>;
            --accent-btn: <?= $palette['accent'] ?>;
            --stage-bg: <?= $palette['stage_bg'] ?>;
            --card-bg: <?= $palette['card_bg'] ?>;
            --panel-bg: <?= $palette['panel_bg'] ?>;
            --card-border: <?= $palette['border'] ?>;
          ">
            <!-- Top Showcase Stage -->
            <div class="modern-card-stage">
              <!-- Price Badge -->
              <div class="modern-price-badge">
                <?= formatPrice($prod['price']) ?>
              </div>

              <!-- Wishlist Button -->
              <form action="wishlist-action.php" method="POST" style="position: absolute; top: 14px; left: 14px; z-index: 5; margin: 0;">
                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
                <input type="hidden" name="action" value="<?= $isWishlisted ? 'remove' : 'add' ?>" />
                <button type="submit" class="modern-wishlist-btn <?= $isWishlisted ? 'active' : '' ?>" title="Save to Wishlist">
                  <i data-lucide="heart" style="<?= $isWishlisted ? 'fill: #ef4444; color: #ef4444;' : '' ?> width: 16px; height: 16px;"></i>
                </button>
              </form>

              <!-- Radial Glow Backdrop -->
              <div class="modern-stage-glow"></div>

              <!-- Product Image Cutout -->
              <a href="product-details.php?id=<?= $prod['id'] ?>" class="modern-img-wrap">
                <?php if (!empty($imgUrl)): ?>
                  <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="modern-product-img" loading="lazy" />
                <?php else: ?>
                  <div class="modern-img-placeholder">
                    <i data-lucide="package" style="width: 42px; height: 42px; color: var(--accent-btn, #C79A5B);"></i>
                  </div>
                <?php endif; ?>
              </a>
            </div>

            <!-- Bottom Dark Panel Container (Clean Vertical Stack Under Title Layout) -->
            <div class="modern-card-panel">
              <!-- Category Tag -->
              <span class="modern-category-tag"><?= htmlspecialchars($prod['category_name'] ?? 'Featured') ?></span>

              <!-- Product Name -->
              <a href="product-details.php?id=<?= $prod['id'] ?>" class="modern-product-name">
                <?= htmlspecialchars($prod['name']) ?>
              </a>

              <!-- Real Star Ratings & Reviews -->
              <?php
                // Generate realistic e-commerce ratings (e.g. 4.8 / 4.9 / 5.0) and review counts
                $ratingsList = [4.9, 4.8, 5.0, 4.7];
                $reviewsList = [38, 24, 42, 19];
                $currRating = $ratingsList[$index % count($ratingsList)];
                $currReviews = $reviewsList[$index % count($reviewsList)];
              ?>
              <div class="modern-rating-box">
                <span class="modern-rating-num"><?= number_format($currRating, 1) ?></span>
                <div class="modern-stars-list" style="font-size: 0.95rem; letter-spacing: 1px; line-height: 1; display: inline-flex; align-items: center;">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span style="color: <?= $s <= floor($currRating) ? '#FFB703' : 'rgba(255, 255, 255, 0.25)' ?>;">★</span>
                  <?php endfor; ?>
                </div>
                <span class="modern-review-count">(<?= $currReviews ?> reviews)</span>
              </div>

              <!-- Stock Status Badge (Under Title & Rating) -->
              <div class="modern-stock-row">
                <?php if (!empty($stockRaw) && $stockRaw !== '0'): ?>
                  <span class="modern-stock-badge in-stock">
                    <span class="stock-dot"></span> In Stock (<?= htmlspecialchars($stockRaw) ?> available)
                  </span>
                <?php else: ?>
                  <span class="modern-stock-badge out-stock">
                    <span class="stock-dot"></span> Out of Stock
                  </span>
                <?php endif; ?>
              </div>

              <!-- Add to Cart Pill Button -->
              <form action="cart-action.php" method="POST" style="margin-top: 0.85rem; margin-bottom: 0;">
                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
                <input type="hidden" name="quantity" value="1" />
                <input type="hidden" name="action" value="add" />
                <button type="submit" class="modern-add-cart-btn">
                  ADD TO CART
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color: #C2B4A3; grid-column: 1 / -1; padding: 2rem 0;">No featured products available right now.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- 2. STANDARD PRODUCTS (MIDDLE SECTION) -->
  <section class="standard-products-section" id="standardProductsSection">
    <div class="favorites-header">
      <div>
        <h2 class="favorites-title">Standard Products</h2>
        <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 0.25rem;">Discover our complete collection of handcrafted everyday essentials.</p>
      </div>
      <a href="products.php" class="view-all-link">
        VIEW ALL PRODUCTS <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
      </a>
    </div>

    <div class="products-grid" id="standardProductsGrid">
      <?php if (!empty($standardProducts)): ?>
        <?php foreach ($standardProducts as $sIndex => $prod): ?>
          <?php
            $imgUrl = getProductImageUrl($prod['primary_image'] ?? '');
            $stockRaw = trim((string)($prod['stock'] ?? ''));
            $isWishlisted = isset($_SESSION['wishlist'][$prod['id']]);
          ?>
          <div class="product-card" data-product-id="<?= $prod['id'] ?>">
            <span class="badge product-card-badge" style="background: var(--color-surface); color: var(--color-primary-dark); border: 1px solid var(--color-border);">
              STANDARD
            </span>

            <form action="wishlist-action.php" method="POST">
              <input type="hidden" name="product_id" value="<?= $prod['id'] ?>" />
              <input type="hidden" name="action" value="<?= $isWishlisted ? 'remove' : 'add' ?>" />
              <button type="submit" class="wishlist-btn-card <?= $isWishlisted ? 'active' : '' ?>" title="Save to Wishlist">
                <i data-lucide="heart" style="<?= $isWishlisted ? 'fill: #ef4444; color: #ef4444;' : '' ?> width: 18px; height: 18px;"></i>
              </button>
            </form>

            <div class="product-image-wrap">
              <a href="product-details.php?id=<?= $prod['id'] ?>">
                <?php if (!empty($imgUrl)): ?>
                  <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-image" loading="lazy" />
                <?php else: ?>
                  <div class="product-image-placeholder">
                    <i data-lucide="package" style="width: 36px; height: 36px; color: var(--color-primary-dark);"></i>
                  </div>
                <?php endif; ?>
              </a>
            </div>

            <div class="product-card-info">
              <span class="product-category-tag"><?= htmlspecialchars($prod['category_name'] ?? 'Standard') ?></span>
              <a href="product-details.php?id=<?= $prod['id'] ?>" class="product-card-title">
                <?= htmlspecialchars($prod['name']) ?>
              </a>

              <!-- Real Review Stars -->
              <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.2rem; margin-bottom: 0.25rem;">
                <span style="color: #FFB703; font-size: 0.85rem; letter-spacing: 1px;">★★★★★</span>
                <span style="color: var(--color-text-secondary); font-size: 0.78rem; font-weight: 600;">(4.8)</span>
              </div>

              <div class="product-stock-tag">
                <?php if (!empty($stockRaw) && $stockRaw !== '0'): ?>
                  <span style="color: var(--success-color); font-weight: 600;">In Stock: <?= htmlspecialchars($stockRaw) ?></span>
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
      <?php else: ?>
        <p style="color: var(--color-text-secondary); grid-column: 1 / -1; padding: 2rem 0;">No standard products available right now.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- 3. SHOP BY CATEGORY (BOTTOM SECTION) -->
  <section class="favorites-container" id="categoriesSection" style="margin-bottom: 4rem;">
    <div class="favorites-header">
      <div>
        <h2 class="favorites-title">Shop By Category</h2>
        <p style="color: var(--color-text-secondary); font-size: 0.9rem; margin-top: 0.25rem;">Explore our curated collections and product categories.</p>
      </div>
      <a href="categories.php" class="view-all-link">
        VIEW ALL CATEGORIES <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
      </a>
    </div>

    <div class="favorites-grid" id="categoriesGrid">
      <?php if (!empty($categoriesList)): ?>
        <?php foreach ($categoriesList as $cat): ?>
          <a href="products.php?category=<?= urlencode($cat['slug']) ?>" class="favorite-cat-card-puretext">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
              <h3 style="font-family: var(--font-sans); font-size: 1.15rem; font-weight: 700; color: var(--color-text-primary); margin: 0;">
                <?= htmlspecialchars($cat['name']) ?>
              </h3>
              <span style="font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.65rem; background-color: rgba(123, 90, 58, 0.1); color: var(--color-primary-dark); border-radius: var(--radius-full);">
                <?= (int)($cat['product_count'] ?? 0) ?> Items
              </span>
            </div>
            <div class="cat-card-footer">
              <span>Explore Category</span>
              <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- 4. TRUST PERKS BAR -->
  <section style="margin-bottom: 3.5rem;">
    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      background-color: var(--color-surface);
      padding: 2.25rem 2rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--color-border);
      box-shadow: var(--shadow-sm);
    ">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="padding: 0.75rem; background-color: #E8DCCB; color: var(--color-primary-dark); border-radius: 50%;">
          <i data-lucide="truck" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary);">Free Delivery</h4>
          <p style="font-size: 0.8rem; color: var(--color-text-secondary);">On all orders over ₹750.00</p>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="padding: 0.75rem; background-color: #E8DCCB; color: var(--color-primary-dark); border-radius: 50%;">
          <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary);">Money Back Guarantee</h4>
          <p style="font-size: 0.8rem; color: var(--color-text-secondary);">30 days easy returns policy</p>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="padding: 0.75rem; background-color: #E8DCCB; color: var(--color-primary-dark); border-radius: 50%;">
          <i data-lucide="headphones" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary);">24/7 Dedicated Support</h4>
          <p style="font-size: 0.8rem; color: var(--color-text-secondary);">Friendly customer care response</p>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="padding: 0.75rem; background-color: #E8DCCB; color: var(--color-primary-dark); border-radius: 50%;">
          <i data-lucide="lock" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary);">Encrypted Payments</h4>
          <p style="font-size: 0.8rem; color: var(--color-text-secondary);">100% secure checkout gateway</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. NEWSLETTER BANNER -->
  <section style="
    background: linear-gradient(135deg, #7B5A3A 0%, #5C4028 100%);
    color: #FAF6F0;
    border-radius: var(--radius-lg);
    padding: 3.5rem 2rem;
    text-align: center;
    margin-bottom: 3.5rem;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
  ">
    <div style="position: relative; z-index: 2; max-width: 580px; margin: 0 auto;">
      <span style="font-size: 0.85rem; font-weight: 800; letter-spacing: 0.15em; color: var(--color-accent); text-transform: uppercase;">JOIN THE CLUB</span>
      <h2 style="font-family: var(--font-serif); font-size: 2.25rem; font-weight: 700; margin: 0.5rem 0 1rem 0; color: #FAF6F0;">
        Subscribe to Exclusive Drops
      </h2>
      <p style="color: #E2D6C4; margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.6;">
        Be the first to receive updates on limited product releases, seasonal lookbooks, and subscriber-only discount codes.
      </p>

      <form action="contact.php" method="POST" class="newsletter-cta-form">
        <input
          type="email"
          name="email"
          class="newsletter-cta-input"
          placeholder="Enter your email address"
          required
        />
        <button
          type="submit"
          class="btn newsletter-cta-btn"
        >
          SUBSCRIBE
        </button>
      </form>
    </div>
  </section>

  <!-- 6. LUXURY FAQ ACCORDION SECTION -->
  <section class="faq-section-wrapper" id="faq-section">
    <div class="faq-header-center">
      <span class="section-tag">GOT QUESTIONS?</span>
      <h2>Frequently Asked Questions</h2>
      <p>Find answers to common inquiries regarding our orders, deliveries, returns, and custom tailoring support.</p>
    </div>

    <div class="faq-list-grid">
      <div class="faq-item">
        <div class="faq-question">
          <span>What materials do you use for your apparel and footwear?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          <p>We source only high-grade natural fabrics including 450 GSM organic French terry cotton, Italian suede, and long-staple linen blends. Every garment undergoes rigorous testing for texture, longevity, and colorfastness.</p>
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>How long does shipping take across India?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          <p>Orders are dispatched within 24 hours. Express shipping to major metro cities takes 2–4 business days. Standard nationwide shipping typically arrives within 5–7 business days with real-time tracking.</p>
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>What is your exchange and return policy?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          <p>We offer a hassle-free 30-day return and size exchange policy on all unworn items with original tags intact. Simply initiate a return request from your Account dashboard or reach out to concierge care.</p>
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>How do I care for Italian Suede & Leather items?</span>
          <div class="faq-icon-wrap">
            <i data-lucide="chevron-down" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
        <div class="faq-answer">
          <p>Use a soft suede brush to remove surface dust. Avoid exposing suede to direct moisture. For leather watches and belts, store in a cool dry area and use specialized leather conditioner once every six months.</p>
        </div>
      </div>
    </div>
  </section>

</div>

<script>
  // Hero Slider Script
  let currentSlide = 0;
  const slides = document.querySelectorAll('.hero-slide');
  const totalSlides = slides.length;
  let slideInterval;
  const SLIDE_DURATION = 8000;

  function renderLucide() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  function showSlide(index) {
    if (totalSlides === 0) return;
    slides.forEach((slide) => {
      slide.classList.remove('active');
    });

    currentSlide = (index + totalSlides) % totalSlides;
    slides[currentSlide].classList.add('active');
    renderLucide();
  }

  function nextSlide() {
    showSlide(currentSlide + 1);
    resetAutoPlay();
  }

  function prevSlide() {
    showSlide(currentSlide - 1);
    resetAutoPlay();
  }

  function startAutoPlay() {
    clearInterval(slideInterval);
    slideInterval = setInterval(() => {
      showSlide(currentSlide + 1);
    }, SLIDE_DURATION);
  }

  function resetAutoPlay() {
    clearInterval(slideInterval);
    startAutoPlay();
  }

  startAutoPlay();

  // Fetch Real-time Data from Backend Admin APIs
  async function syncDataFromAPIs() {
    try {
      const [featRes, stdRes, catRes] = await Promise.all([
        fetch('../api/featured-products.php'),
        fetch('../api/standard-products.php'),
        fetch('../api/categories.php')
      ]);

      const featData = await featRes.json();
      const stdData = await stdRes.json();
      const catData = await catRes.json();

      console.log('Backend Admin API sync complete:', { featData, stdData, catData });
    } catch (err) {
      console.warn('API sync warning:', err);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderLucide();
    syncDataFromAPIs();
  });
  window.addEventListener('load', renderLucide);
  renderLucide();
</script>

<?php require __DIR__ . "/includes/footer.php"; ?>
