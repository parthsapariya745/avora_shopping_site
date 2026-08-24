<?php
$activeNav = 'categories';
$pageTitle = 'Curated Collections - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

$categories = [];
$catSql = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count 
           FROM categories c 
           WHERE c.status = 'active' 
           ORDER BY c.is_featured DESC, c.name ASC";
$res = $conn->query($catSql);
if ($res) {
    while ($c = $res->fetch_assoc()) {
        $categories[] = $c;
    }
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">Collections</span>
  </div>

  <!-- Hero Header -->
  <div class="collections-hero-header">
    <div class="collections-tag-pill">
      <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
      <span>Curated Departments</span>
    </div>
    <h1 style="font-family: var(--font-serif); font-size: 2.75rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.85rem;">
      Explore Our Collections
    </h1>
    <p style="color: var(--color-text-secondary); font-size: 1.05rem; line-height: 1.6;">
      Masterfully crafted items, sustainably sourced and designed to elevate your lifestyle.
    </p>
  </div>

  <?php if (empty($categories)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background-color: var(--color-surface); border-radius: var(--radius-lg); border: 1px solid var(--color-border); color: var(--color-text-secondary);">
      <i data-lucide="package-open" style="width: 48px; height: 48px; color: var(--color-primary); margin-bottom: 1rem;"></i>
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--color-text-primary);">No Collections Available</h3>
      <p style="margin-top: 0.5rem;">Please check back soon as our catalog is updated.</p>
    </div>
  <?php else: ?>
    <div class="collections-grid-lux">
      <?php foreach ($categories as $cat): ?>
        <?php
          $descText = !empty($cat['description']) ? $cat['description'] : 'Discover exceptional items in ' . $cat['name'] . '.';
        ?>
        <a href="products.php?category=<?= urlencode($cat['slug']) ?>" class="collection-card-puretext">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
            <h3 class="collection-title" style="margin: 0; font-family: var(--font-serif); font-size: 1.45rem;">
              <?= htmlspecialchars($cat['name']) ?>
            </h3>
            <span style="font-size: 0.825rem; font-weight: 700; padding: 0.3rem 0.85rem; background-color: rgba(123, 90, 58, 0.12); color: var(--color-primary-dark); border-radius: var(--radius-full);">
              <?= (int)$cat['product_count'] ?> Products
            </span>
          </div>

          <div class="collection-body">
            <p class="collection-desc" style="margin-bottom: 1.25rem;">
              <?= htmlspecialchars($descText) ?>
            </p>

            <div class="collection-footer-cta">
              <span>Explore Collection</span>
              <div class="cta-arrow-box">
                <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
