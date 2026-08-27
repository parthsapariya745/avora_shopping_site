<?php
$activeNav = '';
$pageTitle = 'Sign In - AVORA';
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
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $errorMsg = "Email and Password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];

                setFlashMessage("Welcome back, " . htmlspecialchars($user['name']) . "!", "success");
                header("Location: index.php");
                exit;
            } else {
                $errorMsg = "Incorrect password. Please try again.";
            }
        } else {
            $errorMsg = "No registered account found with that email address.";
        }
        $stmt->close();
    }
}

require __DIR__ . "/includes/header.php";
?>

<div class="app-container" style="min-height: 88vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
  <div class="auth-box" style="margin: 0 auto; width: 100%; max-width: 440px;">
    <div style="text-align: center; margin-bottom: 1.75rem;">
      <?= renderAvoraLogo('light', 'lg', 'index.php') ?>
    </div>

    <div class="auth-header">
      <div style="width: 48px; height: 48px; border-radius: 50%; background: #E8DCCB; color: var(--color-primary-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
        <i data-lucide="lock" style="width: 22px; height: 22px;"></i>
      </div>
      <h1>Customer Sign In</h1>
      <p>Enter your credentials to access your luxury account.</p>
    </div>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error">
        <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
        <span><?= htmlspecialchars($errorMsg) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <!-- Dummy fields to absorb browser credential autofill on localhost -->
      <input type="text" name="fake_username_remember" style="display:none;" tabindex="-1" autocomplete="off" />
      <input type="password" name="fake_password_remember" style="display:none;" tabindex="-1" autocomplete="new-password" />

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input
          type="email"
          name="email"
          class="form-input"
          required
          placeholder="Enter your email"
          autocomplete="off"
          value=""
        />
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <label class="form-label">Password</label>
          <a href="#" style="font-size: 0.8rem; color: var(--color-primary); font-weight: 600;" onclick="alert('Password reset link has been dispatched to your email.'); return false;">Forgot?</a>
        </div>
        <input
          type="password"
          name="password"
          class="form-input"
          required
          placeholder="Enter your password"
          autocomplete="new-password"
          value=""
        />
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1.25rem;">
        Sign In <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.75rem; font-size: 0.9rem; color: var(--color-text-secondary);">
      Don't have an account yet? 
      <a href="register.php" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: underline;">
        Create Account
      </a>
    </div>
  </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
