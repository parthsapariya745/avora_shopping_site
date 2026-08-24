<?php
$basePath = "../";
$activeGroup = 'categories';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Edit Category';
$pageCss = 'category-form.css';
$breadcrumbHtml = '<a href="index.php">Categories</a> <span>/</span> <span>Edit Category</span>';

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($catId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch existing category
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $catId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}
$category = $res->fetch_assoc();
$stmt->close();

$errorMsg = "";

if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $name = trim($_POST["name"] ?? "");
    $slug = trim($_POST["slug"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status = in_array($_POST["status"] ?? "", ["active", "inactive"]) ? $_POST["status"] : "active";
    $isFeatured = isset($_POST["is_featured"]) ? 1 : 0;

    // Normalize slug
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
    }

    if (empty($name) || empty($slug)) {
        $errorMsg = "Category name and slug are required.";
    } else {
        // Check duplicate slug for other categories
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $slug, $catId);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $errorMsg = "A category with the slug '" . htmlspecialchars($slug) . "' already exists. Please choose a different slug.";
        } else {
            $updateStmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, status = ?, is_featured = ? WHERE id = ?");
            $updateStmt->bind_param("ssssii", $name, $slug, $description, $status, $isFeatured, $catId);

            if ($updateStmt->execute()) {
                $updateStmt->close();
                header("Location: index.php");
                exit;
            } else {
                $errorMsg = "Database error: " . $conn->error;
            }
            $updateStmt->close();
        }
        $checkStmt->close();
    }

    // Retain posted values in case of error
    $category['name'] = $name;
    $category['slug'] = $slug;
    $category['description'] = $description;
    $category['status'] = $status;
    $category['is_featured'] = $isFeatured;
}

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Edit Category</h1>
      <p>Modify department title, URL slug, and storefront visibility.</p>
    </div>
    <div>
      <a href="index.php" class="btn btn-secondary">&larr; Back to Categories</a>
    </div>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="alert-box alert-error" style="display: block;">
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Category Details (ID: #<?= $category['id'] ?>)</div>
    </div>
    <div class="card-body">
      <form method="POST" action="edit.php?id=<?= $category['id'] ?>">
        <div class="form-group">
          <label class="form-label" for="catName">Category Name</label>
          <input type="text" id="catName" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="catSlug">URL Slug</label>
          <input type="text" id="catSlug" name="slug" class="form-control" value="<?= htmlspecialchars($category['slug']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="catDesc">Description</label>
          <textarea id="catDesc" name="description" class="form-control" rows="3"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="catStatus">Status</label>
            <select id="catStatus" name="status" class="form-control">
              <option value="active" <?= ($category['status'] === 'active') ? 'selected' : '' ?>>Active (Visible)</option>
              <option value="inactive" <?= ($category['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="catFeatured" name="is_featured" value="1" <?= (!empty($category['is_featured'])) ? 'checked' : '' ?>>
              <span>Feature on Store Homepage</span>
            </label>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
          <a href="index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Category</button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
