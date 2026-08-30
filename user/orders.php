<?php
$activeNav = 'orders';
$pageTitle = 'My Orders - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../config/db.php";

$userId = getCurrentUserId();
$highlightOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$searchOrderId = isset($_GET['search_order']) ? (int)$_GET['search_order'] : 0;
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

// Fetch user order statistics
$statTotal = 0;
$statActive = 0;
$statDelivered = 0;
$statSpent = 0.00;

$stStmt = $conn->prepare("SELECT status, total_amount FROM orders WHERE user_id = ?");
$stStmt->bind_param("i", $userId);
$stStmt->execute();
$stRes = $stStmt->get_result();
if ($stRes) {
    while ($r = $stRes->fetch_assoc()) {
        $statTotal++;
        $statSpent += (float)$r['total_amount'];
        $st = strtolower($r['status']);
        if (in_array($st, ['pending', 'confirmed', 'shipped'])) {
            $statActive++;
        } elseif ($st === 'delivered') {
            $statDelivered++;
        }
    }
}
$stStmt->close();

// Fetch orders with optional status or search filter
$orders = [];
if ($searchOrderId > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $searchOrderId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
} else if (!empty($statusFilter) && $statusFilter !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND LOWER(status) = ? ORDER BY id DESC");
    $stmt->bind_param("is", $userId, $statusFilter);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt->close();
}

