<?php
$activeNav = '';
$pageTitle = 'My Profile & Account - AVORA';
$hideNavbar = true;
$hideFooter = true;
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../config/db.php";

$userId = getCurrentUserId();

// Handle Profile Updates
$errorMsg = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        $errorMsg = "Name and Email are required.";
    } else {
        // Check duplicate email
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $chk->bind_param("si", $email, $userId);
        $chk->execute();
        $chkRes = $chk->get_result();
        if ($chkRes && $chkRes->num_rows > 0) {
            $errorMsg = "This email is already in use by another account.";
        } else {
            $up = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $up->bind_param("sssi", $name, $email, $phone, $userId);
            if ($up->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                setFlashMessage("Your profile information has been successfully updated.", "success");
                header("Location: account.php");
                exit;
            } else {
                $errorMsg = "Failed to update profile: " . $conn->error;
            }
            $up->close();
        }
        $chk->close();
    }
}

// Fetch user data
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$uRes = $userStmt->get_result();
$user = $uRes->fetch_assoc();
$userStmt->close();

// Fetch orders stats
$totalOrders = 0;
$totalSpend = 0.00;
$statStmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as spend FROM orders WHERE user_id = ?");
$statStmt->bind_param("i", $userId);
$statStmt->execute();
$sRes = $statStmt->get_result();
if ($sRes && $row = $sRes->fetch_assoc()) {
    $totalOrders = (int)$row['count'];
    $totalSpend = (float)$row['spend'];
}
$statStmt->close();

require __DIR__ . "/includes/header.php";
?>

<div class="app-container" style="padding-top: 1rem; padding-bottom: 3rem;">
  <!-- Account Portal Top Bar -->
  <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 0; margin-bottom: 1.75rem; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 1rem;">
    <?= renderAvoraLogo('light', 'md', 'index.php') ?>

    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <a href="index.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.35rem;">
        <i data-lucide="shopping-bag" style="width: 14px; height: 14px;"></i> Shop Store
      </a>
      <a href="orders.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.35rem;">
        <i data-lucide="package" style="width: 14px; height: 14px;"></i> My Orders
      </a>
      <a href="logout.php" class="btn btn-sm" style="background-color: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Logout
      </a>
    </div>
  </div>

  <!-- Breadcrumbs -->
  <div class="breadcrumbs" style="padding-top: 0;">
    <a href="index.php">Home</a>
    <span>/</span>
    <span class="active">My Account</span>
  </div>

  <div class="section-header" style="margin-bottom: 2rem;">
    <div>
      <h1 class="section-title">Hello, <?= htmlspecialchars($user['name'] ?? 'Customer') ?></h1>
      <p class="section-subtitle">Manage your personal settings, view order stats, and review your account.</p>
    </div>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="alert alert-error">
      <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
      <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
  <?php endif; ?>

  <div class="cart-layout">
    <!-- Profile Edit Form -->
    <div>
      <div class="summary-card">
        <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
          <i data-lucide="user" style="color: var(--primary-color); width: 20px; height: 20px;"></i>
          Personal Profile Details
        </h2>

        <form method="POST" action="account.php" autocomplete="off">
          <!-- Dummy fields to stop aggressive browser autofill on localhost -->
          <input type="text" name="fake_username_remember" style="display:none;" tabindex="-1" autocomplete="off" />
          <input type="password" name="fake_password_remember" style="display:none;" tabindex="-1" autocomplete="new-password" />

          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input
              type="text"
              name="name"
              class="form-input"
              required
              placeholder="Enter your full name"
              autocomplete="off"
              value="<?= htmlspecialchars($user['name'] ?? '') ?>"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input
              type="email"
              name="email"
              class="form-input"
              required
              placeholder="Enter your email"
              autocomplete="off"
              value="<?= htmlspecialchars($user['email'] ?? '') ?>"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input
              type="tel"
              name="phone"
              class="form-input"
              placeholder="e.g. 9876543210"
              autocomplete="off"
              value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
            />
          </div>

          <button type="submit" name="update_profile" value="1" class="btn btn-primary" style="margin-top: 1rem;">
              Save Profile Changes
            </button>
        </form>
      </div>
    </div>

    <!-- Account Stats & Quick Links -->
    <div>
      <div class="summary-card" style="margin-bottom: 1.5rem;">
        <h3 class="summary-title">Account Overview</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
          <div style="padding: 1.25rem; background-color: var(--bg-main); border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Orders Placed</span>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;"><?= $totalOrders ?></div>
          </div>

          <div style="padding: 1.25rem; background-color: var(--bg-main); border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Spend</span>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-color); margin-top: 0.25rem;"><?= formatPrice($totalSpend) ?></div>
          </div>
        </div>

        <a href="orders.php" class="btn btn-secondary btn-full" style="display: flex; gap: 0.5rem; justify-content: center;">
          <i data-lucide="package" style="width: 18px; height: 18px;"></i> View All My Orders
        </a>
      </div>

      <div class="summary-card">
        <h4 style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.75rem;">Need Assistance?</h4>
        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
          Have questions regarding a delivery or return? Our customer care is ready to help.
        </p>
        <a href="contact.php" class="btn btn-outline btn-full btn-sm">Contact Support</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
