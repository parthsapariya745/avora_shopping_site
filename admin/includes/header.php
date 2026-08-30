<?php
$base = isset($basePath) ? $basePath : "";
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminInitials = strtoupper(substr($adminName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin Portal') ?> - AVORA</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>../user/css/global.css">
  
  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="<?= $base ?>css/<?= htmlspecialchars($pageCss) ?>">
  <?php endif; ?>

  <style>
    /* Admin Portal Full-Width Layout Overrides */
    body .app-container {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    body .page-content {
      max-width: 100% !important;
      width: 100% !important;
      padding: 1.75rem 2rem !important;
      margin: 0 !important;
      box-sizing: border-box !important;
    }

    .tables-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.5rem;
    }

    @media (max-width: 1024px) {
      .tables-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<div class="app-container">
  <?php require_once __DIR__ . "/sidebar.php"; ?>

  <div class="main-wrapper">
    <!-- Top Bar -->
    <header class="top-header">
      <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <div class="breadcrumbs">
          <?= $breadcrumbHtml ?? '<span>Dashboard</span>' ?>
        </div>
      </div>

      <div class="header-right" style="display:flex; align-items:center; gap:0.75rem;">
        <div class="user-avatar" title="<?= htmlspecialchars($adminName) ?>"><?= htmlspecialchars($adminInitials) ?></div>
        <div style="font-size:0.85rem; font-weight:600; color:#0f172a;" class="admin-name-text">
          <?= htmlspecialchars($adminName) ?>
        </div>
      </div>
    </header>
