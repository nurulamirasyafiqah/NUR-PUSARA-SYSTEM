<?php
require_once '../../config/db.php';
requireLogin();

$page_title   = 'Daftar Pengebumian Baru';
$topbar_title = 'Daftar Pengebumian Baru';

$errors  = [];
$success = false;

// Load tapak kubur for dropdown
$tapak_list = [];
$tapak_rows = mysqli_query($conn, "SELECT id_tapak, kod, nama, daerah FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($r = mysqli_fetch_assoc($tapak_rows)) $tapak_list[] = $r;

// Load pra-plot lot kosong (status='tersedia') untuk dropdown pilihan lot
$tersedia_lots = [];
$tersedia_rows = mysqli_query($conn, "SELECT id_lot, id_tapak, kod_lot, fasa, blok, no_baris, no_lajur FROM tbl_lot WHERE status='tersedia' ORDER BY id_tapak, no_baris, no_lajur");
while ($r = mysqli_fetch_assoc($tersedia_rows)) $tersedia_lots[] = $r;

// ── Handle form submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Bahagian 1: Maklumat Si Mati ---
    $nama         = trim($_POST['nama']        ?? '');
    $no_ic        = trim($_POST['no_ic']       ?? '');
    $tarikh_lahir = $_POST['tarikh_lahir']     ?? '';
    $tarikh_mati  = $_POST['tarikh_mati']      ?? '';
    $jantina      = $_POST['jantina']          ?? '';
    $sebab_mati   = trim($_POST['sebab_mati']  ?? '');
    $catatan_sm   = trim($_POST['catatan_sm']  ?? '');

    // --- Bahagian 2: Lokasi Pengebumian ---
    $id_tapak     = intval($_POST['id_tapak']  ?? 0);
    $blok         = strtoupper(trim($_POST['blok']   ?? 'A'));
    $fasa         = strtoupper(trim($_POST['fasa']   ?? 'F01'));
    $id_lot_pilihan = intval($_POST['id_lot_pilihan'] ?? 0);

    // --- Bahagian 3: Butiran Pengebumian ---
    $tarikh_kebumi      = $_POST['tarikh_kebumi']     ?? '';
    $masa_kebumi        = $_POST['masa_kebumi']        ?? '';
    $dikebumikan_oleh   = trim($_POST['dikebumikan_oleh'] ?? $_SESSION['nama_penuh']);
    $nama_saksi         = trim($_POST['nama_saksi']   ?? '');
    $catatan_pb         = trim($_POST['catatan_pb']   ?? '');

    // Validation
    if (!$nama)         $errors[] = 'Nama si mati wajib diisi.';
    if (!$tarikh_mati)  $errors[] = 'Tarikh kematian wajib diisi.';
    if (!$jantina)      $errors[] = 'Jantina wajib dipilih.';
    if (!$id_tapak)     $errors[] = 'Tapak perkuburan wajib dipilih.';
    if (!$tarikh_kebumi) $errors[] = 'Tarikh pengebumian wajib diisi.';
    if (!$masa_kebumi)  $errors[] = 'Masa pengebumian wajib diisi.';

    // Check duplicate IC
    if ($no_ic) {
        $chk = mysqli_prepare($conn, "SELECT id_simati FROM tbl_simati WHERE no_ic = ?");
        mysqli_stmt_bind_param($chk, 's', $no_ic);
        mysqli_stmt_execute($chk);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
            $errors[] = 'Nombor IC ini sudah wujud dalam sistem.';
        }
    }

    // Validate chosen empty lot (if any) is still available and matches the selected tapak
    $lot_terpilih = null;
    if ($id_lot_pilihan > 0) {
        $lchk = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM tbl_lot WHERE id_lot=$id_lot_pilihan AND status='tersedia' FOR UPDATE"
        ));
        if (!$lchk) {
            $errors[] = 'Lot yang dipilih sudah tidak tersedia (mungkin baru sahaja diambil). Sila pilih lot lain atau guna auto-plot.';
        } elseif ($id_tapak && (int)$lchk['id_tapak'] !== $id_tapak) {
            $errors[] = 'Lot yang dipilih bukan dari tapak perkuburan yang sama.';
        } else {
            $lot_terpilih = $lchk;
        }
    }

    if (empty($errors)) {

        // Get tapak info for auto lot code + coordinates
        $tapak = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM tbl_tapak_kubur WHERE id_tapak=$id_tapak"
        ));

        if ($lot_terpilih) {
            // Guna lot pra-plot yang dipilih — tiada lot baru dicipta
            $kod_lot = $lot_terpilih['kod_lot'];
            $new_lat = $lot_terpilih['latitud'];
            $new_lng = $lot_terpilih['longitud'];
            $bearing = $lot_terpilih['sudut'];
        } else {
            // Auto-generate next lot number for this tapak+fasa+blok
            $res = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT MAX(no_lot) AS max_lot FROM tbl_lot
                 WHERE id_tapak=$id_tapak AND fasa='$fasa' AND blok='$blok'"
            ));
            $next_no = ($res['max_lot'] ?? 0) + 1;
            $kod_lot = $tapak['kod'] . '-' . $fasa . '-' . $blok . '-' . str_pad($next_no, 4, '0', STR_PAD_LEFT);

            // Auto-calculate GPS coordinates for new lot
            // Based on tapak centre + grid bearing + row/col offsets
            $centre_lat = (float)$tapak['latitud'];
            $centre_lng = (float)$tapak['longitud'];
            $bearing    = (float)$tapak['sudut_grid'];
            $row_sp     = (float)$tapak['jarak_baris_m'];
            $col_sp     = (float)$tapak['jarak_lajur_m'];

            // Count existing rows/cols for this tapak to get position
            $row_res = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT MAX(no_baris) AS max_row, MAX(no_lajur) AS max_col FROM tbl_lot WHERE id_tapak=$id_tapak"
            ));
            $new_row = ($row_res['max_row'] ?? 0) + 1;
            $new_col = 1;

            // Convert metres offset to lat/lng delta using bearing
            $earth_r   = 6371000;
            $bear_rad  = deg2rad($bearing);
            $row_m     = ($new_row - 1) * $row_sp;
            $col_m     = ($new_col - 1) * $col_sp;

            $delta_lat = (($row_m * cos($bear_rad)) + ($col_m * -sin($bear_rad))) / $earth_r;
            $delta_lng = (($row_m * sin($bear_rad)) + ($col_m *  cos($bear_rad))) / ($earth_r * cos(deg2rad($centre_lat)));

            $new_lat = round($centre_lat + rad2deg($delta_lat), 8);
            $new_lng = round($centre_lng + rad2deg($delta_lng), 8);
        }

        // --- Transaction: insert tbl_simati → tbl_lot → tbl_pengebumian ---
        mysqli_begin_transaction($conn);
        try {
            // 1. Insert si mati
            $stmt = mysqli_prepare($conn,
                "INSERT INTO tbl_simati (nama, no_ic, tarikh_lahir, tarikh_mati, jantina, sebab_mati, catatan, dicipta_oleh)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ic_val  = $no_ic        ?: null;
            $dob_val = $tarikh_lahir ?: null;
            $dod_val = $tarikh_mati;
            mysqli_stmt_bind_param($stmt, 'sssssssi',
                $nama, $ic_val, $dob_val, $dod_val,
                $jantina, $sebab_mati, $catatan_sm, $_SESSION['id_pengguna']
            );
            mysqli_stmt_execute($stmt);
            $id_simati = mysqli_insert_id($conn);

            // 2. Lot: guna lot pra-plot sedia ada, atau cipta lot baru
            if ($lot_terpilih) {
                $stmt2 = mysqli_prepare($conn, "UPDATE tbl_lot SET status='diduduki' WHERE id_lot=? AND status='tersedia'");
                mysqli_stmt_bind_param($stmt2, 'i', $id_lot_pilihan);
                mysqli_stmt_execute($stmt2);
                if (mysqli_stmt_affected_rows($stmt2) !== 1) {
                    throw new Exception('Lot yang dipilih telah diambil oleh rekod lain. Sila cuba semula.');
                }
                $id_lot = $id_lot_pilihan;
            } else {
                $stmt2 = mysqli_prepare($conn,
                    "INSERT INTO tbl_lot (id_tapak, kod_lot, fasa, blok, no_lot, no_baris, no_lajur, status, latitud, longitud, sudut)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'diduduki', ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt2, 'isssiiiddd',
                    $id_tapak, $kod_lot, $fasa, $blok, $next_no,
                    $new_row, $new_col, $new_lat, $new_lng, $bearing
                );
                mysqli_stmt_execute($stmt2);
                $id_lot = mysqli_insert_id($conn);
            }

            // 3. Insert pengebumian
            $masa_val = $masa_kebumi ?: null;
            $stmt3 = mysqli_prepare($conn,
                "INSERT INTO tbl_pengebumian (id_simati, id_lot, tarikh_kebumi, masa_kebumi, dikebumikan_oleh, nama_saksi, catatan, dicipta_oleh)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt3, 'iisssssi',
                $id_simati, $id_lot, $tarikh_kebumi, $masa_val,
                $dikebumikan_oleh, $nama_saksi, $catatan_pb, $_SESSION['id_pengguna']
            );
            mysqli_stmt_execute($stmt3);

            // 4. Update jumlah_lot tapak (hanya jika lot BARU dicipta, bukan lot pra-plot)
            if (!$lot_terpilih) {
                mysqli_query($conn, "UPDATE tbl_tapak_kubur SET jumlah_lot = jumlah_lot + 1 WHERE id_tapak=$id_tapak");
            }

            mysqli_commit($conn);
            $success   = true;
            $id_pengebumian = mysqli_insert_id($conn);

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $errors[] = 'Ralat sistem: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
  <span>✅ Rekod pengebumian berjaya didaftarkan. Lot <strong><?= htmlspecialchars($kod_lot) ?></strong> telah <?= $lot_terpilih ? 'diisi' : 'diplotkan' ?> pada peta.</span>
  <div style="display:flex;gap:0.5rem;">
    <a href="index.php" class="btn-outline">Senarai Pengebumian</a>
    <a href="add.php" class="btn-save">+ Daftar Baru</a>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin:1.5rem;">
  <?php foreach ($errors as $e): ?><div>⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page-content" style="max-width:860px;">

<form method="POST" class="card" style="padding:2rem;">

  <!-- ══ Bahagian 1: Maklumat Si Mati ══ -->
  <div class="section-divider" style="margin-bottom:1.5rem;">
    <span class="section-divider-label">Bahagian 1 — Maklumat Si Mati</span>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label>Nama Penuh <span class="required">*</span></label>
      <input type="text" name="nama" placeholder="Nama penuh si mati" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>No. Kad Pengenalan (IC)</label>
      <input type="text" id="no_ic" name="no_ic" placeholder="cth: 801231-01-5678"
        value="<?= htmlspecialchars($_POST['no_ic'] ?? '') ?>"
        oninput="autoFillDOB(this.value)">
    </div>
    <div class="form-group">
      <label>Tarikh Lahir <small style="font-weight:400;color:var(--text-muted);">(auto-isi dari IC)</small></label>
      <input type="date" id="tarikh_lahir" name="tarikh_lahir" value="<?= htmlspecialchars($_POST['tarikh_lahir'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Tarikh Kematian <span class="required">*</span></label>
      <input type="date" name="tarikh_mati" value="<?= htmlspecialchars($_POST['tarikh_mati'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Jantina <span class="required">*</span></label>
      <select name="jantina" required>
        <option value="">-- Pilih Jantina --</option>
        <option value="lelaki"    <?= ($_POST['jantina'] ?? '') === 'lelaki'    ? 'selected' : '' ?>>Lelaki</option>
        <option value="perempuan" <?= ($_POST['jantina'] ?? '') === 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
      </select>
    </div>
    <div class="form-group">
      <label>Sebab Kematian</label>
      <input type="text" name="sebab_mati" placeholder="Contoh: Sakit jantung" value="<?= htmlspecialchars($_POST['sebab_mati'] ?? '') ?>">
    </div>
    <div class="form-group" style="grid-column:1/-1;">
      <label>Catatan Tambahan (Si Mati)</label>
      <textarea name="catatan_sm" rows="2" placeholder="Maklumat tambahan jika ada..."><?= htmlspecialchars($_POST['catatan_sm'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- ══ Bahagian 2: Lokasi Pengebumian ══ -->
  <div class="section-divider" style="margin:1.5rem 0;">
    <span class="section-divider-label">Bahagian 2 — Lokasi Pengebumian</span>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label>Tapak Perkuburan <span class="required">*</span></label>
      <select name="id_tapak" id="id_tapak" required onchange="onTapakChange()">
        <option value="">-- Pilih Tapak Perkuburan --</option>
        <?php foreach ($tapak_list as $t): ?>
          <option value="<?= $t['id_tapak'] ?>"
            data-kod="<?= htmlspecialchars($t['kod']) ?>"
            <?= ($_POST['id_tapak'] ?? '') == $t['id_tapak'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nama']) ?> (<?= htmlspecialchars($t['daerah']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="grid-column:1/-1;">
      <label>Pilih Lot <span class="required">*</span></label>
      <select name="id_lot_pilihan" id="id_lot_pilihan" onchange="onLotChange()">
        <option value="">— Auto-plot lot baharu (tiada lot tersedia dipilih) —</option>
        <?php foreach ($tersedia_lots as $l): ?>
          <option value="<?= $l['id_lot'] ?>" data-tapak="<?= $l['id_tapak'] ?>"
            <?= ($_POST['id_lot_pilihan'] ?? '') == $l['id_lot'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($l['kod_lot']) ?> — Baris <?= $l['no_baris'] ?>, Lajur <?= $l['no_lajur'] ?> (Tersedia)
          </option>
        <?php endforeach; ?>
      </select>
      <small style="color:var(--text-muted);font-size:0.72rem;">Senarai ini akan ditapis ikut tapak yang dipilih di atas. Pilih lot tersedia dari peta, atau biarkan "Auto-plot" untuk cipta lot baharu secara automatik.</small>
    </div>
    <div class="form-group">
      <label>Fasa <small style="font-weight:400;color:var(--text-muted);">(untuk auto-plot sahaja)</small></label>
      <select name="fasa" id="fasa" onchange="updateLotPreview()">
        <option value="F01" <?= ($_POST['fasa'] ?? 'F01') === 'F01' ? 'selected' : '' ?>>Fasa 01</option>
        <option value="F02" <?= ($_POST['fasa'] ?? '') === 'F02' ? 'selected' : '' ?>>Fasa 02</option>
        <option value="F03" <?= ($_POST['fasa'] ?? '') === 'F03' ? 'selected' : '' ?>>Fasa 03</option>
      </select>
    </div>
    <div class="form-group">
      <label>Blok <small style="font-weight:400;color:var(--text-muted);">(untuk auto-plot sahaja)</small></label>
      <select name="blok" id="blok" onchange="updateLotPreview()">
        <?php foreach (['A','B','C','D','E'] as $b): ?>
          <option value="<?= $b ?>" <?= ($_POST['blok'] ?? 'A') === $b ? 'selected' : '' ?>><?= $b ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Pratonton Kod Lot (auto-jana)</label>
      <input type="text" id="lot_preview" readonly
        style="background:var(--bg-body);font-family:monospace;color:var(--clr-1);font-weight:700;"
        placeholder="Pilih tapak, fasa dan blok dulu...">
      <small style="color:var(--text-muted);font-size:0.72rem;">Hanya terpakai jika "Auto-plot" dipilih di atas</small>
    </div>
  </div>

  <!-- ══ Bahagian 3: Butiran Pengebumian ══ -->
  <div class="section-divider" style="margin:1.5rem 0;">
    <span class="section-divider-label">Bahagian 3 — Butiran Pengebumian</span>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label>Tarikh Pengebumian <span class="required">*</span></label>
      <input type="date" name="tarikh_kebumi" value="<?= htmlspecialchars($_POST['tarikh_kebumi'] ?? date('Y-m-d')) ?>" required>
    </div>
    <div class="form-group">
      <label>Masa Pengebumian <span class="required">*</span></label>
      <input type="time" name="masa_kebumi" value="<?= htmlspecialchars($_POST['masa_kebumi'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Dikebumikan Oleh</label>
      <input type="text" name="dikebumikan_oleh"
        value="<?= htmlspecialchars($_POST['dikebumikan_oleh'] ?? $_SESSION['nama_penuh']) ?>"
        placeholder="Nama pegawai yang menguruskan">
    </div>
    <div class="form-group">
      <label>Nama Saksi</label>
      <input type="text" name="nama_saksi" placeholder="Nama saksi pengebumian"
        value="<?= htmlspecialchars($_POST['nama_saksi'] ?? '') ?>">
    </div>
    <div class="form-group" style="grid-column:1/-1;">
      <label>Catatan (Pengebumian)</label>
      <textarea name="catatan_pb" rows="2" placeholder="Maklumat tambahan pengebumian..."><?= htmlspecialchars($_POST['catatan_pb'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="display:flex;gap:0.75rem;margin-top:1rem;">
    <button type="submit" class="btn-save">💾 Simpan &amp; Daftarkan</button>
    <a href="index.php" class="btn-outline">Batal</a>
  </div>

</form>

</div>

<script>
/* IC auto-fill tarikh lahir */
function autoFillDOB(ic) {
  var clean = ic.replace(/-/g, '').replace(/\s/g, '');
  if (clean.length < 6) return;
  var yy = clean.substring(0, 2);
  var mm = clean.substring(2, 4);
  var dd = clean.substring(4, 6);
  var curYY = parseInt(new Date().getFullYear().toString().substring(2));
  var yyyy  = parseInt(yy) <= curYY ? '20' + yy : '19' + yy;
  if (parseInt(mm) < 1 || parseInt(mm) > 12) return;
  if (parseInt(dd) < 1 || parseInt(dd) > 31) return;
  var dob = document.getElementById('tarikh_lahir');
  if (dob) dob.value = yyyy + '-' + mm + '-' + dd;
}

/* Filter "Pilih Lot" dropdown so only lots from the selected tapak show */
function onTapakChange() {
  var tapakSel = document.getElementById('id_tapak');
  var tapakId  = tapakSel.value;
  var lotSel   = document.getElementById('id_lot_pilihan');
  var opts     = lotSel.querySelectorAll('option[data-tapak]');
  opts.forEach(function(opt) {
    var match = opt.getAttribute('data-tapak') === tapakId;
    opt.hidden = !match;
    opt.disabled = !match;
  });
  // Reset selection if the currently chosen lot no longer matches
  var current = lotSel.options[lotSel.selectedIndex];
  if (current && current.hasAttribute('data-tapak') && current.getAttribute('data-tapak') !== tapakId) {
    lotSel.value = '';
  }
  onLotChange();
  updateLotPreview();
}

/* Disable fasa/blok when a specific pre-plotted lot is picked (auto-plot fields become irrelevant) */
function onLotChange() {
  var lotSel  = document.getElementById('id_lot_pilihan');
  var isAuto  = lotSel.value === '';
  document.getElementById('fasa').disabled = !isAuto;
  document.getElementById('blok').disabled = !isAuto;
}

/* Live lot code preview */
function updateLotPreview() {
  var tapakSel = document.getElementById('id_tapak');
  var fasa     = document.getElementById('fasa').value;
  var blok     = document.getElementById('blok').value;
  var preview  = document.getElementById('lot_preview');
  var opt      = tapakSel.options[tapakSel.selectedIndex];
  var kod      = opt ? opt.getAttribute('data-kod') : '';
  if (kod && fasa && blok) {
    preview.value = kod + '-' + fasa + '-' + blok + '-XXXX';
    preview.style.color = 'var(--clr-1)';
  } else {
    preview.value = '';
  }
}

/* Run on load if values exist (e.g. form re-submit after error) */
onTapakChange();
updateLotPreview();
</script>

<?php include '../../includes/footer.php'; ?>
