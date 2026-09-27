<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Tambah Lot Baru';
$topbar_title = 'Tambah Lot';
$error = '';

$tapak_list = [];
$rows = mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE status='aktif' ORDER BY daerah, nama");
while ($r = mysqli_fetch_assoc($rows)) $tapak_list[] = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tapak = intval($_POST['id_tapak'] ?? 0);
    $fasa     = strtoupper(trim($_POST['fasa'] ?? 'F01'));
    $blok     = strtoupper(trim($_POST['blok'] ?? 'A'));
    $no_baris = intval($_POST['no_baris'] ?? 0);
    $no_lajur = intval($_POST['no_lajur'] ?? 0);
    $status   = $_POST['status'] ?? 'tersedia';
    $catatan  = trim($_POST['catatan'] ?? '');

    if (!$id_tapak || !$no_baris || !$no_lajur) {
        $error = 'Sila pilih tapak dan isi No. Baris / No. Lajur.';
    } elseif (!in_array($status, ['tersedia', 'diduduki', 'penyelenggaraan'])) {
        $error = 'Status tidak sah.';
    } else {
        $tapak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_tapak_kubur WHERE id_tapak=$id_tapak"));

        // Auto-generate next lot number for this tapak+fasa+blok
        $res = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT MAX(no_lot) AS max_lot FROM tbl_lot WHERE id_tapak=$id_tapak AND fasa='$fasa' AND blok='$blok'"
        ));
        $no_lot  = ($res['max_lot'] ?? 0) + 1;
        $kod_lot = $tapak['kod'] . '-' . $fasa . '-' . $blok . '-' . str_pad($no_lot, 4, '0', STR_PAD_LEFT);

        // Check duplicate position
        $dup = mysqli_fetch_row(mysqli_query($conn,
            "SELECT COUNT(*) FROM tbl_lot WHERE id_tapak=$id_tapak AND no_baris=$no_baris AND no_lajur=$no_lajur"
        ))[0];
        if ($dup > 0) {
            $error = "Kedudukan Baris $no_baris, Lajur $no_lajur sudah digunakan oleh lot lain dalam tapak ini.";
        } else {
            // Compute GPS coordinates using the same grid formula as auto-plot
            $centre_lat = (float)$tapak['latitud'];
            $centre_lng = (float)$tapak['longitud'];
            $bearing    = (float)$tapak['sudut_grid'];
            $row_sp     = (float)$tapak['jarak_baris_m'];
            $col_sp     = (float)$tapak['jarak_lajur_m'];
            $earth_r    = 6371000;
            $bear_rad   = deg2rad($bearing);

            $row_m = ($no_baris - 1) * $row_sp;
            $col_m = ($no_lajur - 1) * $col_sp;
            $delta_lat = (($row_m * cos($bear_rad)) + ($col_m * -sin($bear_rad))) / $earth_r;
            $delta_lng = (($row_m * sin($bear_rad)) + ($col_m *  cos($bear_rad))) / ($earth_r * cos(deg2rad($centre_lat)));
            $lat = round($centre_lat + rad2deg($delta_lat), 8);
            $lng = round($centre_lng + rad2deg($delta_lng), 8);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO tbl_lot (id_tapak, kod_lot, fasa, blok, no_lot, no_baris, no_lajur, status, catatan, latitud, longitud, sudut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'isssiiissddd',
                $id_tapak, $kod_lot, $fasa, $blok, $no_lot, $no_baris, $no_lajur, $status, $catatan, $lat, $lng, $bearing
            );
            if (mysqli_stmt_execute($stmt)) {
                mysqli_query($conn, "UPDATE tbl_tapak_kubur SET jumlah_lot = jumlah_lot + 1 WHERE id_tapak=$id_tapak");
                header("Location: index.php?added=1");
                exit();
            } else {
                $error = 'Ralat semasa menyimpan: ' . mysqli_error($conn);
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Tambah Lot Baru</h2>
  <p>Pra-plot satu lot kosong secara manual pada kedudukan grid tertentu (untuk penambahan pukal, guna <code>seed_dummy_lots.php</code>)</p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <div class="form-grid">
      <div class="form-group">
        <label>Tapak Perkuburan <span style="color:#dc2626">*</span></label>
        <select name="id_tapak" required>
          <option value="">-- Pilih Tapak --</option>
          <?php foreach ($tapak_list as $t): ?>
            <option value="<?= $t['id_tapak'] ?>" <?= ($_POST['id_tapak'] ?? '') == $t['id_tapak'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['nama']) ?> (<?= htmlspecialchars($t['daerah']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Fasa</label>
        <select name="fasa">
          <?php foreach (['F01','F02','F03'] as $f): ?>
            <option value="<?= $f ?>" <?= ($_POST['fasa'] ?? 'F01') === $f ? 'selected' : '' ?>>Fasa <?= substr($f,1) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Blok</label>
        <select name="blok">
          <?php foreach (['A','B','C','D','E'] as $b): ?>
            <option value="<?= $b ?>" <?= ($_POST['blok'] ?? 'A') === $b ? 'selected' : '' ?>><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status Awal</label>
        <select name="status">
          <option value="tersedia" <?= ($_POST['status'] ?? 'tersedia') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
          <option value="diduduki" <?= ($_POST['status'] ?? '') === 'diduduki' ? 'selected' : '' ?>>Diduduki</option>
          <option value="penyelenggaraan" <?= ($_POST['status'] ?? '') === 'penyelenggaraan' ? 'selected' : '' ?>>Penyelenggaraan</option>
        </select>
      </div>
      <div class="form-group">
        <label>No. Baris (grid) <span style="color:#dc2626">*</span></label>
        <input type="number" name="no_baris" min="1" placeholder="cth: 12" value="<?= htmlspecialchars($_POST['no_baris'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>No. Lajur (grid) <span style="color:#dc2626">*</span></label>
        <input type="number" name="no_lajur" min="1" placeholder="cth: 1" value="<?= htmlspecialchars($_POST['no_lajur'] ?? '') ?>" required>
      </div>
      <div class="form-group full">
        <label>Nota</label>
        <textarea name="catatan" placeholder="Nota tambahan jika ada..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
      </div>
    </div>
    <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:1rem;">Kod lot dan koordinat GPS akan dijana secara automatik ikut formula grid tapak yang dipilih. No. Lot dalam blok akan disambung dari nombor terakhir sedia ada.</p>
    <div class="form-actions">
      <a href="index.php" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Simpan Lot</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
