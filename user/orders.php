<?php
$activeNav = 'orders';
$pageTitle = 'My Orders & Tracking - AVORA';
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../config/db.php";

$userId = getCurrentUserId();
$highlightOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$searchOrderId = isset($_GET['search_order']) ? (int)$_GET['search_order'] : 0;

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

<div class="app-container">
  <!-- Breadcrumbs -->
  <div class="breadcrumbs">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">My Orders</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">Order History & Tracking</h1>
      <p class="section-subtitle">Track deliveries, inspect purchase details, and manage past orders.</p>
    </div>

    <!-- Quick lookup by Order ID -->
    <form method="GET" action="orders.php" style="display: flex; gap: 0.5rem;">
      <input
        type="number"
        name="search_order"
        placeholder="Enter Order ID (e.g. 102)"
        value="<?= $searchOrderId > 0 ? $searchOrderId : '' ?>"
        style="padding: 0.5rem 0.85rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.85rem;"
      />
      <button type="submit" class="btn btn-secondary btn-sm">Lookup</button>
    </form>
  </div>

  <?php if (empty($ordersWithItems)): ?>
    <div style="text-align: center; padding: 5rem 2rem; background-color: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 3rem;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
        <i data-lucide="package-open" style="width: 32px; height: 32px;"></i>
      </div>
      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">No Orders Found</h2>
      <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
        You have not placed any orders yet. Discover our quality collection!
      </p>

      <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="products.php" class="btn btn-primary">Explore Products</a>
      </div>
    </div>
  <?php else: ?>
    <div class="orders-list">
      <?php foreach ($ordersWithItems as $ord): ?>
        <?php
          $status = strtolower($ord['status'] ?? 'pending');
          $badgeClass = 'badge-warning';
          if ($status === 'delivered') $badgeClass = 'badge-success';
          elseif ($status === 'shipped') $badgeClass = 'badge-primary';
          elseif ($status === 'cancelled') $badgeClass = 'badge-danger';
        ?>
        <div class="order-card" style="<?= ($highlightOrderId === (int)$ord['id']) ? 'border: 2px solid var(--primary-color);' : '' ?>">
          <div class="order-header">
            <div>
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                  Order #STITCH-<?= $ord['id'] ?>
                </h3>
                <span class="badge <?= $badgeClass ?>"><?= strtoupper($status) ?></span>
              </div>
              <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                Placed on <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
              </p>
            </div>

            <div style="text-align: right;">
              <div style="font-size: 0.8rem; color: var(--text-muted);">Total Amount</div>
              <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary-color);">
                <?= formatPrice($ord['total_amount']) ?>
              </div>
            </div>
          </div>

          <!-- Items list in this order -->
          <div class="order-items-preview">
            <?php foreach ($ord['items'] as $it): ?>
              <div class="order-item-row" style="padding: 0.75rem; background: var(--bg-main); border-radius: var(--radius-md);">
                <img src="<?= $it['image'] ?>" alt="<?= htmlspecialchars($it['product_name'] ?? 'Product') ?>" style="width: 52px; height: 52px; object-fit: cover; border-radius: var(--radius-sm);" />
                <div style="flex: 1;">
                  <a href="product-details.php?id=<?= $it['product_id'] ?>" style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">
                    <?= htmlspecialchars($it['product_name'] ?? 'Product #' . $it['product_id']) ?>
                  </a>
                  <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Quantity: <?= (int)$it['quantity'] ?> • <?= formatPrice($it['price']) ?> each
                  </div>
                </div>
                <div style="font-weight: 800; font-size: 1rem;">
                  <?= formatPrice($it['price'] * $it['quantity']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Delivery Status Steps -->
          <div class="tracking-timeline" style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--border-color);">
            <div class="tracking-step done">
              <div class="tracking-dot"></div>
              <span>Order Placed</span>
            </div>

            <div class="tracking-step <?= in_array($status, ['confirmed', 'shipped', 'delivered']) ? 'done' : '' ?>">
              <div class="tracking-dot"></div>
              <span>Processing</span>
            </div>

            <div class="tracking-step <?= in_array($status, ['shipped', 'delivered']) ? 'done' : '' ?>">
              <div class="tracking-dot"></div>
              <span>Dispatched</span>
            </div>

            <div class="tracking-step <?= ($status === 'delivered') ? 'done' : '' ?>">
              <div class="tracking-dot"></div>
              <span>Delivered</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
