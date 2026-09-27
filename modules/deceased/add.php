<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Tambah Rekod Si Mati';
$topbar_title = 'Tambah Rekod Si Mati';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $no_ic      = trim($_POST['no_ic'] ?? '');
    $tarikh_lahir  = $_POST['tarikh_lahir'] ?? '';
    $tarikh_mati  = $_POST['tarikh_mati'] ?? '';
    $jantina         = $_POST['jantina'] ?? '';
    $kewarganegaraan    = trim($_POST['kewarganegaraan'] ?? 'Malaysia');
    $sebab_mati = trim($_POST['sebab_mati'] ?? '');
    $catatan          = trim($_POST['catatan'] ?? '');

    if (empty($name) || empty($tarikh_mati) || empty($jantina)) {
        $error = 'Sila lengkapkan semua maklumat wajib.';
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO tbl_simati (nama, no_ic, tarikh_lahir, tarikh_mati, jantina, kewarganegaraan, sebab_mati, catatan, dicipta_oleh)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ic_val = $no_ic ?: null;
        $dob_val = $tarikh_lahir ?: null;
        mysqli_stmt_bind_param($stmt, 'ssssssssi',
            $name, $ic_val, $dob_val, $tarikh_mati,
            $jantina, $kewarganegaraan, $sebab_mati, $catatan, $_SESSION['id_pengguna']
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            header("Location: view.php?id=$new_id&added=1");
            exit();
        } else {
            $error = 'Ralat semasa menyimpan rekod. Sila cuba lagi.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Tambah Rekod Si Mati</h2>
  <p>Isikan maklumat si mati dengan lengkap dan tepat</p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">

    <div style="margin-bottom:1.2rem;">
      <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:0.8rem;padding-bottom:0.4rem;border-bottom:1px solid var(--border-color);">
        Maklumat Peribadi
      </div>
      <div class="form-grid">
        <div class="form-group full">
          <label>Nama Penuh <span style="color:#dc2626">*</span></label>
          <input type="text" name="name" placeholder="cth: Mohd Ali bin Ahmad" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>No. Kad Pengenalan</label>
          <input type="text" id="no_ic" name="no_ic" placeholder="cth: 801231-01-5678" value="<?= htmlspecialchars($_POST['no_ic'] ?? '') ?>" oninput="autoFillDOB(this.value)">
        </div>
        <div class="form-group">
          <label>Jantina <span style="color:#dc2626">*</span></label>
          <select name="jantina" required>
            <option value="">-- Pilih Jantina --</option>
            <option value="lelaki" <?= ($_POST['jantina'] ?? '') === 'lelaki' ? 'selected' : '' ?>>Lelaki</option>
            <option value="perempuan" <?= ($_POST['jantina'] ?? '') === 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tarikh Lahir <span style="font-size:0.68rem;color:var(--text-muted);font-weight:400;">(auto-isi dari IC)</span></label>
          <input type="date" id="tarikh_lahir" name="tarikh_lahir" value="<?= htmlspecialchars($_POST['tarikh_lahir'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Kewarganegaraan</label>
          <input type="text" name="kewarganegaraan" value="<?= htmlspecialchars($_POST['kewarganegaraan'] ?? 'Malaysia') ?>">
        </div>
      </div>
    </div>

    <div style="margin-bottom:1.2rem;">
      <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:0.8rem;padding-bottom:0.4rem;border-bottom:1px solid var(--border-color);">
        Maklumat Kematian
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Tarikh Kematian <span style="color:#dc2626">*</span></label>
          <input type="date" name="tarikh_mati" value="<?= htmlspecialchars($_POST['tarikh_mati'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Sebab Kematian</label>
          <input type="text" name="sebab_mati" placeholder="cth: Sakit jantung" value="<?= htmlspecialchars($_POST['sebab_mati'] ?? '') ?>">
        </div>
        <div class="form-group full">
          <label>Nota Tambahan</label>
          <textarea name="catatan" placeholder="Maklumat tambahan jika ada..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="index.php" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Simpan Rekod</button>
    </div>

  </form>
</div>

<script>
/* Auto-fill tarikh lahir dari nombor IC MyKad Malaysia */
/* Format IC: YYMMDD-XX-XXXX — 6 digit pertama = tarikh lahir */
function autoFillDOB(ic) {
  var clean = ic.replace(/-/g, '').replace(/\s/g, '');
  if (clean.length < 6) return;

  var yy = clean.substring(0, 2);
  var mm = clean.substring(2, 4);
  var dd = clean.substring(4, 6);

  /* Determine century: 00-24 = 2000s, 25-99 = 1900s */
  var currentYY = parseInt(new Date().getFullYear().toString().substring(2));
  var yyyy = parseInt(yy) <= currentYY ? '20' + yy : '19' + yy;

  /* Validate month and day */
  var month = parseInt(mm);
  var day   = parseInt(dd);
  if (month < 1 || month > 12 || day < 1 || day > 31) return;

  /* Format as YYYY-MM-DD for date input */
  var dob = yyyy + '-' + mm + '-' + dd;
  var dobField = document.getElementById('tarikh_lahir');
  if (dobField) dobField.value = dob;
}
</script>

<?php include '../../includes/footer.php'; ?>
