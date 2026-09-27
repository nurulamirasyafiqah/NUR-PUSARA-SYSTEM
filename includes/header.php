<?php
$page_title = $page_title ?? 'SISTEM NUR-PUSARA';
$topbar_title = $topbar_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="ms" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> — SISTEM NUR-PUSARA</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo-green.png">
</head>
<body>

<div class="app-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
          <span></span><span></span><span></span>
        </button>
        <h1 class="topbar-title"><?= htmlspecialchars($topbar_title) ?></h1>
      </div>

      <div class="topbar-right">
        <button class="theme-toggle" aria-label="Toggle theme">
          <!-- Icon injected by JS -->
        </button>
        <div class="user-badge">
          <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['nama_penuh'] ?? 'A', 0, 1)) ?>
          </div>
          <?= htmlspecialchars($_SESSION['nama_penuh'] ?? 'Admin') ?>
        </div>
      </div>
    </header>

    <!-- Page Content starts here -->
    <main class="page-content">
