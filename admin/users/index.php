<?php
$basePath = "../";
$activeGroup = 'users';
require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . '/../../config/db.php';

$pageTitle = 'Users Management';
$pageCss = 'users.css';
$breadcrumbHtml = '<a href="../dashboard.php">Admin</a> <span>/</span> <span>Customer Accounts</span>';

$msg = "";
$msgType = "success";

// Handle Delete User Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $msg = "User account successfully deleted.";
        $msgType = "success";
    } else {
        $msg = "Failed to delete user: " . $conn->error;
        $msgType = "error";
    }
    $stmt->close();
}

// Handle Toggle Status Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    $toggleStmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $toggleStmt->bind_param("i", $toggleId);
    if ($toggleStmt->execute()) {
        $msg = "User status updated.";
        $msgType = "success";
    }
    $toggleStmt->close();
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'ALL');

$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS total_orders, (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') AS total_spent FROM users u WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "sss";
}

if ($statusFilter !== 'ALL' && in_array(strtolower($statusFilter), ['active', 'inactive'])) {
    $sql .= " AND u.status = ?";
    $params[] = strtolower($statusFilter);
    $types .= "s";
}

$sql .= " ORDER BY u.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header">
    <div class="page-title">
      <h1>Registered Customers & Accounts</h1>
      <p>Customer profiles, account activity, and lifetime purchases.</p>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-box alert-<?= $msgType ?>" style="display:block; margin-bottom: 1.25rem;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="GET" action="index.php" class="table-toolbar">
      <div class="search-input-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div style="display:flex; gap:0.5rem; align-items:center;">
        <select class="filter-select" name="status" onchange="this.form.submit()">
          <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>Status: All Accounts</option>
          <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
        </select>
        <?php if (!empty($search) || $statusFilter !== 'ALL'): ?>
          <a href="index.php" class="btn btn-secondary" style="padding:0.45rem 0.75rem;">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Customer Profile</th>
            <th>Contact Phone</th>
            <th>Registered Date</th>
            <th>Orders Placed</th>
            <th>Total Spend</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                No registered customers found matching your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
              <?php
                $uName = !empty($u['name']) ? $u['name'] : 'User #' . $u['id'];
                $uInitials = strtoupper(substr($uName, 0, 2));
              ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="user-initials"><?= htmlspecialchars($uInitials) ?></div>
                    <div>
                      <div class="user-meta-name"><?= htmlspecialchars($uName) ?></div>
                      <div style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['phone'] ?: 'Not Provided') ?></td>
                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td><strong><?= (int)$u['total_orders'] ?></strong> orders</td>
                <td><strong>₹<?= number_format((float)$u['total_spent'], 2) ?></strong></td>
                <td>
                  <span class="status-pill <?= htmlspecialchars($u['status']) ?>">
                    <?= htmlspecialchars(ucfirst($u['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="btn-action-group">
                    <a href="details.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-view">Profile</a>
                    <a href="index.php?action=toggle&id=<?= $u['id'] ?>" class="btn btn-sm btn-status-toggle" title="Toggle Active/Inactive">
                      <?= ($u['status'] === 'active') ? 'Deactivate' : 'Activate' ?>
                    </a>
                    <a href="index.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this customer account? All order relations will be removed.');">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
