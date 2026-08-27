<?php
$activeNav = '';
$pageTitle = 'Create Account - AVORA';
$hideNavbar = true;
$hideFooter = true;
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/../config/db.php";

if (isUserLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errorMsg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $errorMsg = "Name, Email, and Password are required.";
    } elseif (strlen($password) < 6) {
        $errorMsg = "Password must be at least 6 characters long.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $errorMsg = "An account with that email already exists. Please sign in.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name, email, password, phone, status) VALUES (?, ?, ?, ?, 'active')");
            $ins->bind_param("ssss", $name, $email, $hashed, $phone);

            if ($ins->execute()) {
                setFlashMessage("Account created successfully! Please sign in with your credentials.", "success");
                header("Location: login.php");
                exit;
            } else {
                $errorMsg = "Registration failed: " . $conn->error;
            }
            $ins->close();
        }
        $stmt->close();
    }
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container" style="min-height: 88vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
  <div class="auth-box" style="margin: 0 auto; width: 100%; max-width: 460px;">
    <div style="text-align: center; margin-bottom: 1.75rem;">
      <?= renderAvoraLogo('light', 'lg', 'index.php') ?>
    </div>

    <div class="auth-header">
      <div style="width: 48px; height: 48px; border-radius: 50%; background: #E8DCCB; color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
        <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
      </div>
      <h1>Create an Account</h1>
      <p>Join AVORA for personalized orders and exclusive collections.</p>
    </div>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error">
        <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
        <span><?= htmlspecialchars($errorMsg) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php" autocomplete="off">
      <!-- Dummy fields to absorb browser credential autofill on localhost -->
      <input type="text" name="fake_username_remember" style="display:none;" tabindex="-1" autocomplete="off" />
      <input type="password" name="fake_password_remember" style="display:none;" tabindex="-1" autocomplete="new-password" />

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input
          type="text"
          name="name"
          class="form-input"
          required
          placeholder="e.g. Rahul Sharma"
          autocomplete="off"
          value=""
        />
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input
          type="email"
          name="email"
          class="form-input"
          required
          placeholder="e.g. rahul@example.com"
          autocomplete="off"
          value=""
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
          value=""
        />
      </div>

      <div class="form-group">
        <label class="form-label">Password * (min 6 characters)</label>
        <input
          type="password"
          name="password"
          class="form-input"
          required
          placeholder="Create a strong password"
          autocomplete="new-password"
          value=""
        />
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.25rem;">
        Register Account <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--color-text-secondary);">
      Already have an account? 
      <a href="login.php" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: underline;">
        Sign In
      </a>
    </div>
  </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
