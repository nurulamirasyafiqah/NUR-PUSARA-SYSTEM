<?php
require_once '../config/db.php';
requireLogin();

$page_title = 'Papan Pemuka';
$topbar_title = 'Papan Pemuka';

// Fetch stats
$total_burials   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_pengebumian"))[0] ?? 0;
$jumlah_lot      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_lot"))[0] ?? 0;
$total_deceased  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_simati"))[0] ?? 0;
$penyelenggaraan = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_lot WHERE status='penyelenggaraan'"))[0] ?? 0;

// Recent burials
$recent = mysqli_query($conn, "
    SELECT b.id_pengebumian, d.nama AS nama_simati, b.tarikh_kebumi,
           l.kod_lot, l.blok, l.no_lot, c.nama AS nama_tapak
    FROM tbl_pengebumian b
    JOIN tbl_simati d  ON b.id_simati = d.id_simati
    JOIN tbl_lot l       ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c  ON l.id_tapak = c.id_tapak
    ORDER BY b.tarikh_kebumi DESC
    LIMIT 5
");

// Load tapak list for dashboard map filter
$tapak_list = [];
$tapak_rows = mysqli_query($conn, "SELECT id_tapak, kod, nama, daerah FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah");
while ($tr = mysqli_fetch_assoc($tapak_rows)) $tapak_list[] = $tr;

// Lot data for satellite map
$lots_raw = mysqli_query($conn, "
    SELECT l.id_lot, l.id_tapak, l.kod_lot, l.fasa, l.blok, l.no_lot,
           l.status, l.latitud, l.longitud, l.sudut,
           t.kod AS tapak_kod, t.nama AS tapak_nama,
           t.lebar_lot_m, t.panjang_lot_m, t.sudut_grid,
           d.nama AS nama_simati, d.tarikh_mati, d.tarikh_lahir,
           b.tarikh_kebumi, b.id_pengebumian
    FROM tbl_lot l
    JOIN tbl_tapak_kubur t   ON l.id_tapak = t.id_tapak
    LEFT JOIN tbl_pengebumian b ON l.id_lot = b.id_lot
    LEFT JOIN tbl_simati d   ON b.id_simati = d.id_simati
    ORDER BY l.id_tapak, l.kod_lot
");
$lots_json = [];
while ($r = mysqli_fetch_assoc($lots_raw)) {
    if (!$r['latitud'] || !$r['longitud']) continue;
    $age = null;
    if ($r['tarikh_lahir'] && $r['tarikh_mati']) {
        $age = (new DateTime($r['tarikh_lahir']))->diff(new DateTime($r['tarikh_mati']))->y;
    }
    $short = $r['blok'] . '-' . str_pad($r['no_lot'], 4, '0', STR_PAD_LEFT);
    $lots_json[] = [
        'id_lot'       => (int)$r['id_lot'],
        'id_tapak'     => (int)$r['id_tapak'],
        'kod_lot'      => $r['kod_lot'],
        'short'        => $short,
        'fasa'         => $r['fasa'],
        'blok'         => $r['blok'],
        'status'       => $r['status'],
        'lat'          => (float)$r['latitud'],
        'lng'          => (float)$r['longitud'],
        'sudut'        => (float)($r['sudut'] ?? $r['sudut_grid'] ?? 0),
        'lebar_m'      => (float)$r['lebar_lot_m'],
        'panjang_m'    => (float)$r['panjang_lot_m'],
        'tapak_nama'   => $r['tapak_nama'],
        'tapak_kod'    => $r['tapak_kod'],
        'nama_simati'  => $r['nama_simati'],
        'tarikh_mati'  => $r['tarikh_mati'],
        'tarikh_lahir' => $r['tarikh_lahir'],
        'age'          => $age,
        'id_pengebumian' => $r['id_pengebumian'] ? (int)$r['id_pengebumian'] : null,
    ];
}

include '../includes/header.php';
?>

<!-- Stats Cards -->
<div class="page-header">
  <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['nama_penuh'] ?? 'Admin') ?> 👋</h2>
  <p>Ringkasan sistem pengurusan tanah perkuburan hari ini</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><path d="M8 6a4 4 0 0 1 8 0v2H8V6z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-number"><?= number_format($total_burials) ?></div>
      <div class="stat-label">Jumlah Pengebumian</div>
      <a href="<?= BASE_URL ?>/modules/burial/index.php" class="stat-link">Lihat semua →</a>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon light">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-number"><?= number_format($jumlah_lot) ?></div>
      <div class="stat-label">Jumlah Lot</div>
      <a href="<?= BASE_URL ?>/modules/lot/index.php" class="stat-link">Lihat peta →</a>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon dark">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-number"><?= number_format($total_deceased) ?></div>
      <div class="stat-label">Rekod Si Mati</div>
      <a href="<?= BASE_URL ?>/modules/burial/index.php" class="stat-link">Lihat semua →</a>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon accent">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-number"><?= number_format($penyelenggaraan) ?></div>
      <div class="stat-label">Penyelenggaraan</div>
      <a href="<?= BASE_URL ?>/modules/lot/index.php" class="stat-link">Urus lot →</a>
    </div>
  </div>
</div>

<!-- Dashboard Grid -->
<div class="grid-2">

  <!-- Peta Satelit Mini -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="card-header" style="padding:1rem 1.2rem;flex-wrap:wrap;gap:0.5rem;">
      <div>
        <h3>Peta Perkuburan</h3>
        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">
          <span style="display:inline-flex;align-items:center;gap:0.35rem;">
            <span style="width:10px;height:10px;background:#f5f2e8;border:1px solid #b8a888;border-radius:50%;display:inline-block;"></span> Tersedia
          </span>
          &nbsp;
          <span style="display:inline-flex;align-items:center;gap:0.35rem;">
            <span style="width:10px;height:10px;background:#6b4a3a;border-radius:50%;display:inline-block;"></span> Diduduki
          </span>
          &nbsp;
          <span style="display:inline-flex;align-items:center;gap:0.35rem;">
            <span style="width:10px;height:10px;background:#f97316;border-radius:50%;display:inline-block;"></span> Penyelenggaraan
          </span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
        <select id="dashTapakFilter" onchange="filterDashMap(this.value)"
          style="background:var(--bg-input);border:1px solid var(--border-input);border-radius:6px;
                 padding:0.35rem 0.7rem;font-size:0.75rem;color:var(--text-primary);outline:none;">
          <option value="">Semua Tapak</option>
          <?php foreach ($tapak_list as $t): ?>
            <option value="<?= $t['id_tapak'] ?>">
              <?= htmlspecialchars($t['kod']) ?> — <?= htmlspecialchars($t['daerah']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <a href="<?= BASE_URL ?>/modules/lot/index.php" class="btn-outline" style="font-size:0.78rem;">
          🗺️ Lihat Peta Penuh
        </a>
      </div>
    </div>
    <!-- Leaflet mini map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <div id="dashMap" style="height:380px;width:100%;"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function() {
      var lotsData = <?= json_encode($lots_json) ?>;
      var BASE = '<?= BASE_URL ?>';

      var map = L.map('dashMap', { zoomControl: true, attributionControl: false });
      map.setView([3.0738, 101.5183], 11);

      // Satellite tile
      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 22, maxNativeZoom: 19
      }).addTo(map);

      var COLOURS = {
        tersedia:        { pin: '#f5f2e8', border: '#b8a888' },
        diduduki:        { pin: '#6b4a3a', border: '#4a3227' },
        penyelenggaraan: { pin: '#f97316', border: '#c2410c' },
      };

      function makeIcon(status) {
        var col = COLOURS[status] || COLOURS.diduduki;
        return L.divIcon({
          className: '',
          html: '<div style="width:18px;height:18px;border-radius:50%;background:' + col.pin +
                ';border:2px solid ' + col.border + ';box-shadow:0 1px 3px rgba(0,0,0,0.4);"></div>',
          iconSize: [18, 18],
          iconAnchor: [9, 9]
        });
      }

      var poly2Group = L.layerGroup().addTo(map);

      function renderDashLots(lots) {
        poly2Group.clearLayers();
        var bounds = [];
        var statusLabel = { tersedia: 'Tersedia', diduduki: 'Diduduki', penyelenggaraan: 'Penyelenggaraan' };
        lots.forEach(function(lot) {
          var popup2a = '<b>' + lot.short + '</b><br>'
                     + '<small>' + (statusLabel[lot.status] || lot.status) + '</small><br>'
                     + (lot.nama_simati ? lot.nama_simati + '<br>' : '')
                     + '<small>📍 ' + lot.lat.toFixed(7) + ', ' + lot.lng.toFixed(7) + '</small>';
          L.marker([lot.lat, lot.lng], { icon: makeIcon(lot.status) })
              .bindPopup(popup2a).addTo(poly2Group);
          bounds.push([lot.lat, lot.lng]);
        });
        if (bounds.length) map.fitBounds(bounds, { padding: [20,20], maxZoom: 20 });
      }

      renderDashLots(lotsData);

      window.filterDashMap = function(tapakId) {
        var filtered = tapakId
          ? lotsData.filter(function(l) { return String(l.id_tapak) === String(tapakId); })
          : lotsData;
        renderDashLots(filtered);
      };
    })();
    </script>
  </div>

  <!-- Recent Burials -->
  <div class="card">
    <div class="card-header">
      <h3>Pengebumian Terkini</h3>
      <a href="<?= BASE_URL ?>/modules/burial/index.php" class="btn-outline">Lihat Semua</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Si Mati</th>
            <th>Tarikh</th>
            <th>Kod Lot</th>
            <th>Tapak Perkuburan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($recent) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
              <tr>
                <td><?= htmlspecialchars($row['nama_simati']) ?></td>
                <td><?= date('d M Y', strtotime($row['tarikh_kebumi'])) ?></td>
                <td><strong><?= htmlspecialchars($row['blok']) ?>-<?= str_pad($row['no_lot'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                <td><span class="badge badge-diduduki"><?= htmlspecialchars($row['nama_tapak']) ?></span></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 2rem;">Tiada rekod pengebumian</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Lot Panel (click from map) -->
<div class="lot-panel" id="lotPanel">
  <div class="lot-panel-header">
    <h3 class="lot-panel-id">Lot —</h3>
    <button class="btn-icon lot-panel-close" id="lotPanelClose">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div style="display: flex; flex-direction: column; gap: 1rem;">
    <div>
      <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.3rem;">Status</div>
      <div class="lot-panel-status"></div>
    </div>
    <div>
      <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.3rem;">Nama Si Mati</div>
      <div class="lot-panel-deceased" style="font-weight: 500; color: var(--text-primary);"></div>
    </div>
    <div>
      <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.3rem;">Tarikh Pengebumian</div>
      <div class="lot-panel-date" style="font-weight: 500; color: var(--text-primary);"></div>
    </div>
    <div>
      <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.3rem;">Seksyen</div>
      <div class="lot-panel-section" style="font-weight: 500; color: var(--text-primary);"></div>
    </div>

    <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
      <a href="<?= BASE_URL ?>/modules/lot/edit.php" class="btn-save btn-assign-lot" style="display:none; text-align:center;">Tetapkan Lot Ini</a>
      <a href="<?= BASE_URL ?>/modules/lot/edit.php" class="btn-outline" style="text-align:center;">Kemaskini Lot</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
