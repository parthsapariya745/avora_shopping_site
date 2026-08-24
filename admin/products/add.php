<?php
$basePath = "../";
$activeGroup = 'products';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Add Product';
$pageCss = 'product-form.css';
$breadcrumbHtml = '<a href="index.php">Products</a> <span>/</span> <span>Add New Product</span>';

// Fetch active categories
$categoriesList = [];
$catRes = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

$errorMsg = "";

if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $name = trim($_POST["name"] ?? "");
    $slug = trim($_POST["slug"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $categoryId = (int)($_POST["category_id"] ?? 0);
    $price = (float)($_POST["price"] ?? 0);
    $stock = trim($_POST["stock"] ?? "");
    $status = in_array($_POST["status"] ?? "", ["active", "inactive"]) ? $_POST["status"] : "active";
    $isFeatured = isset($_POST["is_featured"]) ? 1 : 0;

    // Normalize slug
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
    }

    if (empty($name) || empty($slug) || $categoryId <= 0) {
        $errorMsg = "Product title, slug, and a valid category are required.";
    } else {
        // Check duplicate slug
        $checkStmt = $conn->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $errorMsg = "A product with the slug '" . htmlspecialchars($slug) . "' already exists. Please choose a unique slug.";
        } else {
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, price, stock, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdsis", $categoryId, $name, $slug, $description, $price, $stock, $isFeatured, $status);

            if ($stmt->execute()) {
                $newProductId = $stmt->insert_id;
                $stmt->close();

                // Handle Image Uploads
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . "/../../uploads/products/";
                    if (!file_exists($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }

                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $totalFiles = count($_FILES['images']['name']);

                    for ($i = 0; $i < $totalFiles; $i++) {
                        $fileName = $_FILES['images']['name'][$i];
                        $fileTmp = $_FILES['images']['tmp_name'][$i];
                        $fileError = $_FILES['images']['error'][$i];

                        if ($fileError === UPLOAD_ERR_OK && is_uploaded_file($fileTmp)) {
                            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            if (in_array($ext, $allowedExts)) {
                                $uniqueName = "prod_" . time() . "_" . uniqid() . "." . $ext;
                                $targetPath = $uploadDir . $uniqueName;

                                if (move_uploaded_file($fileTmp, $targetPath)) {
                                    $imgStmt = $conn->prepare("INSERT INTO products_images (product_id, image) VALUES (?, ?)");
                                    $imgStmt->bind_param("is", $newProductId, $uniqueName);
                                    $imgStmt->execute();
                                    $imgStmt->close();
                                }
                            }
                        }
                    }
                }

                header("Location: index.php");
                exit;
            } else {
                $errorMsg = "Database error: " . $conn->error;
            }
        }
        $checkStmt->close();
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Add New Product Item</h1>
      <p>Publish a new item with pricing, stock units, photo gallery, and featured status.</p>
    </div>
    <div>
      <a href="index.php" class="btn btn-secondary">&larr; Back to Products</a>
    </div>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="alert-box alert-error" style="display: block;">
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="add.php" enctype="multipart/form-data">
    <div class="product-form-grid">
      <!-- Left Column: Core Info & Media -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">General Information</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="prodName">Product Title</label>
              <input type="text" id="prodName" name="name" class="form-control" placeholder="e.g. 4K Ultra HD Drone with Gimbal Camera" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodSlug">URL Slug</label>
              <input type="text" id="prodSlug" name="slug" class="form-control" placeholder="4k-ultra-hd-drone-gimbal" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodDesc">Full Description</label>
              <textarea id="prodDesc" name="description" class="form-control" rows="5" placeholder="Provide complete product specifications, dimensions, and warranty details..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">Product Imagery</div>
          </div>
          <div class="card-body">
            <label class="image-upload-dropzone" id="dropzoneLabel">
              <input type="file" id="prodImageFiles" name="images[]" accept="image/*" multiple style="display:none;">
              <div class="upload-icon-wrap">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
              </div>
              <div class="upload-text"><strong>Drag &amp; Drop photos here</strong> or <span class="browse-link">browse files</span></div>
              <div class="upload-subtext">Supports PNG, JPG, WEBP, GIF up to 5MB</div>
            </label>

            <div class="image-preview-grid" id="imagePreviewContainer"></div>
          </div>
        </div>
      </div>

      <!-- Right Column: Category, Pricing, Inventory & Settings -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">Organization & Pricing</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="prodCategory">Category</label>
              <select id="prodCategory" name="category_id" class="form-control" required>
                <option value="">Select a Category</option>
                <?php foreach ($categoriesList as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodPrice">Price (₹)</label>
              <input type="number" step="0.01" id="prodPrice" name="price" class="form-control" placeholder="199.99" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodStock">Stock / Quantity (e.g. 2kg, 500g, 50)</label>
              <input type="text" id="prodStock" name="stock" class="form-control" placeholder="e.g. 2kg or 50 pcs" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodStatus">Store Status</label>
              <select id="prodStatus" name="status" class="form-control">
                <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active (Available for purchase)</option>
                <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive (Draft / Hidden)</option>
              </select>
            </div>

            <div class="form-group" style="margin-top:1.5rem;">
              <label class="checkbox-label">
                <input type="checkbox" id="prodFeatured" name="is_featured" value="1" <?= (!isset($_POST['name']) || isset($_POST['is_featured'])) ? 'checked' : '' ?>>
                <span>Mark as Featured Product</span>
              </label>
            </div>

            <div style="margin-top:1.5rem;">
              <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Publish Product</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('dropzoneLabel');
  const fileInput = document.getElementById('prodImageFiles');
  const previewGrid = document.getElementById('imagePreviewContainer');
  let selectedFiles = [];

  if (!dropzone || !fileInput) return;

  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
    }, false);
  });

  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => dropzone.classList.add('drag-active'), false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => dropzone.classList.remove('drag-active'), false);
  });

  dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files) {
      handleFiles(dt.files);
    }
  });

  fileInput.addEventListener('change', (e) => {
    if (e.target.files) {
      handleFiles(e.target.files);
    }
  });

  function handleFiles(files) {
    Array.from(files).forEach(file => {
      if (file.type.startsWith('image/')) {
        selectedFiles.push(file);
      }
    });
    updateFileInput();
    renderPreviews();
  }

  function updateFileInput() {
    try {
      const dataTransfer = new DataTransfer();
      selectedFiles.forEach(file => dataTransfer.items.add(file));
      fileInput.files = dataTransfer.files;
    } catch (err) {
      console.log('DataTransfer fallback', err);
    }
  }

  function renderPreviews() {
    previewGrid.innerHTML = '';
    selectedFiles.forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const box = document.createElement('div');
        box.className = 'preview-box-item';
        box.innerHTML = `
          <img src="${e.target.result}" alt="Preview">
          <button type="button" class="preview-remove-btn" title="Remove">&times;</button>
        `;
        box.querySelector('.preview-remove-btn').addEventListener('click', () => {
          selectedFiles.splice(index, 1);
          updateFileInput();
          renderPreviews();
        });
        previewGrid.appendChild(box);
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
