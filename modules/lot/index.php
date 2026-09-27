<?php
require_once '../../config/db.php';
requireLogin();

$page_title   = 'Pengurusan Lot';
$topbar_title = 'Pengurusan Lot';

// Load tapak kubur
$tapak_list = [];
$tapak_rows = mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($r = mysqli_fetch_assoc($tapak_rows)) $tapak_list[] = $r;

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        SUM(status='tersedia')          AS tersedia,
        SUM(status='diduduki')         AS diduduki,
        SUM(status='penyelenggaraan')  AS penyelenggaraan,
        COUNT(*)                       AS total
    FROM tbl_lot
"));

// Load all lots with burial + deceased info
$lots_raw = mysqli_query($conn, "
    SELECT
        l.id_lot, l.id_tapak, l.kod_lot, l.fasa, l.blok, l.no_lot,
        l.status, l.latitud, l.longitud, l.sudut, l.catatan,
        t.kod AS tapak_kod, t.nama AS tapak_nama, t.daerah, t.alamat,
        t.lebar_lot_m, t.panjang_lot_m, t.sudut_grid,
        d.nama  AS nama_simati, d.tarikh_mati, d.tarikh_lahir, d.no_ic,
        b.tarikh_kebumi, b.dikebumikan_oleh, b.id_pengebumian
    FROM tbl_lot l
    JOIN tbl_tapak_kubur t      ON l.id_tapak   = t.id_tapak
    LEFT JOIN tbl_pengebumian b ON l.id_lot     = b.id_lot
    LEFT JOIN tbl_simati d      ON b.id_simati  = d.id_simati
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
        'id_lot'          => (int)$r['id_lot'],
        'id_tapak'        => (int)$r['id_tapak'],
        'kod_lot'         => $r['kod_lot'],
        'short'           => $short,
        'fasa'            => $r['fasa'],
        'blok'            => $r['blok'],
        'no_lot'          => (int)$r['no_lot'],
        'status'          => $r['status'],
        'lat'             => (float)$r['latitud'],
        'lng'             => (float)$r['longitud'],
        'sudut'           => (float)($r['sudut'] ?? $r['sudut_grid'] ?? 0),
        'lebar_m'         => (float)$r['lebar_lot_m'],
        'panjang_m'       => (float)$r['panjang_lot_m'],
        'tapak_nama'      => $r['tapak_nama'],
        'tapak_kod'       => $r['tapak_kod'],
        'daerah'          => $r['daerah'],
        'alamat'          => $r['alamat'],
        'nama_simati'     => $r['nama_simati'],
        'tarikh_mati'     => $r['tarikh_mati'],
        'tarikh_lahir'    => $r['tarikh_lahir'],
        'tarikh_kebumi'   => $r['tarikh_kebumi'],
        'dikebumikan_oleh'=> $r['dikebumikan_oleh'],
        'age'             => $age,
        'id_pengebumian'  => $r['id_pengebumian'] ? (int)$r['id_pengebumian'] : null,
        'catatan'         => $r['catatan'],
    ];
}

