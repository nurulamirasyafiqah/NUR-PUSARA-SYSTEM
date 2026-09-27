<?php
require_once '../../config/db.php';
requireLogin();

$page_title   = 'Kalibrasi Orientasi Grid';
$topbar_title = 'Kalibrasi Orientasi Grid';

$tapak_list = [];
$rows = mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($r = mysqli_fetch_assoc($rows)) $tapak_list[] = $r;

include '../../includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
  .page-content { padding: 0 !important; display: flex; flex-direction: column; }
  .cal-toolbar {
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
    padding: 0.9rem 1.2rem; background: var(--bg-card); border-bottom: 1px solid var(--border-color);
  }
  .cal-select {
    background: var(--bg-input); border: 1px solid var(--border-input); border-radius: 6px;
    padding: 0.38rem 0.75rem; font-size: 0.82rem; color: var(--text-primary); min-width: 260px;
  }
  .cal-slider-wrap { display: flex; align-items: center; gap: 0.7rem; flex: 1; min-width: 280px; }
  .cal-slider-wrap input[type=range] { flex: 1; }
  .cal-angle-val { font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--clr-1); min-width: 60px; text-align: right; }
  .cal-note { font-size: 0.72rem; color: var(--text-muted); padding: 0 1.2rem 0.7rem; }
  #calMap { flex: 1; min-height: calc(100vh - 150px); }
  .cal-help {
    position: absolute; top: 90px; left: 50%; transform: translateX(-50%);
    background: rgba(32,51,27,0.92); color: #C3CA92; padding: 0.5rem 1rem; border-radius: 8px;
    font-size: 0.78rem; z-index: 500; max-width: 480px; text-align: center;
  }
</style>

<div class="cal-toolbar">
  <span style="font-weight:600;font-size:0.9rem;">🧭 Kalibrasi Orientasi Grid</span>
  <select class="cal-select" id="tapakSelect" onchange="loadTapak(this.value)">
    <?php foreach ($tapak_list as $t): ?>
      <option value="<?= $t['id_tapak'] ?>"
        data-lat="<?= $t['latitud'] ?>" data-lng="<?= $t['longitud'] ?>"
        data-bearing="<?= $t['sudut_grid'] ?>" data-rowsp="<?= $t['jarak_baris_m'] ?>" data-colsp="<?= $t['jarak_lajur_m'] ?>">
        <?= htmlspecialchars($t['nama']) ?> (<?= htmlspecialchars($t['daerah']) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <div class="cal-slider-wrap">
    <input type="range" id="bearingSlider" min="-180" max="180" step="1" value="0" oninput="onSliderChange(this.value)">
    <div class="cal-angle-val" id="angleVal">0°</div>
  </div>
  <button class="btn-save" id="saveBtn" onclick="saveBearing()">💾 Simpan &amp; Kira Semula Lot</button>
</div>
<div class="cal-note">Pusing slider sehingga garisan kuning sepadan dengan arah barisan kubur sebenar dalam gambar satelit di bawah, lepas tu klik "Simpan". Semua lot sedia ada untuk tapak ini akan dikira semula ikut sudut baharu.</div>

<div style="position:relative;flex:1;">
  <div class="cal-help">Garisan kuning = arah baris (row). Garisan biru = arah lajur (column). Padankan dengan barisan kubur sebenar.</div>
  <div id="calMap"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE = '<?= BASE_URL ?>';
var map = L.map('calMap', { zoomControl: true });
var satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
  attribution: 'Tiles © Esri', maxZoom: 22, maxNativeZoom: 19
}).addTo(map);

var previewGroup = L.layerGroup().addTo(map);
var centreLat, centreLng, rowSp, colSp, currentBearing = 0;

function drawPreview(bearing) {
  previewGroup.clearLayers();
  var earthR = 6371000;
  var bearRad = bearing * Math.PI / 180;

  function offset(rowM, colM) {
    var dLat = ((rowM * Math.cos(bearRad)) + (colM * -Math.sin(bearRad))) / earthR;
    var dLng = ((rowM * Math.sin(bearRad)) + (colM *  Math.cos(bearRad))) / (earthR * Math.cos(centreLat * Math.PI / 180));
    return [centreLat + dLat * 180 / Math.PI, centreLng + dLng * 180 / Math.PI];
  }

  // Row direction line (yellow) — 6 rows deep
  var rowLine = [];
  for (var r = 0; r < 6; r++) rowLine.push(offset(r * rowSp, 0));
  L.polyline(rowLine, { color: '#facc15', weight: 4, opacity: 0.9 }).addTo(previewGroup);

  // Column direction line (blue) — 6 cols wide
  var colLine = [];
  for (var c = 0; c < 6; c++) colLine.push(offset(0, c * colSp));
  L.polyline(colLine, { color: '#38bdf8', weight: 4, opacity: 0.9 }).addTo(previewGroup);

  // Small grid dots for visual reference (5x5)
  for (var r = 0; r < 5; r++) {
    for (var c = 0; c < 5; c++) {
      var pos = offset(r * rowSp, c * colSp);
      L.circleMarker(pos, { radius: 5, color: '#fff', weight: 1.5, fillColor: '#6b4a3a', fillOpacity: 0.9 }).addTo(previewGroup);
    }
  }

  // Centre marker
  L.circleMarker([centreLat, centreLng], { radius: 7, color: '#fff', weight: 2, fillColor: '#dc2626', fillOpacity: 1 }).addTo(previewGroup);
}

function onSliderChange(val) {
  currentBearing = parseFloat(val);
  document.getElementById('angleVal').textContent = currentBearing + '°';
  drawPreview(currentBearing);
}

function loadTapak(id) {
  var sel = document.getElementById('tapakSelect');
  var opt = sel.options[sel.selectedIndex];
  centreLat = parseFloat(opt.getAttribute('data-lat'));
  centreLng = parseFloat(opt.getAttribute('data-lng'));
  rowSp     = parseFloat(opt.getAttribute('data-rowsp'));
  colSp     = parseFloat(opt.getAttribute('data-colsp'));
  currentBearing = parseFloat(opt.getAttribute('data-bearing'));

  document.getElementById('bearingSlider').value = currentBearing;
  document.getElementById('angleVal').textContent = currentBearing + '°';

  map.setView([centreLat, centreLng], 19);
  drawPreview(currentBearing);
}

function saveBearing() {
  var sel = document.getElementById('tapakSelect');
  var id_tapak = sel.value;
  var btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.textContent = 'Menyimpan...';

  fetch(BASE + '/modules/lot/save_bearing.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id_tapak=' + encodeURIComponent(id_tapak) + '&bearing=' + encodeURIComponent(currentBearing)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    btn.textContent = '💾 Simpan & Kira Semula Lot';
    if (data.ok) {
      alert('Berjaya! ' + data.jumlah_lot_dikemaskini + ' lot telah dikira semula dengan sudut baharu.');
    } else {
      alert('Ralat: ' + (data.msg || 'Tidak diketahui'));
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    btn.textContent = '💾 Simpan & Kira Semula Lot';
    alert('Ralat rangkaian: ' + err);
  });
}

// Init with first tapak
loadTapak(document.getElementById('tapakSelect').value);
</script>

<?php include '../../includes/footer.php'; ?>
