<?php
$base = isset($basePath) ? $basePath : "";

// Compute live counts for sidebar badges
$sidebarUserCount = 0;
$sidebarCategoryCount = 0;
$sidebarProductCount = 0;
$sidebarPendingOrdersCount = 0;
$sidebarInquiryCount = 0;

if (isset($conn) && $conn instanceof mysqli) {
    if ($res = $conn->query("SELECT COUNT(*) FROM users")) {
        $sidebarUserCount = (int)$res->fetch_row()[0];
    }
    if ($res = $conn->query("SELECT COUNT(*) FROM categories")) {
        $sidebarCategoryCount = (int)$res->fetch_row()[0];
    }
    if ($res = $conn->query("SELECT COUNT(*) FROM products")) {
        $sidebarProductCount = (int)$res->fetch_row()[0];
    }
    if ($res = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")) {
        $sidebarPendingOrdersCount = (int)$res->fetch_row()[0];
    }
    if ($res = $conn->query("SHOW TABLES LIKE 'inquiries'")) {
        if ($res->num_rows > 0) {
            if ($inqRes = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")) {
                $sidebarInquiryCount = (int)$inqRes->fetch_row()[0];
            }
        }
    }
}

$activeGroup = $activeGroup ?? 'dashboard';
require_once __DIR__ . '/../../user/includes/logo.php';
?>
<!-- Desktop & Mobile Sidebar -->
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-brand" style="padding: 1.25rem 1rem;">
    <?= renderAvoraLogo('light', 'sm', $base . 'dashboard.php') ?>
  </div>

  <ul class="sidebar-menu">
    <li class="menu-category">Management</li>
    <li>
      <a href="<?= $base ?>dashboard.php" class="menu-link <?= ($activeGroup === 'dashboard') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        </span>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="<?= $base ?>users/index.php" class="menu-link <?= ($activeGroup === 'users') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </span>
        <span>Users</span>
        <span class="badge-count" id="badgeUsers"><?= $sidebarUserCount ?></span>
      </a>
    </li>
    <li>
      <a href="<?= $base ?>categories/index.php" class="menu-link <?= ($activeGroup === 'categories') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        </span>
        <span>Categories</span>
        <span class="badge-count" id="badgeCategories"><?= $sidebarCategoryCount ?></span>
      </a>
    </li>
    <li>
      <a href="<?= $base ?>products/index.php" class="menu-link <?= ($activeGroup === 'products') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </span>
        <span>Products</span>
        <span class="badge-count" id="badgeProducts"><?= $sidebarProductCount ?></span>
      </a>
    </li>
    <li>
      <a href="<?= $base ?>orders/index.php" class="menu-link <?= ($activeGroup === 'orders') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        </span>
        <span>Orders</span>
        <span class="badge-count" id="badgeOrders" style="background:#fffbeb; color:#b45309;"><?= $sidebarPendingOrdersCount ?></span>
      </a>
    </li>
    <li>
      <a href="<?= $base ?>inquiries/index.php" class="menu-link <?= ($activeGroup === 'inquiries') ? 'active' : '' ?>">
        <span class="menu-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        </span>
        <span>Inquiries</span>
        <span class="badge-count" id="badgeInquiries" style="background:#f0fdf4; color:#15803d;"><?= $sidebarInquiryCount ?></span>
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= $base ?>logout.php" class="logout-link" id="sidebarLogoutBtn">
      <span class="menu-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      </span>
      <span>Logout</span>
    </a>
  </div>
</aside>
