    </main>

    <!-- Site Footer -->
    <?php if (!($hideFooter ?? false)): ?>
    <footer class="site-footer">
      <div class="app-container">
        <div class="footer-top">
          <!-- Column 1: Brand -->
          <div class="footer-brand">
            <?= renderAvoraLogo('dark', 'md', 'index.php') ?>
            <p class="footer-desc">
              Timeless pieces made with quality materials to elevate your everyday living.
            </p>
            <div class="footer-socials">
              <a href="#" class="social-icon" aria-label="Instagram"><i data-lucide="instagram" style="width: 18px; height: 18px;"></i></a>
              <a href="#" class="social-icon" aria-label="Twitter"><i data-lucide="twitter" style="width: 18px; height: 18px;"></i></a>
            </div>
          </div>

          <!-- Column 2: Quick Links -->
          <div>
            <h4 class="footer-column-title">Shop</h4>
            <ul class="footer-links-list">
              <li><a href="products.php">All Products</a></li>
              <li><a href="categories.php">Collections</a></li>
              <li><a href="products.php?sort=newest">New Arrivals</a></li>
              <li><a href="wishlist.php">Wishlist</a></li>
            </ul>
          </div>

          <!-- Column 3: Customer Care -->
          <div>
            <h4 class="footer-column-title">Support</h4>
            <ul class="footer-links-list">
              <li><a href="orders.php">My Orders</a></li>
              <li><a href="cart.php">Shopping Bag</a></li>
              <li><a href="contact.php">Contact Us</a></li>
              <li><a href="account.php">My Account</a></li>
            </ul>
          </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
          <p>© <?= date('Y') ?> AVORA. All rights reserved.</p>
        </div>
      </div>
    </footer>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
  <script src="js/main.js"></script>
  <script>
    function initIcons() {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }
    }
    document.addEventListener('DOMContentLoaded', initIcons);
    window.addEventListener('load', initIcons);
    initIcons();
  </script>
</body>
</html>
