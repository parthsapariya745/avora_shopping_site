<?php
$basePath = "../";
$activeGroup = 'categories';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Categories Management';
$pageCss = 'categories.css';
$breadcrumbHtml = '<a href="../dashboard.php">Admin</a> <span>/</span> <span>Categories</span>';

$msg = "";
$msgType = "success";

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $msg = "Category successfully deleted.";
        $msgType = "success";
    } else {
        $msg = "Failed to delete category: " . $conn->error;
        $msgType = "error";
    }
    $stmt->close();
}

// Search and Filter Handling
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'ALL');
$featuredFilter = trim($_GET['featured'] ?? 'ALL');

$sql = "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count FROM categories c WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.slug LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "ss";
}

if ($statusFilter !== 'ALL' && in_array(strtolower($statusFilter), ['active', 'inactive'])) {
    $sql .= " AND c.status = ?";
    $params[] = strtolower($statusFilter);
    $types .= "s";
}

if ($featuredFilter === '1') {
    $sql .= " AND c.is_featured = 1";
} elseif ($featuredFilter === '0') {
    $sql .= " AND (c.is_featured = 0 OR c.is_featured IS NULL)";
}

$sql .= " ORDER BY c.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Categories & Departments</h1>
      <p>Organize store departments, storefront taxonomy, and featured collections.</p>
    </div>
    <div>
      <a href="add.php" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; background:#0f172a; color:#fff; border-radius:6px; font-size:0.85rem; font-weight:500;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add New Category
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
        <input type="text" name="search" placeholder="Search by name or slug..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div style="display:flex; gap:0.5rem; align-items:center;">
        <select class="filter-select" name="featured" onchange="this.form.submit()">
          <option value="ALL" <?= $featuredFilter === 'ALL' ? 'selected' : '' ?>>Featured: All</option>
          <option value="1" <?= $featuredFilter === '1' ? 'selected' : '' ?>>Featured Only</option>
          <option value="0" <?= $featuredFilter === '0' ? 'selected' : '' ?>>Standard Only</option>
        </select>

        <select class="filter-select" name="status" onchange="this.form.submit()">
          <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>Status: All</option>
          <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>

        <?php if (!empty($search) || $statusFilter !== 'ALL' || $featuredFilter !== 'ALL'): ?>
          <a href="index.php" class="btn btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Department / Category</th>
            <th>URL Slug</th>
            <th>Total Products</th>
            <th>Featured</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                No categories found matching your query.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:32px; height:32px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#475569; font-weight:700; font-size:0.8rem;">
                      <?= strtoupper(substr($cat['name'], 0, 2)) ?>
                    </div>
                    <div>
                      <div class="cat-name-bold"><?= htmlspecialchars($cat['name']) ?></div>
                      <?php if (!empty($cat['description'])): ?>
                        <div style="font-size:0.75rem; color:#64748b; max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                          <?= htmlspecialchars($cat['description']) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="cat-slug-text"><?= htmlspecialchars($cat['slug']) ?></span>
                </td>
                <td>
                  <strong><?= (int)$cat['product_count'] ?></strong> items
                </td>
                <td>
                  <?php if (!empty($cat['is_featured'])): ?>
                    <span class="badge badge-featured" style="background:#fef3c7; color:#b45309; padding:0.2rem 0.55rem; border-radius:999px; font-size:0.75rem; font-weight:600;">&#9733; Featured</span>
                  <?php else: ?>
                    <span class="badge badge-standard" style="background:#f1f5f9; color:#64748b; padding:0.2rem 0.55rem; border-radius:999px; font-size:0.75rem;">Standard</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pill <?= htmlspecialchars($cat['status']) ?>">
                    <?= htmlspecialchars(ucfirst($cat['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="btn-action-group">
                    <a href="edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-edit">Edit</a>
                    <a href="index.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this category? Associated products will become uncategorized.');">Delete</a>
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
