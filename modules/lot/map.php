<?php
require_once '../../config/db.php';
requireLogin();

$page_title   = 'Peta Interaktif Perkuburan';
$topbar_title = 'Peta Interaktif';

$focus_lot = intval($_GET['focus'] ?? 0);

// Load tapak kubur list
$tapak_list = [];
$tapak_rows = mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($r = mysqli_fetch_assoc($tapak_rows)) $tapak_list[] = $r;

// Load all lots with burial + deceased info
$lots_raw = mysqli_query($conn, "
    SELECT
        l.id_lot, l.id_tapak, l.kod_lot, l.fasa, l.blok, l.no_lot,
        l.status, l.latitud, l.longitud, l.sudut,
        t.kod AS tapak_kod, t.nama AS tapak_nama, t.daerah,
        t.lebar_lot_m, t.panjang_lot_m, t.sudut_grid,
        d.nama  AS nama_simati, d.tarikh_mati, d.tarikh_lahir,
        b.tarikh_kebumi, b.id_pengebumian
    FROM tbl_lot l
    JOIN tbl_tapak_kubur t   ON l.id_tapak   = t.id_tapak
    LEFT JOIN tbl_pengebumian b ON l.id_lot  = b.id_lot
    LEFT JOIN tbl_simati d   ON b.id_simati  = d.id_simati
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
        'id_lot'        => (int)$r['id_lot'],
        'id_tapak'      => (int)$r['id_tapak'],
        'kod_lot'       => $r['kod_lot'],
        'short'         => $short,
        'fasa'          => $r['fasa'],
        'blok'          => $r['blok'],
        'status'        => $r['status'],
        'lat'           => (float)$r['latitud'],
        'lng'           => (float)$r['longitud'],
        'tapak_nama'    => $r['tapak_nama'],
        'tapak_kod'     => $r['tapak_kod'],
        'daerah'        => $r['daerah'],
        'nama_simati'   => $r['nama_simati'],
        'tarikh_mati'   => $r['tarikh_mati'],
        'tarikh_lahir'  => $r['tarikh_lahir'],
        'age'           => $age,
        'id_pengebumian'=> $r['id_pengebumian'] ? (int)$r['id_pengebumian'] : null,
    ];
}