// Fetch items for all fetched orders
$ordersWithItems = [];
foreach ($orders as $o) {
    $oId = (int)$o['id'];
    $items = [];
    $itemSql = "SELECT oi.*, p.name AS product_name, p.slug AS product_slug, 
                (SELECT image FROM products_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS primary_image 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
    $iStmt = $conn->prepare($itemSql);
    $iStmt->bind_param("i", $oId);
    $iStmt->execute();
    $iRes = $iStmt->get_result();
    if ($iRes) {
        while ($item = $iRes->fetch_assoc()) {
            $item['image'] = getProductImageUrl($item['primary_image'] ?? '');
            $items[] = $item;
        }
    }
    $iStmt->close();

    $o['items'] = $items;
    $ordersWithItems[] = $o;
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container" style="padding-top: 1rem; padding-bottom: 3.5rem;">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">My Orders</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">My Orders</h1>
      <p class="section-subtitle">View past order details, review purchased items, and manage your account purchases.</p>
    </div>
  </div>

  <!-- Summary Statistics Bar -->
  <div class="orders-stats-grid">
    <div class="orders-stat-card">
      <div class="orders-stat-icon">
        <i data-lucide="package" style="width: 24px; height: 24px;"></i>
      </div>
      <div class="orders-stat-info">
        <span class="orders-stat-label">Total Orders</span>
        <span class="orders-stat-value"><?= $statTotal ?></span>
      </div>
    </div>

    <div class="orders-stat-card">
      <div class="orders-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #D97706;">
        <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
      </div>
      <div class="orders-stat-info">
        <span class="orders-stat-label">In Progress</span>
        <span class="orders-stat-value"><?= $statActive ?></span>
      </div>
    </div>

    <div class="orders-stat-card">
      <div class="orders-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
        <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
      </div>
      <div class="orders-stat-info">
        <span class="orders-stat-label">Delivered</span>
        <span class="orders-stat-value"><?= $statDelivered ?></span>
      </div>
    </div>

    <div class="orders-stat-card">
      <div class="orders-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #4F46E5;">
        <i data-lucide="wallet" style="width: 24px; height: 24px;"></i>
      </div>
      <div class="orders-stat-info">
        <span class="orders-stat-label">Total Spend</span>
        <span class="orders-stat-value"><?= formatPrice($statSpent) ?></span>
      </div>
    </div>
  </div>

  <!-- Filter & Search Header Bar -->
  <div class="orders-filter-bar">
    <div class="orders-filter-tabs">
      <?php
        $filters = [
          'all' => 'All Orders',
          'pending' => 'Pending',
          'confirmed' => 'Confirmed',
          'shipped' => 'Shipped',
          'delivered' => 'Delivered',
          'cancelled' => 'Cancelled'
        ];
      ?>
      <?php foreach ($filters as $key => $label): ?>
        <a href="orders.php?status=<?= $key ?>" class="orders-filter-tab <?= ($statusFilter === $key) ? 'active' : '' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>

    <form method="GET" action="orders.php" class="orders-search-box">
      <input
        type="number"
        name="search_order"
        class="orders-search-input"
        placeholder="Search Order ID (e.g. 102)"
        value="<?= $searchOrderId > 0 ? $searchOrderId : '' ?>"
      />
      <button type="submit" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.35rem;">
        <i data-lucide="search" style="width: 14px; height: 14px;"></i> Lookup
      </button>
      <?php if ($searchOrderId > 0 || $statusFilter !== 'all'): ?>
        <a href="orders.php" class="btn btn-outline btn-sm" style="color: var(--text-muted);">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($ordersWithItems)): ?>
    <div style="text-align: center; padding: 4.5rem 2rem; background-color: var(--color-surface); border-radius: var(--radius-lg); border: 1px solid var(--color-border); margin-bottom: 3.5rem; box-shadow: var(--shadow-sm);">
      <div style="width: 72px; height: 72px; border-radius: 50%; background-color: rgba(199, 154, 91, 0.12); color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
        <i data-lucide="package-open" style="width: 36px; height: 36px;"></i>
      </div>
      <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--color-text-primary); margin-bottom: 0.5rem;">No Orders Found</h2>
      <p style="color: var(--color-text-secondary); margin-bottom: 2rem; max-width: 420px; margin-left: auto; margin-right: auto; font-size: 0.95rem;">
        <?= $searchOrderId > 0 ? 'No order matches ID #' . htmlspecialchars($searchOrderId) . '.' : 'You have not placed any orders matching this selection yet.' ?>
      </p>

      <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="products.php" class="btn btn-primary">Discover Products</a>
        <?php if ($searchOrderId > 0 || $statusFilter !== 'all'): ?>
          <a href="orders.php" class="btn btn-secondary">View All Orders</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="orders-list">
      <?php foreach ($ordersWithItems as $ord): ?>
        <?php
          $status = strtolower($ord['status'] ?? 'pending');
          $badgeClass = 'lux-status-pending';
          $statusIcon = 'clock';
          $statusLabel = 'Pending Review';

          if ($status === 'delivered') {
            $badgeClass = 'lux-status-delivered';
            $statusIcon = 'check-circle-2';
            $statusLabel = 'Delivered';
          } elseif ($status === 'shipped') {
            $badgeClass = 'lux-status-shipped';
            $statusIcon = 'truck';
            $statusLabel = 'Shipped (In Transit)';
          } elseif ($status === 'confirmed') {
            $badgeClass = 'lux-status-confirmed';
            $statusIcon = 'package-check';
            $statusLabel = 'Confirmed';
          } elseif ($status === 'cancelled') {
            $badgeClass = 'lux-status-cancelled';
            $statusIcon = 'x-circle';
            $statusLabel = 'Cancelled';
          }

          $totalItemCount = 0;
          foreach ($ord['items'] as $it) {
            $totalItemCount += (int)$it['quantity'];
          }
        ?>
        <div class="lux-order-card" style="<?= ($highlightOrderId === (int)$ord['id']) ? 'border: 2px solid var(--color-accent);' : '' ?>">
          <!-- Order Card Header -->
          <div class="lux-order-header">
            <div class="lux-order-meta">
              <div class="lux-order-number">
                <i data-lucide="hash" style="width: 16px; height: 16px; color: var(--color-primary-dark);"></i>
                Order #ORD-<?= $ord['id'] ?>
              </div>
              <div class="lux-order-date">
                <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                Placed on <?= date('M d, Y \a\t h:i A', strtotime($ord['created_at'])) ?>
              </div>
            </div>

            <div class="lux-status-badge <?= $badgeClass ?>">
              <i data-lucide="<?= $statusIcon ?>" style="width: 14px; height: 14px;"></i>
              <?= $statusLabel ?>
            </div>
          </div>

          <!-- Order Items List -->
          <div class="lux-order-body">
            <div class="lux-order-items-list">
              <?php foreach ($ord['items'] as $it): ?>
                <div class="lux-order-item-row">
                  <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    <img
                      src="<?= $it['image'] ?>"
                      alt="<?= htmlspecialchars($it['product_name'] ?? 'Product') ?>"
                      class="lux-order-item-thumb"
                    />
                    <div class="lux-order-item-details">
                      <a href="product-details.php?id=<?= $it['product_id'] ?>" class="lux-order-item-title">
                        <?= htmlspecialchars($it['product_name'] ?? 'Product #' . $it['product_id']) ?>
                      </a>
                      <div class="lux-order-item-meta">
                        <span>Quantity: <strong><?= (int)$it['quantity'] ?></strong></span>
                        <span>•</span>
                        <span><?= formatPrice($it['price']) ?> each</span>
                      </div>
                    </div>
                  </div>
                  <div class="lux-order-item-total">
                    <?= formatPrice($it['price'] * $it['quantity']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Order Summary Footer -->
          <div class="lux-order-footer">
            <div class="lux-order-total-summary">
              <span class="lux-order-total-label">
                Total Amount (<?= $totalItemCount ?> <?= $totalItemCount === 1 ? 'item' : 'items' ?>):
              </span>
              <span class="lux-order-total-amount">
                <?= formatPrice($ord['total_amount']) ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
