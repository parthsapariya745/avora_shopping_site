<?php
$isLoggedIn = isUserLoggedIn();
$userName = getCurrentUserName();
?>
<header class="navbar-wrapper">
  <div class="app-container main-header">
    <!-- Brand Logo -->
    <?= renderAvoraLogo('dark', 'md', 'index.php') ?>

    <!-- Navigation Menu Links (Desktop) -->
    <ul class="nav-links desktop-nav" id="navLinksList" style="padding: 0; margin: 0;">
      <li><a href="index.php" class="nav-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">Shop</a></li>
      <li><a href="categories.php" class="nav-link <?= ($activeNav ?? '') === 'categories' ? 'active' : '' ?>">Collections</a></li>
      <li><a href="products.php?sort=newest" class="nav-link <?= ($activeNav ?? '') === 'products' ? 'active' : '' ?>">New Arrivals</a></li>
      <li><a href="contact.php" class="nav-link <?= ($activeNav ?? '') === 'contact' ? 'active' : '' ?>">Contact</a></li>
    </ul>

    <!-- Header Search Bar -->
    <form action="products.php" method="GET" class="header-search">
      <button type="submit" class="search-icon-btn" aria-label="Search">
        <i data-lucide="search" style="width: 16px; height: 16px;"></i>
      </button>
      <input
        type="text"
        name="search"
        placeholder="Search collection..."
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
      />
    </form>

    <!-- Header Actions -->
    <div class="header-actions">
      <?php if ($isLoggedIn): ?>
        <a href="account.php" class="action-item" title="My Account">
          <i data-lucide="user" style="width: 20px; height: 20px;"></i>
          <span><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
        </a>
      <?php else: ?>
        <a href="login.php" class="action-item" title="Sign In">
          <i data-lucide="user" style="width: 20px; height: 20px;"></i>
        </a>
      <?php endif; ?>

      <a href="wishlist.php" class="action-item" title="Wishlist">
        <i data-lucide="heart" style="width: 20px; height: 20px;"></i>
        <?php if ($wishlistCount > 0): ?>
          <span class="action-badge"><?= $wishlistCount ?></span>
        <?php endif; ?>
      </a>

      <a href="cart.php" class="action-item" title="Cart">
        <i data-lucide="shopping-bag" style="width: 20px; height: 20px;"></i>
        <?php if ($cartCount > 0): ?>
          <span class="action-badge"><?= $cartCount ?></span>
        <?php endif; ?>
      </a>

      <button class="btn-icon mobile-toggle" id="mobileMenuBtn" aria-label="Menu">
        <i data-lucide="menu" style="width: 22px; height: 22px;"></i>
      </button>
    </div>
  </div>

  <!-- Mobile Dropdown Menu -->
  <div class="mobile-nav-drawer" id="mobileNavDrawer" style="display: none; background-color: #23170E; padding: 1rem 1.5rem; border-top: 1px solid rgba(226, 214, 196, 0.1);">
    <ul style="list-style: none; display: flex; flex-direction: column; gap: 1rem;">
      <li><a href="index.php" class="nav-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">Shop</a></li>
      <li><a href="categories.php" class="nav-link <?= ($activeNav ?? '') === 'categories' ? 'active' : '' ?>">Collections</a></li>
      <li><a href="products.php?sort=newest" class="nav-link">New Arrivals</a></li>
      <li><a href="orders.php" class="nav-link <?= ($activeNav ?? '') === 'orders' ? 'active' : '' ?>">My Orders</a></li>
      <li><a href="contact.php" class="nav-link <?= ($activeNav ?? '') === 'contact' ? 'active' : '' ?>">Contact</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a href="account.php" class="nav-link">Account (<?= htmlspecialchars($userName) ?>)</a></li>
        <li><a href="logout.php" class="nav-link" style="color: var(--danger-color);">Sign Out</a></li>
      <?php else: ?>
        <li><a href="login.php" class="nav-link">Sign In</a></li>
        <li><a href="register.php" class="nav-link">Create Account</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>
