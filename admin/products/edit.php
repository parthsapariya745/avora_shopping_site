<?php
$basePath = "../";
$activeGroup = 'products';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Edit Product';
$pageCss = 'product-form.css';
$breadcrumbHtml = '<a href="index.php">Products</a> <span>/</span> <span>Edit Product</span>';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: index.php");
    exit;
}

// Handle Delete Single Image Action
if (isset($_GET['delete_img_id'])) {
    $delImgId = (int)$_GET['delete_img_id'];
    $imgStmt = $conn->prepare("SELECT image FROM products_images WHERE id = ? AND product_id = ? LIMIT 1");
    $imgStmt->bind_param("ii", $delImgId, $productId);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    if ($imgRes && $imgRes->num_rows > 0) {
        $imgRow = $imgRes->fetch_assoc();
        $filePath = __DIR__ . "/../../uploads/products/" . $imgRow['image'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $delStmt = $conn->prepare("DELETE FROM products_images WHERE id = ?");
        $delStmt->bind_param("i", $delImgId);
        $delStmt->execute();
        $delStmt->close();
    }
    $imgStmt->close();
    header("Location: edit.php?id=" . $productId);
    exit;
}

// Fetch existing product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}
$product = $res->fetch_assoc();
$stmt->close();

// Fetch categories
$categoriesList = [];
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categoriesList[] = $c;
    }
}

// Fetch existing images
$existingImages = [];
$imgRes = $conn->query("SELECT * FROM products_images WHERE product_id = " . (int)$productId);
if ($imgRes) {
    while ($img = $imgRes->fetch_assoc()) {
        $existingImages[] = $img;
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
        // Check duplicate slug for other products
        $checkStmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $slug, $productId);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $errorMsg = "A product with the slug '" . htmlspecialchars($slug) . "' already exists. Please choose a unique slug.";
        } else {
            $updateStmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, is_featured = ?, status = ? WHERE id = ?");
            $updateStmt->bind_param("isssdsisi", $categoryId, $name, $slug, $description, $price, $stock, $isFeatured, $status, $productId);

            if ($updateStmt->execute()) {
                $updateStmt->close();

                // Handle New Image Uploads
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
                                    $imgStmt->bind_param("is", $productId, $uniqueName);
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

    // Retain posted values
    $product['name'] = $name;
    $product['slug'] = $slug;
    $product['description'] = $description;
    $product['category_id'] = $categoryId;
    $product['price'] = $price;
    $product['stock'] = $stock;
    $product['status'] = $status;
    $product['is_featured'] = $isFeatured;
}

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Edit Product Item</h1>
      <p>Modify inventory details, pricing, gallery photos, and featured promotion.</p>
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

  <form method="POST" action="edit.php?id=<?= $productId ?>" enctype="multipart/form-data">
    <div class="product-form-grid">
      <!-- Left Column -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">Product Details (ID: #<?= $product['id'] ?>)</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="prodName">Product Title</label>
              <input type="text" id="prodName" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodSlug">URL Slug</label>
              <input type="text" id="prodSlug" name="slug" class="form-control" value="<?= htmlspecialchars($product['slug']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodDesc">Full Description</label>
              <textarea id="prodDesc" name="description" class="form-control" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">Product Imagery</div>
          </div>
          <div class="card-body">
            <?php if (!empty($existingImages)): ?>
              <div style="font-size:0.82rem; font-weight:700; color:#334155; margin-bottom:0.65rem;">Existing Product Photos:</div>
              <div class="image-preview-grid" style="margin-top:0; margin-bottom:1.25rem;">
                <?php foreach ($existingImages as $img): ?>
                  <div class="preview-box-item">
                    <img src="<?= getProductImageUrl($img['image']) ?>" alt="Product image">
                    <a href="edit.php?id=<?= $productId ?>&delete_img_id=<?= $img['id'] ?>" 
                       onclick="return confirm('Remove this image?');" 
                       class="preview-remove-btn"
                       title="Delete Image">&times;</a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <label class="image-upload-dropzone" id="dropzoneLabel">
              <input type="file" id="prodImageFiles" name="images[]" accept="image/*" multiple style="display:none;">
              <div class="upload-icon-wrap">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
              </div>
              <div class="upload-text"><strong>Drag &amp; Drop new photos here</strong> or <span class="browse-link">browse files</span></div>
              <div class="upload-subtext">Supports PNG, JPG, WEBP, GIF up to 5MB</div>
            </label>

            <div class="image-preview-grid" id="imagePreviewContainer"></div>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">Inventory & Price</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="prodCategory">Category</label>
              <select id="prodCategory" name="category_id" class="form-control" required>
                <option value="">Select a Category</option>
                <?php foreach ($categoriesList as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodPrice">Price (₹)</label>
              <input type="number" step="0.01" id="prodPrice" name="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodStock">Stock / Quantity (e.g. 2kg, 500g, 50)</label>
              <input type="text" id="prodStock" name="stock" class="form-control" placeholder="e.g. 2kg or 50 pcs" value="<?= htmlspecialchars($product['stock']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="prodStatus">Store Status</label>
              <select id="prodStatus" name="status" class="form-control">
                <option value="active" <?= ($product['status'] === 'active') ? 'selected' : '' ?>>Active (Available for purchase)</option>
                <option value="inactive" <?= ($product['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Draft / Hidden)</option>
              </select>
            </div>

            <div class="form-group" style="margin-top:1.5rem;">
              <label class="checkbox-label">
                <input type="checkbox" id="prodFeatured" name="is_featured" value="1" <?= (!empty($product['is_featured'])) ? 'checked' : '' ?>>
                <span>Mark as Featured Product</span>
              </label>
            </div>

            <div style="margin-top:1.5rem;">
              <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Update Product</button>
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
