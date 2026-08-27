<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

require __DIR__ . '/../config/db.php';

$message = "";

if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($email) || empty($password)) {
        $message = "Please fill in all credentials.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $admin = $res->fetch_assoc();
            $stmt->close();

            if (password_verify($password, $admin["password"]) || $password === $admin["password"]) {
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_name"] = $admin["name"];
                $_SESSION["admin_email"] = $admin["email"];

                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Invalid email or password.";
            }
        } else {
            $stmt->close();
            $message = "Admin account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - AVORA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../user/css/global.css">
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php require_once __DIR__ . '/../user/includes/logo.php'; ?>
<div class="login-wrapper">
  <div class="login-card">
    <div class="brand-section" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem;">
      <?= renderAvoraLogo('light', 'lg', '#') ?>
      <p class="brand-subtitle" style="margin-top: 0.5rem;">Enter credentials to access administrative dashboard</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-message alert-error" style="display:block;">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <!-- Dummy fields to absorb browser credential autofill on localhost -->
      <input type="text" name="fake_admin_user" style="display:none;" tabindex="-1" autocomplete="off" />
      <input type="password" name="fake_admin_pass" style="display:none;" tabindex="-1" autocomplete="new-password" />

      <div class="form-group">
        <label class="form-label" for="adminEmail">Email Address</label>
        <input type="email" id="adminEmail" name="email" class="form-control" placeholder="Enter email" autocomplete="off" value="" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="adminPassword">Password</label>
        <input type="password" id="adminPassword" name="password" class="form-control" placeholder="Enter password" autocomplete="new-password" value="" required>
      </div>

      <button type="submit" class="login-btn">
        Sign In to Dashboard
      </button>
    </form>

    <div class="card-footer-text">
      AVORA Management Portal &copy; <?= date('Y') ?>
    </div>
  </div>
</div>

</body>
</html>
