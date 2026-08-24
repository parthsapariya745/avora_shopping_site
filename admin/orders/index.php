<?php
$basePath = "../";
$activeGroup = 'orders';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Orders Management';
$pageCss = 'orders.css';
$breadcrumbHtml = '<a href="../dashboard.php">Admin</a> <span>/</span> <span>Customer Orders</span>';

$msg = "";
$msgType = "success";

// Handle Delete Order Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $msg = "Order successfully deleted.";
        $msgType = "success";
    } else {
        $msg = "Failed to delete order: " . $conn->error;
        $msgType = "error";
    }
    $stmt->close();
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'ALL');

$sql = "SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $cleanNum = preg_replace('/[^0-9]/', '', $search);
    if (!empty($cleanNum)) {
        $sql .= " AND (o.id = ? OR u.name LIKE ? OR u.email LIKE ?)";
        $likeSearch = "%" . $search . "%";
        $orderIdInt = (int)$cleanNum;
        $params[] = $orderIdInt;
        $params[] = $likeSearch;
        $params[] = $likeSearch;
        $types .= "iss";
    } else {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
        $likeSearch = "%" . $search . "%";
        $params[] = $likeSearch;
        $params[] = $likeSearch;
        $types .= "ss";
    }
}

$validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
if ($statusFilter !== 'ALL' && in_array(strtolower($statusFilter), $validStatuses)) {
    $sql .= " AND o.status = ?";
    $params[] = strtolower($statusFilter);
    $types .= "s";
}

$sql .= " ORDER BY o.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Customer Orders & Dispatches</h1>
      <p>Track store purchases, payment fulfillment statuses, and packaging shipments.</p>
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
        <input type="text" name="search" placeholder="Search by Order ID or customer email..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div style="display:flex; gap:0.5rem; align-items:center;">
        <select class="filter-select" name="status" onchange="this.form.submit()">
          <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>Status: All Orders</option>
          <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
          <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
          <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <?php if (!empty($search) || $statusFilter !== 'ALL'): ?>
          <a href="index.php" class="btn btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order Ref</th>
            <th>Customer</th>
            <th>Placed Date</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                No customer orders match your query.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $ord): ?>
              <tr>
                <td>
                  <span class="order-id-tag">#ORD-<?= $ord['id'] ?></span>
                </td>
                <td>
                  <div class="customer-name-text"><?= htmlspecialchars(!empty($ord['customer_name']) ? $ord['customer_name'] : 'Customer #' . $ord['user_id']) ?></div>
                  <div class="customer-email-text"><?= htmlspecialchars($ord['customer_email'] ?: 'No email available') ?></div>
                </td>
                <td>
                  <?= date('M d, Y - h:i A', strtotime($ord['created_at'])) ?>
                </td>
                <td>
                  <span class="price-text">₹<?= number_format($ord['total_amount'], 2) ?></span>
                </td>
                <td>
                  <span class="order-badge <?= htmlspecialchars(strtolower($ord['status'])) ?>">
                    <?= htmlspecialchars(ucfirst($ord['status'])) ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display:inline-flex; gap:0.4rem;">
                    <a href="details.php?id=<?= $ord['id'] ?>" class="btn-view-details">View Details</a>
                    <a href="index.php?action=delete&id=<?= $ord['id'] ?>" class="btn-view-details" style="color:#dc2626; border-color:#fecaca;" onclick="return confirm('Are you sure you want to delete this order?');">Delete</a>
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
