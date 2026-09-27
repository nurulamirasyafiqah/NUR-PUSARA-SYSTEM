<?php
require_once '../../config/db.php';
// Public page — no requireLogin()

$results    = [];
$searched   = false;
$cemeteries = [];
$query      = trim($_GET['q']       ?? '');
$year       = trim($_GET['year']    ?? '');
$cemetery   = trim($_GET['cemetery'] ?? '');

// Load cemetery list for filter dropdown
$cem_rows = mysqli_query($conn, "SELECT id_tapak, nama, daerah FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($row = mysqli_fetch_assoc($cem_rows)) {
    $cemeteries[] = $row;
}

// Run search if any filter is provided
if ($query !== '' || $year !== '' || $cemetery !== '') {
    $searched = true;

    $where   = ['b.id_pengebumian IS NOT NULL'];
    $params  = [];
    $types   = '';

    if ($query !== '') {
        $where[]  = 'd.nama LIKE ?';
        $params[] = '%' . $query . '%';
        $types   .= 's';
    }
    if ($year !== '') {
        $where[]  = 'YEAR(d.tarikh_mati) = ?';
        $params[] = $year;
        $types   .= 'i';
    }
    if ($cemetery !== '') {
        $where[]  = 'c.id_tapak = ?';
        $params[] = $cemetery;
        $types   .= 'i';
    }

    $sql = "
        SELECT
            d.nama,
            d.tarikh_lahir,
            d.tarikh_mati,
            d.jantina,
            l.kod_lot,
            l.fasa,
            l.blok,
            l.no_lot,
            l.latitud,
            l.longitud,
            c.nama          AS nama_tapak,
            c.daerah,
            c.alamat       AS cemetery_address,
            b.tarikh_kebumi
        FROM tbl_simati d
        JOIN tbl_pengebumian b ON d.id_simati = b.id_simati
        JOIN tbl_lot l        ON b.id_lot    = l.id_lot
        JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.tarikh_kebumi DESC
        LIMIT 50
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate age at death
        if ($row['tarikh_lahir'] && $row['tarikh_mati']) {
            $dob  = new DateTime($row['tarikh_lahir']);
            $dod  = new DateTime($row['tarikh_mati']);
            $row['age_at_death'] = $dob->diff($dod)->y;
        } else {
            $row['age_at_death'] = null;
        }
        $results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ms" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cari Maklumat Arwah — NUR-PUSARA</title>
  <link rel="icon" type="image/png" href="../../assets/img/logo-green.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --green-1: #20331B; --green-2: #2b4522; --green-3: #3a5c2e;
      --gold: #C3CA92; --gold-2: #a8b074;
      --text-main: #ffffff; --text-muted: rgba(255,255,255,0.55);
      --card-bg: rgba(255,255,255,0.07); --card-border: rgba(255,255,255,0.12);
      --radius: 14px; --shadow: 0 8px 32px rgba(0,0,0,0.35);
    }
    html, body { min-height: 100%; }
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: var(--text-main);
      position: relative;
    }
    html[data-theme="light"] body { background: linear-gradient(160deg, #20331B 0%, #2b4522 50%, #1a2d14 100%); }
    html[data-theme="dark"]  body { background: linear-gradient(160deg, #0a1208 0%, #0d180a 50%, #080f06 100%); }
    html[data-theme="dark"] .result-card,
    html[data-theme="dark"] .empty-card { background: rgba(0,0,0,0.4); border-color: rgba(255,255,255,0.08); }
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Cg fill='none' stroke='rgba(195,202,146,0.05)' stroke-width='0.8'%3E%3Cpolygon points='40,4 52,16 64,16 72,28 64,40 72,52 64,64 52,64 40,76 28,64 16,64 8,52 16,40 8,28 16,16 28,16'/%3E%3C/g%3E%3C/svg%3E");
      pointer-events: none; z-index: 0;
    }

    /* Navbar */
    .navbar {
      width: 100%; position: relative; z-index: 2;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1rem 2rem;
      background: rgba(0,0,0,0.18); backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(195,202,146,0.1);
      flex-shrink: 0;
    }
    .navbar-brand { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: #fff; }
    .navbar-brand img { width: 30px; }
    .navbar-brand span { font-family: 'Cormorant Garamond', serif; font-size: 0.95rem; font-weight: 700; letter-spacing: 0.05em; }
    .navbar-right { display: flex; align-items: center; gap: 0.6rem; }
    .btn-back {
      display: flex; align-items: center; gap: 0.35rem;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
      border-radius: 50px; padding: 0.4rem 0.9rem;
      color: rgba(255,255,255,0.7); font-family: 'Inter', sans-serif;
      font-size: 0.75rem; text-decoration: none; transition: background 0.2s;
    }
    .btn-back:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .theme-btn {
      width: 34px; height: 34px; border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.18);
      background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.7);
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.2s;
    }
    .theme-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }

    /* Main content */
    .main { flex: 1; width: 100%; max-width: 680px; padding: 2.5rem 1.5rem 0; position: relative; z-index: 1; }

    /* Hero text */
    .hero-text { text-align: center; margin-bottom: 2rem; }
    .hero-text h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.9rem, 5vw, 2.8rem);
      font-weight: 700; margin-bottom: 0.4rem;
    }
    .hero-text p { font-size: 0.85rem; color: var(--text-muted); }

    /* Search panel */
    .search-panel {
      background: var(--card-bg); border: 1.5px solid var(--card-border);
      border-radius: var(--radius); padding: 1.5rem;
      backdrop-filter: blur(12px); margin-bottom: 2rem;
    }

    /* Main search bar */
    .main-search {
      display: flex; gap: 0.5rem;
      background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15);
      border-radius: 50px; padding: 0.35rem 0.35rem 0.35rem 1.2rem;
      margin-bottom: 1rem; transition: border-color 0.2s;
    }
    .main-search:focus-within { border-color: var(--gold); }
    .main-search input {
      flex: 1; background: none; border: none; outline: none;
      color: #fff; font-family: 'Inter', sans-serif; font-size: 0.9rem;
    }
    .main-search input::placeholder { color: rgba(255,255,255,0.35); }
    .main-search button {
      background: var(--gold); color: var(--green-1); border: none;
      border-radius: 50px; padding: 0.55rem 1.3rem;
      font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; gap: 0.35rem;
      white-space: nowrap; transition: background 0.2s;
    }
    .main-search button:hover { background: #d4db9e; }

    /* Filter row */
    .filter-label {
      font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.09em; color: rgba(255,255,255,0.4); margin-bottom: 0.55rem;
    }
    .filter-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .filter-field { flex: 1; min-width: 160px; }
    .filter-field select,
    .filter-field input[type="number"] {
      width: 100%; background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
      padding: 0.55rem 0.8rem; color: #fff;
      font-family: 'Inter', sans-serif; font-size: 0.82rem;
      outline: none; appearance: none; transition: border-color 0.2s;
    }
    .filter-field select option { background: #20331B; color: #fff; }
    .filter-field select:focus,
    .filter-field input[type="number"]:focus { border-color: var(--gold); }
    .filter-field input[type="number"]::placeholder { color: rgba(255,255,255,0.35); }

    /* Result count */
    .result-count { font-size: 0.78rem; color: var(--gold); margin-bottom: 0.75rem; }

    /* Result card */
    .result-card {
      background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: var(--radius); padding: 1.3rem;
      backdrop-filter: blur(12px); box-shadow: var(--shadow);
      margin-bottom: 1.1rem;
    }

    /* Card top: name + lot badge */
    .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.9rem; }
    .card-name { font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-weight: 700; line-height: 1.3; }
    .card-cemetery { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem; display: flex; align-items: center; gap: 0.3rem; }

    .lot-badge {
      background: var(--gold); color: var(--green-1);
      font-family: 'Cormorant Garamond', serif;
      font-size: 0.95rem; font-weight: 700;
      padding: 0.25rem 0.7rem; border-radius: 8px;
      white-space: nowrap; text-align: center; line-height: 1.4;
    }
    .lot-fasa { font-size: 0.62rem; color: var(--green-2); text-align: center; margin-top: 0.15rem; font-weight: 600; }

    /* Info grid */
    .info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 0.4rem 1.5rem; margin-bottom: 0.9rem;
    }
    .info-item { }
    .info-label { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: rgba(255,255,255,0.38); margin-bottom: 0.15rem; }
    .info-value { font-size: 0.82rem; color: rgba(255,255,255,0.85); }
    .info-value.accent { color: var(--gold); font-weight: 600; }

    /* Divider */
    .divider { height: 1px; background: var(--card-border); margin: 0.85rem 0; }

    /* Map */
    .map-label { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.3rem; }
    .map-frame { width: 100%; height: 210px; border: none; border-radius: 10px; display: block; }
    .map-open {
      display: inline-flex; align-items: center; gap: 0.35rem;
      margin-top: 0.6rem; font-size: 0.75rem; font-weight: 600;
      color: var(--gold); text-decoration: none; transition: opacity 0.2s;
    }
    .map-open:hover { opacity: 0.75; }

    /* Empty state */
    .empty-card {
      background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: var(--radius); padding: 2.5rem 1.5rem;
      text-align: center; backdrop-filter: blur(12px);
    }
    .empty-card .icon { font-size: 2rem; margin-bottom: 0.6rem; }
    .empty-card h3 { font-size: 1rem; margin-bottom: 0.35rem; }
    .empty-card p { font-size: 0.82rem; color: var(--text-muted); }

    /* Footer */
    .footer {
      width: 100%; padding: 1.2rem 1rem; font-size: 0.7rem;
      color: rgba(255,255,255,0.2); text-align: center;
      margin-top: auto; position: relative; z-index: 1;
    }

    @media (max-width: 600px) {
      .info-grid { grid-template-columns: 1fr; }
      .filter-row { flex-direction: column; }
      .navbar { padding: 0.9rem 1.2rem; }
    }

    /* Advanced search toggle */
    .adv-toggle {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: none;
      border: none;
      color: rgba(255,255,255,0.5);
      font-family: 'Inter', sans-serif;
      font-size: 0.75rem;
      font-weight: 500;
      cursor: pointer;
      padding: 0.4rem 0;
      margin-top: 0.5rem;
      transition: color 0.2s;
      position: relative;
    }
    .adv-toggle:hover { color: var(--gold); }
    .adv-active-dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--gold);
      display: inline-blok;
    }

    .adv-filters {
      margin-top: 0.9rem;
      padding-top: 0.9rem;
      border-top: 1px solid rgba(255,255,255,0.1);
      animation: fadeIn 0.2s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }

  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <a href="../../index.php" class="navbar-brand">
      <img src="../../assets/img/logo-dark-bg.png" alt="NUR-PUSARA" onerror="this.style.display='none'">
      <span>NUR-PUSARA</span>
    </a>
    <div class="navbar-right">
      <button class="theme-btn" id="themeBtn" aria-label="Toggle tema">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <a href="../../index.php" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
      </a>
    </div>
  </nav>

  <div class="main">

    <!-- Hero -->
    <div class="hero-text">
      <h1>Cari Maklumat Arwah</h1>
      <p>Cari lokasi kubur berdasarkan nama, tahun kematian atau tapak perkuburan</p>
    </div>

    <!-- Search panel -->
    <form method="GET" action="" class="search-panel">

      <!-- Main name search -->
      <div class="main-search">
        <input
          type="text"
          name="q"
          placeholder="Masukkan nama arwah..."
          value="<?= htmlspecialchars($query) ?>"
          autocomplete="off"
          autofocus
        >
        <button type="submit">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Cari
        </button>
      </div>

      <!-- Advanced search toggle -->
      <button type="button" class="adv-toggle" id="advToggle" onclick="toggleAdv()">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        Carian Lanjutan <?= ($year || $cemetery) ? '<span class="adv-active-dot"></span>' : '' ?>
        <svg id="advChevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </button>

      <!-- Advanced filters — hidden by default, shown if has value or toggled -->
      <div class="adv-filters" id="advFilters" style="display:<?= ($year || $cemetery) ? 'block' : 'none' ?>;">
        <div class="filter-label">Tapis mengikut</div>
        <div class="filter-row">
          <div class="filter-field">
            <select name="cemetery">
              <option value="">— Semua tapak perkuburan —</option>
              <?php foreach ($cemeteries as $cem): ?>
                <option value="<?= $cem['id_tapak'] ?>" <?= $cemetery == $cem['id_tapak'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cem['nama']) ?> (<?= htmlspecialchars($cem['daerah']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-field">
            <input type="number" name="year" placeholder="Tahun kematian (cth: 2026)"
              value="<?= htmlspecialchars($year) ?>" min="1900" max="<?= date('Y') ?>">
          </div>
        </div>
      </div>

    </form>

    <!-- Results -->
    <?php if ($searched): ?>
      <?php if (count($results) > 0): ?>
        <div class="result-count">
          <?= count($results) ?> rekod dijumpai
          <?php if ($query) echo ' untuk "<strong>' . htmlspecialchars($query) . '</strong>"'; ?>
          <?php if ($year) echo ' &mdash; Tahun ' . htmlspecialchars($year); ?>
        </div>

        <?php $rindex = 0; foreach ($results as $r): ?>
        <div class="result-card">

          <!-- Name + lot badge -->
          <div class="card-top">
            <div>
              <div class="card-name"><?= htmlspecialchars($r['nama']) ?></div>
              <div class="card-cemetery">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <?= htmlspecialchars($r['nama_tapak']) ?>, <?= htmlspecialchars($r['daerah']) ?>
              </div>
            </div>
            <div>
              <div class="lot-badge"><?= htmlspecialchars($r['blok'] . '-' . str_pad($r['no_lot'], 4, '0', STR_PAD_LEFT)) ?></div>
              <div class="lot-fasa">Fasa <?= htmlspecialchars($r['fasa']) ?> — Blok <?= htmlspecialchars($r['blok']) ?></div>
            </div>
          </div>

          <!-- Info grid — NO IC NUMBER (privacy) -->
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Tarikh Lahir</div>
              <div class="info-value"><?= $r['tarikh_lahir'] ? date('d M Y', strtotime($r['tarikh_lahir'])) : '—' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Tarikh Kematian</div>
              <div class="info-value"><?= date('d M Y', strtotime($r['tarikh_mati'])) ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Umur Semasa Meninggal</div>
              <div class="info-value accent"><?= $r['age_at_death'] !== null ? $r['age_at_death'] . ' tahun' : '—' ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Jantina</div>
              <div class="info-value"><?= ucfirst($r['jantina']) ?></div>
            </div>
          </div>

          <!-- Interactive satellite map with pin marker (same style as admin peta interaktif) -->
          <?php if (!empty($r['latitud']) && !empty($r['longitud'])): ?>
            <?php
              $lat   = $r['latitud'];
              $lng   = $r['longitud'];
              $gmaps = "https://www.google.com/maps?q={$lat},{$lng}&z=21&hl=ms";
              $mapId = 'resultMap_' . $rindex;
            ?>
            <div class="divider"></div>
            <div class="map-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
              Lokasi Lot — <?= htmlspecialchars($r['alamat_tapak'] ?? $r['nama_tapak']) ?>
            </div>
            <div class="map-frame result-map" id="<?= $mapId ?>" data-lat="<?= $lat ?>" data-lng="<?= $lng ?>" data-kod="<?= htmlspecialchars($r['blok'] . '-' . str_pad($r['no_lot'], 4, '0', STR_PAD_LEFT)) ?>"></div>
            <a href="<?= $gmaps ?>" target="_blank" class="map-open">
              🗺️ Buka dalam Google Maps
            </a>
          <?php endif; ?>

        </div>
        <?php $rindex++; ?>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-card">
          <div class="icon">🔍</div>
          <h3>Tiada rekod dijumpai</h3>
          <p>Cuba tukar kata carian, tahun atau pilih tapak perkuburan yang berbeza</p>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="empty-card">
        <div class="icon">⬆️</div>
        <h3>Masukkan nama atau pilih tapak perkuburan</h3>
        <p>Anda boleh cari menggunakan nama sahaja, atau gunakan penapis untuk hasil lebih tepat</p>
      </div>
    <?php endif; ?>

  </div>

  <div class="footer">
    &copy; <?= date('Y') ?> SISTEM NUR-PUSARA &mdash; Jabatan Agama Islam Selangor
  </div>

<script>
  var MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SUN  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('nur-pusara-theme', t);
    var btn = document.getElementById('themeBtn');
    if (btn) btn.innerHTML = t === 'light' ? MOON : SUN;
  }
  applyTheme(localStorage.getItem('nur-pusara-theme') || 'light');
  document.getElementById('themeBtn').addEventListener('click', function () {
    applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  });

  /* Advanced search toggle */
  function toggleAdv() {
    var panel   = document.getElementById('advFilters');
    var chevron = document.getElementById('advChevron');
    var isOpen  = panel.style.display !== 'none';
    panel.style.display  = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
    chevron.style.transition = 'transform 0.2s';
  }

</script>

<!-- Leaflet — interactive satellite map per result (replaces plain Google embed) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var CRESCENT_SVG = '<svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 100 20 8 8 0 010-20z" opacity="0.9"/></svg>';
  var pinIcon = L.divIcon({
    className: '',
    html: '<div style="width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;' +
          'background:#6b4a3a;border:2.5px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);">' +
          '<span style="width:13px;height:13px;display:block;">' + CRESCENT_SVG + '</span></div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
  });

  document.querySelectorAll('.result-map').forEach(function(el) {
    var lat = parseFloat(el.getAttribute('data-lat'));
    var lng = parseFloat(el.getAttribute('data-lng'));
    var kod = el.getAttribute('data-kod');
    var m = L.map(el, { zoomControl: true, attributionControl: false }).setView([lat, lng], 19);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      maxZoom: 22, maxNativeZoom: 19
    }).addTo(m);
    L.marker([lat, lng], { icon: pinIcon }).addTo(m).bindPopup('<b>' + kod + '</b>');
  });
</script>
</body>
</html>