include '../../includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
  .page-content { padding: 0 !important; display: flex; flex-direction: column; }

  /* ── Toolbar ── */
  .map-toolbar {
    display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
    padding: 0.8rem 1.2rem;
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0; z-index: 10;
  }
  .map-toolbar-title { font-weight: 600; font-size: 0.9rem; color: var(--text-primary); margin-right: 0.25rem; }
  .map-select {
    background: var(--bg-input); border: 1px solid var(--border-input);
    border-radius: 6px; padding: 0.38rem 0.75rem;
    font-family: var(--font-body); font-size: 0.8rem;
    color: var(--text-primary); outline: none; cursor: pointer; min-width: 200px;
  }
  .map-legend {
    display: flex; align-items: center; gap: 1rem; margin-left: auto; flex-wrap: wrap;
  }
  .legend-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: var(--text-muted); }
  .legend-dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid; }
  .legend-dot.tersedia        { background: #f5f2e8; border-color: #b8a888; }
  .legend-dot.diduduki        { background: #6b4a3a; border-color: #4a3227; }
  .legend-dot.penyelenggaraan { background: #f97316; border-color: #c2410c; }

  /* ── Map ── */
  #map { flex: 1; min-height: calc(100vh - 110px); }

  /* ── Pin marker icon (crescent-badge style, matches reference aesthetic) ── */
  .lot-pin {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: 2.5px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.4);
  }
  .lot-pin.diduduki        { background: #6b4a3a; }
  .lot-pin.penyelenggaraan { background: #f97316; }
  .lot-pin.tersedia        { background: #f5f2e8; border-color: #b8a888; }
  .lot-pin svg { width: 13px; height: 13px; }

  /* ── Leaflet popup ── */
  .lot-popup       { font-family: 'Inter', sans-serif; min-width: 210px; }
  .lp-code         { font-family: 'Cormorant Garamond', serif; font-size: 1.15rem; font-weight: 700; color: #20331B; margin-bottom: 2px; }
  .lp-tapak        { font-size: 0.72rem; color: #888; margin-bottom: 0.55rem; }
  .lp-status       { display:inline-block; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:0.15rem 0.5rem; border-radius:50px; margin-bottom:0.6rem; }
  .lp-status.tersedia        { background:#f5f2e8; color:#8a7a55; }
  .lp-status.diduduki        { background:#1e3a1e; color:#C3CA92; }
  .lp-status.penyelenggaraan { background:#ffedd5; color:#ea580c; }
  .lp-row          { display:flex; justify-content:space-between; font-size:0.78rem; padding:0.28rem 0; border-bottom:1px solid #f0f0ec; }
  .lp-row:last-of-type { border-bottom:none; }
  .lp-label        { color:#888; font-weight:500; }
  .lp-val          { color:#1a1a1a; font-weight:500; text-align:right; max-width:130px; }
  .lp-coord        { font-family:monospace; font-size:0.68rem; color:#666; margin-top:0.45rem; background:#f8f9f4; padding:0.3rem 0.5rem; border-radius:4px; }
  .lp-empty        { font-size:0.78rem; color:#8a7a55; padding:0.4rem 0; font-style:italic; }
  .lp-actions      { margin-top:0.65rem; display:flex; gap:0.4rem; flex-wrap:wrap; }
  .lp-btn          { display:inline-block; padding:0.3rem 0.7rem; border-radius:6px; font-size:0.72rem; font-weight:600; text-decoration:none; cursor:pointer; border:none; }
  .lp-btn.primary  { background:#20331B; color:#C3CA92; }
  .lp-btn.outline  { background:transparent; color:#20331B; border:1px solid #20331B; }

  /* Leaflet tooltip (permanent label, hidden when zoomed out) */
  .lot-tip { font-size:0.6rem; font-weight:700; background:rgba(32,51,27,0.88); color:#C3CA92; border:none; border-radius:3px; padding:0.06rem 0.28rem; pointer-events:none; }
  .lot-tip::before { display:none; }
  .lot-tip.diduduki        { background:rgba(107,74,58,0.9); color:#fff; }
  .lot-tip.penyelenggaraan { background:rgba(249,115,22,0.9); color:#3a1e00; }
  .lot-tip.tersedia        { background:rgba(245,242,232,0.92); color:#8a7a55; }
</style>

<!-- ── Toolbar ── -->
<div class="map-toolbar">
  <span class="map-toolbar-title">🗺️ Peta Lot Perkuburan</span>

  <select class="map-select" id="tapakFilter" onchange="filterTapak(this.value)">
    <option value="">— Semua Tapak Perkuburan —</option>
    <?php foreach ($tapak_list as $t): ?>
      <option value="<?= $t['id_tapak'] ?>">
        <?= htmlspecialchars($t['nama']) ?> (<?= htmlspecialchars($t['daerah']) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <div class="map-legend">
    <div class="legend-item"><div class="legend-dot tersedia"></div> Tersedia</div>
    <div class="legend-item"><div class="legend-dot diduduki"></div> Diduduki</div>
    <div class="legend-item"><div class="legend-dot penyelenggaraan"></div> Penyelenggaraan</div>
  </div>
</div>

<!-- ── Map container ── -->
<div id="map"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var BASE     = '<?= BASE_URL ?>';
var allLots  = <?= json_encode($lots_json) ?>;
var focusLotId = <?= json_encode($focus_lot ?: null) ?>;
var markers  = [];
var currentTapak = '';

var CRESCENT_SVG = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 8 8 0 010-20z" opacity="0.9"/></svg>';

// ── Initialise map — default view Selangor ──
var map = L.map('map', { zoomControl: true, preferCanvas: false });
map.setView([3.0738, 101.5183], 11);

// Tile layers
var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors', maxZoom: 22
});
var satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
  attribution: 'Tiles © Esri', maxZoom: 22, maxNativeZoom: 19
});
osmLayer.addTo(map);
L.control.layers({ '🛰️ Satelit (ESRI)': satLayer, '🗺️ Peta Jalan (OSM)': osmLayer }).addTo(map);

// ── Build custom pin icon per status ──
function makeIcon(status) {
  return L.divIcon({
    className: '',
    html: '<div class="lot-pin ' + status + '">' + (status === 'tersedia' ? '' : CRESCENT_SVG) + '</div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12],
    popupAnchor: [0, -12]
  });
}
var ICONS = {
  tersedia: makeIcon('tersedia'),
  diduduki: makeIcon('diduduki'),
  penyelenggaraan: makeIcon('penyelenggaraan')
};

// ── Build popup HTML ──
function buildPopup(lot) {
  var statusLabel = { tersedia: 'Tersedia', diduduki: 'Diduduki', penyelenggaraan: 'Penyelenggaraan' };
  var h = '<div class="lot-popup">';
  h += '<div class="lp-code">' + lot.short + '</div>';
  h += '<div class="lp-tapak">' + lot.tapak_nama + ' · ' + lot.daerah + '</div>';
  h += '<span class="lp-status ' + lot.status + '">' + (statusLabel[lot.status] || lot.status) + '</span>';

  if (lot.status === 'tersedia') {
    h += '<div class="lp-empty">Lot ini masih kosong — belum ada pengebumian didaftarkan.</div>';
  } else {
    if (lot.nama_simati) {
      h += '<div class="lp-row"><span class="lp-label">Nama Arwah</span><span class="lp-val">' + lot.nama_simati + '</span></div>';
    }
    if (lot.tarikh_mati) {
      var d = new Date(lot.tarikh_mati);
      h += '<div class="lp-row"><span class="lp-label">Tarikh Kematian</span><span class="lp-val">' + d.toLocaleDateString('ms-MY',{day:'2-digit',month:'short',year:'numeric'}) + '</span></div>';
    }
    if (lot.age !== null) {
      h += '<div class="lp-row"><span class="lp-label">Umur Semasa Meninggal</span><span class="lp-val">' + lot.age + ' tahun</span></div>';
    }
  }
  h += '<div class="lp-row"><span class="lp-label">Fasa / Blok</span><span class="lp-val">' + lot.fasa + ' / Blok ' + lot.blok + '</span></div>';

  // GPS coordinates — still accurate & within cemetery grounds regardless of status
  h += '<div class="lp-coord">📍 Latitud: ' + lot.lat.toFixed(7) + '<br>Longitud: ' + lot.lng.toFixed(7) + '</div>';

  // Actions
  h += '<div class="lp-actions">';
  if (lot.id_pengebumian) {
    h += '<a href="' + BASE + '/modules/burial/view.php?id=' + lot.id_pengebumian + '" class="lp-btn primary">Lihat Rekod</a>';
  } else if (lot.status === 'tersedia') {
    h += '<a href="' + BASE + '/modules/burial/add.php" class="lp-btn primary">+ Daftar Pengebumian</a>';
  }
  h += '<a href="https://www.google.com/maps?q=' + lot.lat + ',' + lot.lng + '&z=21&hl=ms" target="_blank" class="lp-btn outline">Google Maps</a>';
  h += '<a href="' + BASE + '/modules/lot/edit.php?id=' + lot.id_lot + '" class="lp-btn outline">Kemaskini</a>';
  h += '</div></div>';
  return h;
}

// ── Draw all markers ──
var markerGroup = L.layerGroup().addTo(map);

function renderMarkers(lots) {
  markerGroup.clearLayers();
  markers = [];
  var bounds = [];

  lots.forEach(function(lot) {
    var icon = ICONS[lot.status] || ICONS.diduduki;
    var marker = L.marker([lot.lat, lot.lng], { icon: icon });

    marker.bindPopup(buildPopup(lot), { maxWidth: 260 });
    marker.bindTooltip(lot.short, {
      permanent: true,
      direction: 'top',
      offset: [0, -10],
      className: 'lot-tip ' + lot.status
    });

    marker._lotData = lot;
    marker.addTo(markerGroup);
    markers.push(marker);
    bounds.push([lot.lat, lot.lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 20 });
  }
}

// ── Toggle lot labels based on zoom (avoid clutter when zoomed out) ──
var LABEL_MIN_ZOOM = 19;
function updateLabelVisibility() {
  var show = map.getZoom() >= LABEL_MIN_ZOOM;
  document.querySelectorAll('.lot-tip').forEach(function(el) {
    el.style.display = show ? '' : 'none';
  });
}
map.on('zoomend', updateLabelVisibility);
map.on('layeradd', updateLabelVisibility);

// Initial render — all lots
renderMarkers(allLots);
updateLabelVisibility();

// ── Focus on a specific lot (e.g. arrived from a burial record) ──
if (focusLotId) {
  var focusMarker = markers.find(function(m) { return m._lotData.id_lot === focusLotId; });
  if (focusMarker) {
    document.getElementById('tapakFilter').value = focusMarker._lotData.id_tapak;
    map.setView([focusMarker._lotData.lat, focusMarker._lotData.lng], 21);
    setTimeout(function() { focusMarker.openPopup(); }, 300);
  }
}

// ── Filter by tapak ──
function filterTapak(val) {
  currentTapak = val;
  var filtered = val
    ? allLots.filter(function(l) { return String(l.id_tapak) === String(val); })
    : allLots;
  renderMarkers(filtered);
  updateLabelVisibility();
}
</script>

<?php include '../../includes/footer.php'; ?>
