<?php
$basePath = "../";
$activeGroup = 'inquiries';
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mailer.php';

$pageTitle = 'Contact Inquiries';
$pageCss = 'dashboard.css';
$breadcrumbHtml = '<a href="../dashboard.php">Dashboard</a> <span>/</span> <span>Inquiries</span>';

// Create table if not exists
saveInquiryToDatabase('System Test', 'test@avora.com', 'Init Check', 'System initialization check.');
// Delete test row if it was just created
$conn->query("DELETE FROM inquiries WHERE email = 'test@avora.com' AND name = 'System Test'");

// Handle Delete or Status Change
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);

    if ($inquiryId > 0) {
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_flash'] = ['type' => 'success', 'text' => 'Inquiry deleted successfully.'];
        } elseif ($action === 'mark_read') {
            $stmt = $conn->prepare("UPDATE inquiries SET status = 'read' WHERE id = ?");
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_flash'] = ['type' => 'success', 'text' => 'Inquiry marked as read.'];
        }
    }
    header("Location: index.php");
    exit;
}

// Fetch inquiries
$inquiries = [];
$res = $conn->query("SELECT * FROM inquiries ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $inquiries[] = $row;
    }
}

$mailerConfig = getMailerConfig();

require_once __DIR__ . "/../includes/header.php";
?>

<main class="page-content">
  <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div class="page-title">
      <h1>Contact Form Inquiries</h1>
      <p>All inquiries submitted via website contact form are forwarded to <strong><?= htmlspecialchars($mailerConfig['receiver']) ?></strong></p>
    </div>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:0.6rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
      <span style="width:8px; height:8px; background:#16a34a; border-radius:50%; display:inline-block;"></span>
      SMTP Direct Receiver: <?= htmlspecialchars($mailerConfig['receiver']) ?>
    </div>
  </div>

  <?php if (isset($_SESSION['admin_flash'])): ?>
    <div style="padding:0.75rem 1rem; margin-bottom:1rem; border-radius:6px; background:#dcfce7; color:#166534; font-size:0.85rem; font-weight:600;">
      <?= htmlspecialchars($_SESSION['admin_flash']['text']) ?>
    </div>
    <?php unset($_SESSION['admin_flash']); ?>
  <?php endif; ?>

  <div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <div class="card-title">Inquiry Messages Log (<?= count($inquiries) ?>)</div>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Date & Time</th>
            <th>Sender Name</th>
            <th>Sender Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($inquiries)): ?>
            <tr>
              <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                No inquiries submitted yet. When users submit the contact form, their messages will appear here and deliver to <strong><?= htmlspecialchars($mailerConfig['receiver']) ?></strong>.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($inquiries as $inq): ?>
              <tr style="<?= $inq['status'] === 'new' ? 'background-color: #f8fafc; font-weight: 500;' : '' ?>">
                <td>#<?= $inq['id'] ?></td>
                <td style="white-space:nowrap; font-size:0.8rem; color:#64748b;"><?= date('M d, Y h:i A', strtotime($inq['created_at'])) ?></td>
                <td><strong><?= htmlspecialchars($inq['name']) ?></strong></td>
                <td><a href="mailto:<?= htmlspecialchars($inq['email']) ?>" style="color:#2563eb; text-decoration:none;"><?= htmlspecialchars($inq['email']) ?></a></td>
                <td><span class="badge" style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:0.75rem;"><?= htmlspecialchars($inq['subject'] ?: 'General') ?></span></td>
                <td style="max-width:300px; font-size:0.85rem; color:#334155; line-height:1.4;">
                  <?= nl2br(htmlspecialchars($inq['message'])) ?>
                </td>
                <td>
                  <span class="status-pill status-<?= $inq['status'] === 'new' ? 'pending' : 'confirmed' ?>">
                    <?= htmlspecialchars(ucfirst($inq['status'])) ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex; gap:0.4rem;">
                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject'] ?: 'Inquiry Reply') ?>" style="padding:4px 8px; background:#0f172a; color:#fff; border-radius:4px; font-size:0.75rem; text-decoration:none; display:inline-block;">Reply</a>
                    <?php if ($inq['status'] === 'new'): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                        <input type="hidden" name="action" value="mark_read">
                        <button type="submit" style="padding:4px 8px; background:#e2e8f0; border:none; color:#334155; border-radius:4px; font-size:0.75rem; cursor:pointer;">Read</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this inquiry?');">
                      <input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" style="padding:4px 8px; background:#fee2e2; border:none; color:#dc2626; border-radius:4px; font-size:0.75rem; cursor:pointer;">Delete</button>
                    </form>
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
