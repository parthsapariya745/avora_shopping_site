<?php
$basePath = "../";
$activeGroup = 'categories';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Add Category';
$pageCss = 'category-form.css';
$breadcrumbHtml = '<a href="index.php">Categories</a> <span>/</span> <span>Add New Category</span>';

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
        // Check duplicate slug
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $errorMsg = "A category with the slug '" . htmlspecialchars($slug) . "' already exists. Please choose a different slug.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, status, is_featured) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $slug, $description, $status, $isFeatured);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php");
                exit;
            } else {
                $errorMsg = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Add New Category</h1>
      <p>Create a product category department with slug identification and homepage featuring.</p>
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
      <div class="card-title">Category Information</div>
    </div>
    <div class="card-body">
      <form method="POST" action="add.php">
        <div class="form-group">
          <label class="form-label" for="catName">Category Name</label>
          <input type="text" id="catName" name="name" class="form-control" placeholder="e.g. Smart Electronics" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="catSlug">URL Slug</label>
          <input type="text" id="catSlug" name="slug" class="form-control" placeholder="e.g. smart-electronics" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="catDesc">Description</label>
          <textarea id="catDesc" name="description" class="form-control" rows="3" placeholder="Brief summary of products categorized under this department..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="catStatus">Status</label>
            <select id="catStatus" name="status" class="form-control">
              <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active (Visible)</option>
              <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="catFeatured" name="is_featured" value="1" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
              <span>Feature on Store Homepage</span>
            </label>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
          <a href="index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
