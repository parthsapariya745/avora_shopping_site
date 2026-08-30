<?php
$basePath = "../";
$activeGroup = 'orders';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Order Details';
$pageCss = 'order-details.css';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}

$msg = "";
$msgType = "success";

// Handle Status Update
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST" && isset($_POST["status"])) {
    $newStatus = trim($_POST["status"]);
    $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $orderId);
        if ($updateStmt->execute()) {
            $msg = "Order status updated to '" . ucfirst($newStatus) . "'.";
            $msgType = "success";
        } else {
            $msg = "Failed to update order status: " . $conn->error;
            $msgType = "error";
        }
        $updateStmt->close();
    }
}

// Fetch Order & Customer
$stmt = $conn->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.created_at AS customer_since FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderRes = $stmt->get_result();
if (!$orderRes || $orderRes->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}
$order = $orderRes->fetch_assoc();
$stmt->close();

$breadcrumbHtml = '<a href="index.php">Orders</a> <span>/</span> <span>#ORD-' . $order['id'] . '</span>';

// Fetch Ordered Items
$items = [];
$itemStmt = $conn->prepare("SELECT oi.*, p.name AS product_name, p.slug AS product_slug, (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS product_image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$itemRes = $itemStmt->get_result();
if ($itemRes) {
    while ($row = $itemRes->fetch_assoc()) {
        $items[] = $row;
    }
}
$itemStmt->close();

$itemsSubtotal = 0.00;
foreach ($items as $it) {
    $itemsSubtotal += ((float)$it['price'] * (int)$it['quantity']);
}
if ($itemsSubtotal <= 0) {
    $itemsSubtotal = (float)$order['total_amount'];
}

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Order Invoice & Dispatch: #ORD-<?= $order['id'] ?></h1>
      <p>Placed on <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <div class="header-actions">
      <button onclick="window.print()" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print Invoice
      </button>
      <a href="index.php" class="btn btn-secondary">&larr; Back to Orders</a>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-box alert-<?= $msgType ?>" style="display: block;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="order-grid">
    <!-- Left Column: Items Table & Breakdown -->
    <div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">Purchased Items (<?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?>)</div>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th style="text-align: right;">Line Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr>
                  <td colspan="4" style="text-align: center; padding: 2rem; color: #94a3b8;">
                    No line items recorded for this order. Total: ₹<?= number_format($order['total_amount'], 2) ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <?php $lineTotal = (float)$item['price'] * (int)$item['quantity']; ?>
                  <tr>
                    <td>
                      <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div class="item-thumb-box">
                          <?php 
                            $imgSrc = getAdminProductImageUrl($item['product_image'] ?? '', '../../');
                            $hasImg = false;
                            if (!empty($item['product_image'])) {
                                if (strpos($item['product_image'], 'http://') === 0 || strpos($item['product_image'], 'https://') === 0) {
                                    $hasImg = true;
                                } elseif (file_exists(__DIR__ . "/../../uploads/products/" . $item['product_image'])) {
                                    $hasImg = true;
                                }
                            }
                          ?>
                          <?php if ($hasImg): ?>
                            <img src="<?= $imgSrc ?>" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                          <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                          <?php endif; ?>
                        </div>
                        <div>
                          <div class="item-name-bold"><?= htmlspecialchars($item['product_name'] ?: 'Product Item #' . $item['product_id']) ?></div>
                          <div style="font-size:0.75rem; color:#64748b; font-family:monospace;">Item Ref: #<?= $item['product_id'] ?></div>
                        </div>
                      </div>
                    </td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td><strong><?= (int)$item['quantity'] ?></strong></td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a;">
                      ₹<?= number_format($lineTotal, 2) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">Order Cost Breakdown</div>
        </div>
        <div class="card-body">
          <div class="summary-list">
            <div class="summary-row">
              <span>Items Subtotal</span>
              <span>₹<?= number_format($itemsSubtotal, 2) ?></span>
            </div>
            <div class="summary-row">
              <span>Standard Shipping & Handling</span>
              <span style="color:#16a34a;">Free Shipping</span>
            </div>
            <div class="summary-row">
              <span>Estimated Tax (0%)</span>
              <span>₹0.00</span>
            </div>
            <div class="summary-row total-row">
              <span>Grand Total</span>
              <span style="color:#2563eb;">₹<?= number_format($order['total_amount'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Status & Customer Information -->
    <div>
      <!-- Status Management Form -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Order Status</div>
          <span class="order-badge <?= htmlspecialchars(strtolower($order['status'])) ?>">
            <?= htmlspecialchars(ucfirst($order['status'])) ?>
          </span>
        </div>
        <div class="card-body">
          <form method="POST" action="details.php?id=<?= $order['id'] ?>">
            <div class="form-group">
              <label class="form-label" for="orderStatusSelect">Update Fulfillment Status</label>
              <select name="status" id="orderStatusSelect" class="form-control" style="font-weight:600;">
                <option value="pending" <?= ($order['status'] === 'pending') ? 'selected' : '' ?>>Pending Review</option>
                <option value="confirmed" <?= ($order['status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                <option value="shipped" <?= ($order['status'] === 'shipped') ? 'selected' : '' ?>>Shipped (In Transit)</option>
                <option value="delivered" <?= ($order['status'] === 'delivered') ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= ($order['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
              Apply Status Update
            </button>
          </form>
        </div>
      </div>

      <!-- Customer Summary -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Customer Information</div>
        </div>
        <div class="card-body">
          <div class="customer-info-box">
            <div>
              <div class="info-label">Customer Name</div>
              <div class="info-text"><?= htmlspecialchars(!empty($order['customer_name']) ? $order['customer_name'] : 'Customer #' . $order['user_id']) ?></div>
            </div>
            <div>
              <div class="info-label">Email Address</div>
              <div class="info-text"><?= htmlspecialchars($order['customer_email'] ?: 'No email linked') ?></div>
            </div>
            <div>
              <div class="info-label">Phone Number</div>
              <div class="info-text"><?= htmlspecialchars($order['customer_phone'] ?: 'N/A') ?></div>
            </div>
            <div>
              <div class="info-label">Customer Account ID</div>
              <div class="info-text">
                <a href="../users/details.php?id=<?= $order['user_id'] ?>" style="color:#2563eb; text-decoration:underline;">
                  View User Profile (#<?= $order['user_id'] ?>)
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
