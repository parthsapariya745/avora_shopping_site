<?php
require_once __DIR__ . "/session.php";
$activeNav = $activeNav ?? 'home';
$hideNavbar = $hideNavbar ?? false;
$hideFooter = $hideFooter ?? false;
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
$flash = getFlashMessage();
$cssVersion = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'AVORA - Luxury Minimalist Collection') ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <link rel="stylesheet" href="css/variables.css?v=<?= $cssVersion ?>" />
  <link rel="stylesheet" href="css/global.css?v=<?= $cssVersion ?>" />
  <link rel="stylesheet" href="css/navbar.css?v=<?= $cssVersion ?>" />
  <link rel="stylesheet" href="css/footer.css?v=<?= $cssVersion ?>" />
  <link rel="stylesheet" href="css/productCard.css?v=<?= $cssVersion ?>" />
  <link rel="stylesheet" href="css/pages.css?v=<?= $cssVersion ?>" />
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
  <div class="app-wrapper">
    <!-- Sticky Floating Toast Notification Container (Fixed overlay - won't push page sections down) -->
    <div class="toast-container" id="toastContainer">
      <?php if ($flash): ?>
        <div class="toast toast-<?= htmlspecialchars($flash['type']) ?>" id="flashToast">
          <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
          <span><?= htmlspecialchars($flash['text']) ?></span>
          <button onclick="this.parentElement.remove()" class="toast-close-btn" aria-label="Close">&times;</button>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$hideNavbar): ?>
      <?php require __DIR__ . "/navbar.php"; ?>
    <?php endif; ?>

    <main class="main-content"<?= $hideNavbar ? ' style="padding-top: 1.5rem;"' : '' ?>>
