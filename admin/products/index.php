<?php
$basePath = "../";
$activeGroup = 'products';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Products Catalog';
$pageCss = 'products.css';
$breadcrumbHtml = '<a href="../dashboard.php">Admin</a> <span>/</span> <span>Products</span>';

$msg = "";
$msgType = "success";

// Handle Delete Product
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    
    // Remove product image files from disk
    $imgStmt = $conn->prepare("SELECT image FROM products_images WHERE product_id = ?");
    $imgStmt->bind_param("i", $delId);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    while ($row = $imgRes->fetch_assoc()) {
        $imgPath = __DIR__ . "/../../uploads/products/" . $row['image'];
        if (file_exists($imgPath)) {
            @unlink($imgPath);
        }
    }
    $imgStmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $msg = "Product successfully deleted.";
        $msgType = "success";
    } else {
        $msg = "Failed to delete product: " . $conn->error;
        $msgType = "error";
    }
    $stmt->close();
}

// Fetch categories for filter dropdown
$categoriesList = [];
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

// Search and Filters
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? 'ALL');
$stockFilter = trim($_GET['stock'] ?? 'ALL');
$featuredFilter = trim($_GET['featured'] ?? 'ALL');

$sql = "SELECT p.*, c.name AS category_name, (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.slug LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "ss";
}

if ($categoryFilter !== 'ALL' && is_numeric($categoryFilter)) {
    $sql .= " AND p.category_id = ?";
    $params[] = (int)$categoryFilter;
    $types .= "i";
}

if ($stockFilter === 'in_stock') {
    $sql .= " AND p.stock != '' AND p.stock != '0' AND LOWER(p.stock) != 'out of stock'";
} elseif ($stockFilter === 'low_stock') {
    $sql .= " AND (CAST(p.stock AS DECIMAL(10,2)) BETWEEN 1 AND 10 OR LOWER(p.stock) LIKE '%low%')";
} elseif ($stockFilter === 'out_of_stock') {
    $sql .= " AND (p.stock = '' OR p.stock = '0' OR LOWER(p.stock) = 'out of stock')";
}

if ($featuredFilter === '1') {
    $sql .= " AND p.is_featured = 1";
} elseif ($featuredFilter === '0') {
    $sql .= " AND (p.is_featured = 0 OR p.is_featured IS NULL)";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Products & Inventory Catalog</h1>
      <p>Manage product pricing, stock availability, category assignment, and featured items.</p>
    </div>
    <div>
      <a href="add.php" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; background:#0f172a; color:#fff; border-radius:6px; font-size:0.85rem; font-weight:500;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add New Product
      </a>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-box alert-<?= $msgType ?>" style="display:block; margin-bottom: 1.25rem;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="GET" action="index.php" class="table-toolbar">
      <div class="search-input-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" name="search" placeholder="Search by product name or slug..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="toolbar-filters" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
        <select class="filter-select" name="category" onchange="this.form.submit()">
          <option value="ALL">All Categories</option>
          <?php foreach ($categoriesList as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($categoryFilter == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <select class="filter-select" name="featured" onchange="this.form.submit()">
          <option value="ALL" <?= $featuredFilter === 'ALL' ? 'selected' : '' ?>>Featured: All</option>
          <option value="1" <?= $featuredFilter === '1' ? 'selected' : '' ?>>Featured Only</option>
          <option value="0" <?= $featuredFilter === '0' ? 'selected' : '' ?>>Standard Only</option>
        </select>

        <select class="filter-select" name="stock" onchange="this.form.submit()">
          <option value="ALL" <?= $stockFilter === 'ALL' ? 'selected' : '' ?>>Stock: All</option>
          <option value="in_stock" <?= $stockFilter === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
          <option value="low_stock" <?= $stockFilter === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
          <option value="out_of_stock" <?= $stockFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>

        <?php if (!empty($search) || $categoryFilter !== 'ALL' || $stockFilter !== 'ALL' || $featuredFilter !== 'ALL'): ?>
          <a href="index.php" class="btn btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product Item</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Featured</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                No products found matching your search and filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $prod): ?>
              <?php
                $stockRaw = trim((string)($prod['stock'] ?? ''));
                $stockLower = strtolower($stockRaw);
                if ($stockRaw === '' || $stockRaw === '0' || $stockLower === 'out of stock') {
                    $stockClass = 'out-of-stock';
                    $stockLabel = !empty($stockRaw) ? $stockRaw : 'Out of Stock';
                } elseif (is_numeric($stockRaw) && (float)$stockRaw <= 5) {
                    $stockClass = 'low-stock';
                    $stockLabel = $stockRaw;
                } else {
                    $stockClass = 'in-stock';
                    $stockLabel = $stockRaw;
                }
              ?>
              <tr>
                <td>
                  <div class="product-cell">
                    <div class="product-thumb">
                      <?php if (!empty($prod['primary_image']) && file_exists(__DIR__ . "/../../uploads/products/" . $prod['primary_image'])): ?>
                        <img src="../../uploads/products/<?= htmlspecialchars($prod['primary_image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                      <?php else: ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div class="product-meta-name"><?= htmlspecialchars($prod['name']) ?></div>
                      <div class="product-meta-slug"><?= htmlspecialchars($prod['slug']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span style="font-weight: 500; color: #475569;"><?= htmlspecialchars($prod['category_name'] ?: 'Uncategorized') ?></span>
                </td>
                <td>
                  <span class="price-text">₹<?= number_format($prod['price'], 2) ?></span>
                </td>
                <td>
                  <span class="stock-indicator <?= $stockClass ?>">
                    <?= htmlspecialchars($stockLabel) ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($prod['is_featured'])): ?>
                    <span class="badge badge-featured" style="background:#fef3c7; color:#b45309; padding:0.2rem 0.55rem; border-radius:999px; font-size:0.75rem; font-weight:600;">&#9733; Featured</span>
                  <?php else: ?>
                    <span class="badge badge-standard" style="background:#f1f5f9; color:#64748b; padding:0.2rem 0.55rem; border-radius:999px; font-size:0.75rem;">Standard</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pill <?= htmlspecialchars($prod['status']) ?>">
                    <?= htmlspecialchars(ucfirst($prod['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="btn-action-group">
                    <a href="edit.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-edit">Edit</a>
                    <a href="index.php?action=delete&id=<?= $prod['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