include '../../includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
  /* Full-height layout */
  .page-content { padding: 0 !important; display: flex; flex-direction: column; height: calc(100vh - 56px); }

  /* ── Top stats bar ── */
  .lot-stats-bar {
    display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;
    padding: 0.65rem 1.2rem;
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
  }
  .lot-stat { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; }
  .lot-stat-num { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; line-height: 1; }
  .lot-stat-num.tersedia     { color: #8a7a55; }
  .lot-stat-num.diduduki      { color: var(--clr-7); }
  .lot-stat-num.penyelenggaraan { color: #d97706; }
  .lot-stat-num.total         { color: var(--clr-1); }
  .lot-stat-label { color: var(--text-muted); font-size: 0.75rem; }

  /* Filter bar */
  .lot-filter-bar {
    display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
    padding: 0.6rem 1.2rem;
    background: var(--bg-body);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
  }
  .lot-filter-select {
    background: var(--bg-input); border: 1px solid var(--border-input);
    border-radius: 6px; padding: 0.38rem 0.75rem;
    font-family: var(--font-body); font-size: 0.8rem;
    color: var(--text-primary); outline: none; min-width: 220px;
  }
  .map-legend { display: flex; align-items: center; gap: 0.8rem; margin-left: auto; }
  .legend-item { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: var(--text-muted); }
  .legend-box { width: 14px; height: 8px; border-radius: 2px; }
  .legend-box.tersedia        { background: #f5f2e8; border: 1px solid #b8a888; }
  .legend-box.diduduki        { background: #6b4a3a; }
  .legend-box.penyelenggaraan { background: #f97316; }

  /* ── Main body: map + panel side by side ── */
  .lot-body { display: flex; flex: 1; overflow: hidden; }

  /* Map */
  #lotMap { flex: 1; min-width: 0; }

  /* ── Detail panel ── */
  .lot-detail-panel {
    width: 320px;
    flex-shrink: 0;
    background: var(--bg-card);
    border-left: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    transition: width 0.2s ease;
  }
  .lot-detail-panel.collapsed { width: 0; border-left: none; }

  .panel-empty {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 0.82rem; gap: 0.5rem; padding: 2rem;
    text-align: center;
  }
  .panel-empty svg { opacity: 0.3; }

  .panel-content { padding: 1.2rem; display: none; }
  .panel-content.show { display: block; }

  .panel-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 1rem; gap: 0.5rem;
  }
  .panel-kod {
    font-family: var(--font-display); font-size: 1rem; font-weight: 700;
    color: var(--clr-1); line-height: 1.3;
  }
  .panel-short {
    font-size: 1.6rem; font-weight: 700; color: var(--clr-7);
    font-family: var(--font-display);
  }
  .panel-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 0.2rem;
  }
  .panel-close:hover { color: var(--text-primary); }

  .panel-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    font-size: 0.8rem; padding: 0.4rem 0;
    border-bottom: 1px solid var(--border-color);
    gap: 0.5rem;
  }
  .panel-row:last-of-type { border-bottom: none; }
  .panel-row-label { color: var(--text-muted); font-weight: 500; white-space: nowrap; }
  .panel-row-val   { text-align: right; font-weight: 500; color: var(--text-primary); }

  .panel-coord {
    font-family: monospace; font-size: 0.72rem; color: var(--text-muted);
    background: var(--bg-body); padding: 0.4rem 0.6rem;
    border-radius: 6px; margin: 0.6rem 0;
  }

  .panel-actions { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.8rem; }
</style>

<!-- Stats bar -->
<div class="lot-stats-bar">
  <div class="lot-stat">
    <div>
      <div class="lot-stat-num total"><?= $stats['total'] ?></div>
      <div class="lot-stat-label">Jumlah Lot</div>
    </div>
  </div>
  <div style="width:1px;height:32px;background:var(--border-color);"></div>
  <div class="lot-stat">
    <div>
      <div class="lot-stat-num tersedia"><?= $stats['tersedia'] ?? 0 ?></div>
      <div class="lot-stat-label">Tersedia</div>
    </div>
  </div>
  <div style="width:1px;height:32px;background:var(--border-color);"></div>
  <div class="lot-stat">
    <div>
      <div class="lot-stat-num diduduki"><?= $stats['diduduki'] ?? 0 ?></div>
      <div class="lot-stat-label">Diduduki</div>
    </div>
  </div>
  <div style="width:1px;height:32px;background:var(--border-color);"></div>
  <div class="lot-stat">
    <div>
      <div class="lot-stat-num penyelenggaraan"><?= $stats['penyelenggaraan'] ?? 0 ?></div>
      <div class="lot-stat-label">Penyelenggaraan</div>
    </div>
  </div>
  <div style="margin-left:auto;display:flex;gap:0.5rem;">
    <a href="<?= BASE_URL ?>/modules/lot/add.php" class="btn-outline" style="font-size:0.8rem;padding:0.45rem 1rem;">
      + Tambah Lot Manual
    </a>
    <a href="<?= BASE_URL ?>/modules/burial/add.php" class="btn-save" style="font-size:0.8rem;padding:0.45rem 1rem;">
      + Daftar Pengebumian Baru
    </a>
  </div>
</div>

<!-- Filter bar -->
<div class="lot-filter-bar">
  <select class="lot-filter-select" id="tapakFilter" onchange="filterTapak(this.value)">
    <option value="">— Semua Tapak Perkuburan —</option>
    <?php foreach ($tapak_list as $t): ?>
      <option value="<?= $t['id_tapak'] ?>">
        <?= htmlspecialchars($t['nama']) ?> (<?= htmlspecialchars($t['daerah']) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <select class="lot-filter-select" style="min-width:160px;" id="statusFilter" onchange="filterStatus(this.value)">
    <option value="">— Semua Status —</option>
    <option value="tersedia">Tersedia</option>
    <option value="diduduki">Diduduki</option>
    <option value="penyelenggaraan">Penyelenggaraan</option>
  </select>
  <div class="map-legend">
    <div class="legend-item"><div class="legend-box tersedia"></div> Tersedia</div>
    <div class="legend-item"><div class="legend-box diduduki"></div> Diduduki</div>
    <div class="legend-item"><div class="legend-box penyelenggaraan"></div> Penyelenggaraan</div>
  </div>
</div>

<!-- Body: map + panel -->
<div class="lot-body">

  <!-- Satellite map -->
  <div id="lotMap"></div>

  <!-- Detail panel -->
  <div class="lot-detail-panel" id="detailPanel">

    <!-- Empty state -->
    <div class="panel-empty" id="panelEmpty">
      <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
      <div>Klik pada lot di peta untuk melihat maklumat</div>
    </div>

    <!-- Content -->
    <div class="panel-content" id="panelContent">
      <div class="panel-header">
        <div>
          <div class="panel-short" id="pShort"></div>
          <div class="panel-kod" id="pKod"></div>
        </div>
        <button class="panel-close" onclick="closePanel()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div id="pStatus"></div>

      <div style="margin-top:0.8rem;" id="pRows"></div>

      <div class="panel-coord" id="pCoord"></div>

      <div class="panel-actions" id="pActions"></div>
    </div>

  </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE     = '<?= BASE_URL ?>';
var allLots  = <?= json_encode($lots_json) ?>;
var polyGroup, currentTapak = '', currentStatus = '';

var COLOURS = {
  tersedia:        { pin: '#f5f2e8', border: '#b8a888' },
  diduduki:        { pin: '#6b4a3a', border: '#4a3227' },
  penyelenggaraan: { pin: '#f97316', border: '#c2410c' },
};

var CRESCENT_SVG = '<svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 100 20 8 8 0 010-20z" opacity="0.9"/></svg>';

function makeIcon(status) {
  var col = COLOURS[status] || COLOURS.diduduki;
  var inner = status === 'tersedia' ? '' : CRESCENT_SVG;
  return L.divIcon({
    className: '',
    html: '<div style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;' +
          'background:' + col.pin + ';border:2px solid ' + col.border + ';box-shadow:0 1px 3px rgba(0,0,0,0.4);">' +
          '<span style="width:11px;height:11px;display:block;">' + inner + '</span></div>',
    iconSize: [22, 22],
    iconAnchor: [11, 11]
  });
}

/* ── Init map ── */
var map = L.map('lotMap', { zoomControl: true });
map.setView([3.0738, 101.5183], 12);

var satLayer = L.tileLayer(
  'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
  { attribution: '© Esri', maxZoom: 22, maxNativeZoom: 19 }
);
var osmLayer = L.tileLayer(
  'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  { attribution: '© OpenStreetMap', maxZoom: 22 }
);
osmLayer.addTo(map);
L.control.layers({ '🛰️ Satelit (ESRI)': satLayer, '🗺️ Peta Jalan (OSM)': osmLayer }).addTo(map);

/* Circle markers — no polygon helpers needed */

/* ── Format date ── */
function fmtDate(d) {
  if (!d) return '—';
  var dt = new Date(d);
  return dt.toLocaleDateString('ms-MY', { day:'2-digit', month:'short', year:'numeric' });
}

/* ── Open detail panel ── */
function openPanel(lot) {
  document.getElementById('panelEmpty').style.display   = 'none';
  document.getElementById('panelContent').className     = 'panel-content show';

  document.getElementById('pShort').textContent = lot.short;
  document.getElementById('pKod').textContent   = lot.kod_lot;

  var statusLabel = { tersedia:'Tersedia', diduduki:'Diduduki', penyelenggaraan:'Penyelenggaraan' };
  var statusCol   = { tersedia:'#8a7a55', diduduki:'var(--clr-7)', penyelenggaraan:'#d97706' };
  document.getElementById('pStatus').innerHTML =
    '<span class="badge" style="background:' + (statusCol[lot.status]||'#888') + ';color:#fff;font-size:0.7rem;">' +
    (statusLabel[lot.status]||lot.status) + '</span>';

  var rows = [
    ['Tapak Perkuburan', lot.tapak_nama],
    ['Daerah', lot.daerah],
    ['Fasa / Blok', lot.fasa + ' / Blok ' + lot.blok],
  ];
  if (lot.status === 'tersedia') {
    rows.push(['Status', 'Kosong — belum ada pengebumian']);
  } else if (lot.nama_simati) {
    rows.push(['Nama Arwah', lot.nama_simati]);
    rows.push(['Tarikh Kematian', fmtDate(lot.tarikh_mati)]);
    rows.push(['Tarikh Pengebumian', fmtDate(lot.tarikh_kebumi)]);
    if (lot.age !== null) rows.push(['Umur Semasa Meninggal', lot.age + ' tahun']);
  }
  if (lot.catatan) rows.push(['Catatan', lot.catatan]);

  document.getElementById('pRows').innerHTML = rows.map(function(r) {
    return '<div class="panel-row"><span class="panel-row-label">' + r[0] +
           '</span><span class="panel-row-val">' + (r[1]||'—') + '</span></div>';
  }).join('');

  document.getElementById('pCoord').innerHTML =
    '📍 Latitud: ' + lot.lat.toFixed(7) + '<br>Longitud: ' + lot.lng.toFixed(7);

  var acts = '<a href="https://www.google.com/maps?q=' + lot.lat + ',' + lot.lng +
             '&z=21&hl=ms" target="_blank" class="btn-outline" style="text-align:center;font-size:0.78rem;">🗺️ Google Maps</a>';
  if (lot.status === 'tersedia') {
    acts += '<a href="' + BASE + '/modules/burial/add.php" class="btn-save" style="text-align:center;font-size:0.78rem;">+ Daftar Pengebumian</a>';
  }
  if (lot.id_pengebumian) {
    acts += '<a href="' + BASE + '/modules/burial/view.php?id=' + lot.id_pengebumian +
            '" class="btn-outline" style="text-align:center;font-size:0.78rem;">Lihat Rekod Pengebumian</a>';
  }
  acts += '<a href="' + BASE + '/modules/lot/edit.php?id=' + lot.id_lot +
          '" class="btn-outline" style="text-align:center;font-size:0.78rem;">Kemaskini Lot</a>';
  document.getElementById('pActions').innerHTML = acts;
}

function closePanel() {
  document.getElementById('panelEmpty').style.display    = 'flex';
  document.getElementById('panelContent').className      = 'panel-content';
}

/* ── Draw polygons ── */
polyGroup = L.layerGroup().addTo(map);

function renderLots(lots) {
  polyGroup.clearLayers();
  var bounds = [];

  lots.forEach(function(lot) {
    var marker = L.marker([lot.lat, lot.lng], { icon: makeIcon(lot.status) });

    marker.bindTooltip(lot.short, {
      permanent: false, direction: 'top',
      className: 'lot-tip'
    });

    marker.on('click', function() { openPanel(lot); });

    marker.addTo(polyGroup);
    bounds.push([lot.lat, lot.lng]);
  });

  if (bounds.length) {
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 20 });
  }
}

renderLots(allLots);

/* ── Filters ── */
function applyFilters() {
  var filtered = allLots.filter(function(l) {
    var okTapak  = !currentTapak  || String(l.id_tapak) === String(currentTapak);
    var okStatus = !currentStatus || l.status === currentStatus;
    return okTapak && okStatus;
  });
  renderLots(filtered);
}
function filterTapak(v)  { currentTapak  = v; applyFilters(); }
function filterStatus(v) { currentStatus = v; applyFilters(); }
</script>

<style>
.lot-tip { font-size:0.65rem; font-weight:700; background:rgba(32,51,27,0.88); color:#C3CA92; border:none; border-radius:3px; padding:0.1rem 0.35rem; }
.lot-tip::before { display:none; }
</style>

<?php include '../../includes/footer.php'; ?>
