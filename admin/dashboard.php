<?php
$basePath = "";
$activeGroup = 'dashboard';
require_once __DIR__ . "/includes/auth.php";
require __DIR__ . '/../config/db.php';

$pageTitle = 'Executive Dashboard';
$pageCss = 'dashboard.css';
$breadcrumbHtml = '<span>Dashboard</span>';

// 1. Total Revenue
$totalRevenue = 0.00;
$revRes = $conn->query("SELECT COALESCE(SUM(total_amount), 0) AS rev FROM orders WHERE status != 'cancelled'");
if ($revRes) {
    $totalRevenue = (float)$revRes->fetch_assoc()['rev'];
}

// 2. Total Orders
$totalOrders = 0;
$ordRes = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($ordRes) {
    $totalOrders = (int)$ordRes->fetch_assoc()['total'];
}

// 3. Pending Dispatches
$pendingOrders = 0;
$pendRes = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'");
if ($pendRes) {
    $pendingOrders = (int)$pendRes->fetch_assoc()['total'];
}

// 4. Products Count & Stock
$totalProducts = 0;
$outOfStockCount = 0;
$prodRes = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN stock = '' OR stock = '0' OR LOWER(stock) = 'out of stock' THEN 1 ELSE 0 END) AS out_of_stock FROM products");
if ($prodRes) {
    $pRow = $prodRes->fetch_assoc();
    $totalProducts = (int)$pRow['total'];
    $outOfStockCount = (int)$pRow['out_of_stock'];
}

// 5. Total Categories
$totalCategories = 0;
$catRes = $conn->query("SELECT COUNT(*) AS total FROM categories");
if ($catRes) {
    $totalCategories = (int)$catRes->fetch_assoc()['total'];
}

// 6. Total Users
$totalUsers = 0;
$usrRes = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($usrRes) {
    $totalUsers = (int)$usrRes->fetch_assoc()['total'];
}

// 7. Recent 5 Orders
$recentOrders = [];
$rOrdersRes = $conn->query("SELECT o.*, u.name AS customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 5");
if ($rOrdersRes) {
    while ($row = $rOrdersRes->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

// 8. Recent 5 Users
$recentUsers = [];
$rUsersRes = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
if ($rUsersRes) {
    while ($row = $rUsersRes->fetch_assoc()) {
        $recentUsers[] = $row;
    }
}

// 9. Weekly Sales Data (Last 7 Days)
$days = [];
$salesMap = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($d));
    $days[$d] = $label;
    $salesMap[$d] = 0.00;
}

