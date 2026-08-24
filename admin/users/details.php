<?php
$basePath = "../";
$activeGroup = 'users';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'User Details';
$pageCss = 'user-details.css';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    header("Location: index.php");
    exit;
}

$msg = "";
$msgType = "success";

// Handle User Edit POST
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $status = in_array($_POST["status"] ?? "", ["active", "inactive"]) ? $_POST["status"] : "active";

    if (empty($name) || empty($email)) {
        $msg = "Name and email cannot be empty.";
        $msgType = "error";
    } else {
        // Check duplicate email
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $msg = "Another user is already registered with that email address.";
            $msgType = "error";
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
            $updateStmt->bind_param("ssssi", $name, $email, $phone, $status, $userId);
            if ($updateStmt->execute()) {
                $msg = "User profile updated successfully.";
                $msgType = "success";
            } else {
                $msg = "Failed to update profile: " . $conn->error;
                $msgType = "error";
            }
            $updateStmt->close();
        }
        $checkStmt->close();
    }
}

// Fetch User Info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}
$user = $res->fetch_assoc();
$stmt->close();

$breadcrumbHtml = '<a href="index.php">Users</a> <span>/</span> <span>' . htmlspecialchars($user['name'] ?: 'User #' . $user['id']) . '</span>';

// Fetch User Orders
$orders = [];
$totalSpent = 0.00;
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$orderStmt->bind_param("i", $userId);
$orderStmt->execute();
$orderRes = $orderStmt->get_result();
if ($orderRes) {
    while ($o = $orderRes->fetch_assoc()) {
        $orders[] = $o;
        if ($o['status'] !== 'cancelled') {
            $totalSpent += (float)$o['total_amount'];
        }
    }
}
$orderStmt->close();

$userInitials = strtoupper(substr($user['name'] ?: 'U', 0, 2));

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Customer Profile & History</h1>
      <p>Account details, contact preferences, and full transaction logs.</p>
    </div>
    <div>
      <a href="index.php" class="btn btn-secondary">&larr; Back to Users</a>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-box alert-<?= $msgType ?>" style="display: block;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="profile-grid">
    <!-- Left Column: Summary Card -->
    <div>
      <div class="card">
        <div class="card-body">
          <div class="profile-summary">
            <div class="large-avatar"><?= htmlspecialchars($userInitials) ?></div>
            <div class="user-full-name"><?= htmlspecialchars($user['name'] ?: 'Customer #' . $user['id']) ?></div>
            <div class="user-email-text"><?= htmlspecialchars($user['email']) ?></div>
            <span class="status-pill <?= htmlspecialchars($user['status']) ?>">
              <?= htmlspecialchars(ucfirst($user['status'])) ?>
            </span>
          </div>

          <div class="info-list">
            <div class="info-item">
              <span class="info-key">User ID:</span>
              <span class="info-val">#<?= $user['id'] ?></span>
            </div>
            <div class="info-item">
              <span class="info-key">Phone:</span>
              <span class="info-val"><?= htmlspecialchars($user['phone'] ?: 'None') ?></span>
            </div>
            <div class="info-item">
              <span class="info-key">Joined:</span>
              <span class="info-val"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="info-item">
              <span class="info-key">Total Orders:</span>
              <span class="info-val"><?= count($orders) ?></span>
            </div>
            <div class="info-item">
              <span class="info-key">Lifetime Spend:</span>
              <span class="info-val" style="color:#2563eb;">₹<?= number_format($totalSpent, 2) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Edit Profile & Order History -->
    <div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">Edit Customer Profile</div>
        </div>
        <div class="card-body">
          <form method="POST" action="details.php?id=<?= $user['id'] ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="userName">Full Name</label>
                <input type="text" id="userName" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label" for="userEmail">Email Address</label>
                <input type="email" id="userEmail" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="userPhone">Phone Number</label>
                <input type="text" id="userPhone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
              </div>

              <div class="form-group">
                <label class="form-label" for="userStatus">Account Status</label>
                <select id="userStatus" name="status" class="form-control">
                  <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Active Account</option>
                  <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Suspended)</option>
                </select>
              </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:1rem;">
              <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </div>
          </form>
        </div>
      </div>

      <!-- User Orders History Table -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Order History (<?= count($orders) ?>)</div>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order Ref</th>
                <th>Placed Date</th>
                <th>Total Paid</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">
                    No orders placed by this user yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($orders as $ord): ?>
                  <tr>
                    <td><strong>#ORD-<?= $ord['id'] ?></strong></td>
                    <td><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                    <td><span style="font-weight:600; color:#0f172a;">₹<?= number_format($ord['total_amount'], 2) ?></span></td>
                    <td>
                      <span class="order-badge <?= htmlspecialchars(strtolower($ord['status'])) ?>">
                        <?= htmlspecialchars(ucfirst($ord['status'])) ?>
                      </span>
                    </td>
                    <td>
                      <a href="../orders/details.php?id=<?= $ord['id'] ?>" class="btn btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.75rem;">View Order</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
