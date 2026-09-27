<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
  <!-- Logo -->
  <div class="sidebar-logo">
    <img src="<?= BASE_URL ?>/assets/img/logo-dark-bg.png" alt="NUR-PUSARA Logo">
    <div class="sidebar-logo-text">
      NUR-PUSARA
      <span>SISTEM</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <div class="nav-section-label">Utama</div>

    <a href="<?= BASE_URL ?>/dashboard/index.php" class="nav-item <?= $current_dir === 'dashboard' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      <span class="nav-label">Papan Pemuka</span>
    </a>

    <div class="nav-section-label">Rekod</div>


    <a href="<?= BASE_URL ?>/modules/burial/index.php" class="nav-item <?= $current_dir === 'burial' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/>
        <path d="M8 6a4 4 0 0 1 8 0v2H8V6z"/>
      </svg>
      <span class="nav-label">Rekod Pengebumian</span>
    </a>

    <div class="nav-section-label">Tanah Perkuburan</div>

    <a href="<?= BASE_URL ?>/modules/lot/index.php" class="nav-item <?= $current_dir === 'lot' && basename($_SERVER['PHP_SELF']) !== 'map.php' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>
      </svg>
      <span class="nav-label">Pengurusan Lot</span>
    </a>

    <a href="<?= BASE_URL ?>/modules/lot/map.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'map.php' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>
      </svg>
      <span class="nav-label">Peta Interaktif</span>
    </a>

    <div class="nav-section-label">Laporan</div>

    <a href="<?= BASE_URL ?>/modules/reports/index.php" class="nav-item <?= $current_dir === 'reports' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/>
      </svg>
      <span class="nav-label">Laporan</span>
    </a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="nav-section-label">Pentadbir</div>

    <a href="<?= BASE_URL ?>/modules/users/index.php" class="nav-item <?= $current_dir === 'users' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
        <line x1="12" y1="12" x2="12" y2="22"/><line x1="9" y1="19" x2="15" y2="19"/>
      </svg>
      <span class="nav-label">Pengurusan Pengguna</span>
    </a>
    <?php endif; ?>

  </nav>

  <!-- Bottom -->
  <div class="sidebar-bottom">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item" style="color: #f87171;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      <span class="nav-label">Log Keluar</span>
    </a>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