$chartRes = $conn->query("SELECT DATE(created_at) AS order_date, SUM(total_amount) AS daily_total FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
if ($chartRes) {
    while ($cRow = $chartRes->fetch_assoc()) {
        if (isset($salesMap[$cRow['order_date']])) {
            $salesMap[$cRow['order_date']] = (float)$cRow['daily_total'];
        }
    }
}

$maxSale = max(array_values($salesMap)) ?: 100;

require_once __DIR__ . "/includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Executive Overview</h1>
      <p>Real-time analytics and transaction metrics for AVORA</p>
    </div>
    <div style="display:flex; gap:0.5rem;">
      <a href="products/add.php" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; background:#0f172a; color:#fff; border-radius:6px; font-size:0.85rem; font-weight:500;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Product
      </a>
      <a href="categories/add.php" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; background:#fff; border:1px solid #cbd5e1; color:#334155; border-radius:6px; font-size:0.85rem; font-weight:500;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Category
      </a>
    </div>
  </div>

  <!-- Metric KPI Cards -->
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-label">Total Revenue</span>
        <div class="metric-icon" style="background:#f0fdf4; color:#16a34a;">₹</div>
      </div>
      <div class="metric-value">₹<?= number_format($totalRevenue, 2) ?></div>
      <div class="metric-trend trend-positive">&uarr; Completed transactions</div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-label">Total Orders</span>
        <div class="metric-icon" style="background:#eff6ff; color:#2563eb;">#</div>
      </div>
      <div class="metric-value"><?= number_format($totalOrders) ?></div>
      <div class="metric-trend" style="color:#64748b;"><?= $pendingOrders ?> pending review</div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-label">Product Catalog</span>
        <div class="metric-icon" style="background:#faf5ff; color:#9333ea;">&Xi;</div>
      </div>
      <div class="metric-value"><?= number_format($totalProducts) ?></div>
      <div class="metric-trend <?= $outOfStockCount > 0 ? 'trend-negative' : 'trend-positive' ?>">
        <?= $outOfStockCount > 0 ? "$outOfStockCount out of stock" : "All items in stock" ?>
      </div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-label">Categories</span>
        <div class="metric-icon" style="background:#fef3c7; color:#d97706;">&diams;</div>
      </div>
      <div class="metric-value"><?= number_format($totalCategories) ?></div>
      <div class="metric-trend trend-positive">Active store departments</div>
    </div>

    <div class="metric-card">
      <div class="metric-header">
        <span class="metric-label">Registered Customers</span>
        <div class="metric-icon" style="background:#f1f5f9; color:#475569;">&oplus;</div>
      </div>
      <div class="metric-value"><?= number_format($totalUsers) ?></div>
      <div class="metric-trend trend-positive">Verified customer base</div>
    </div>
  </div>

  <!-- Weekly Revenue Activity Chart Bar -->
  <div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
      <div class="card-title">Weekly Sales Performance (₹)</div>
    </div>
    <div class="card-body">
      <div style="display:flex; align-items:flex-end; justify-content:space-between; height:180px; padding:1rem 0; gap:1rem;">
        <?php foreach ($salesMap as $date => $amount): ?>
          <?php 
            $pct = $maxSale > 0 ? max(10, round(($amount / $maxSale) * 100)) : 10;
            $dayName = $days[$date];
          ?>
          <div style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end;">
            <div style="font-size:0.75rem; font-weight:600; color:#0f172a; margin-bottom:0.35rem;">₹<?= number_format($amount, 0) ?></div>
            <div style="width:100%; max-width:48px; background:linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%); border-radius:4px; height:<?= $pct ?>%; transition:height 0.3s ease;"></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.4rem; font-weight:500;"><?= $dayName ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Recent Orders & Users Grid -->
  <div class="tables-grid">
    <!-- Recent Orders -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Recent Transactions</div>
        <a href="orders/index.php" style="font-size:0.8rem; color:#2563eb; font-weight:600;">View All &rarr;</a>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentOrders)): ?>
              <tr>
                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">No orders placed yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentOrders as $ord): ?>
                <tr>
                  <td><a href="orders/details.php?id=<?= $ord['id'] ?>" style="font-weight:600; color:#2563eb;">#ORD-<?= $ord['id'] ?></a></td>
                  <td><?= htmlspecialchars($ord['customer_name'] ?: 'Customer #' . $ord['user_id']) ?></td>
                  <td><strong>₹<?= number_format($ord['total_amount'], 2) ?></strong></td>
                  <td>
                    <span class="status-pill status-<?= htmlspecialchars(strtolower($ord['status'])) ?>">
                      <?= htmlspecialchars(ucfirst($ord['status'])) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Latest Customer Signups</div>
        <a href="users/index.php" style="font-size:0.8rem; color:#2563eb; font-weight:600;">View All &rarr;</a>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Email</th>
              <th>Joined Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentUsers)): ?>
              <tr>
                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 2rem;">No customer signups yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentUsers as $u): ?>
                <tr>
                  <td>
                    <a href="users/details.php?id=<?= $u['id'] ?>" style="font-weight:600; color:#0f172a;">
                      <?= htmlspecialchars($u['name'] ?: 'User #' . $u['id']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
