<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM tbl_simati WHERE id_simati = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$deceased = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$deceased) { header("Location: index.php"); exit(); }

$error = '';

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
            UPDATE tbl_simati SET nama=?, no_ic=?, tarikh_lahir=?, tarikh_mati=?, jantina=?, kewarganegaraan=?, sebab_mati=?, catatan=?
            WHERE id_simati=?
        ");
        $ic_val = $no_ic ?: null;
        $dob_val = $tarikh_lahir ?: null;
        mysqli_stmt_bind_param($stmt, 'ssssssssi', $name, $ic_val, $dob_val, $tarikh_mati, $jantina, $kewarganegaraan, $sebab_mati, $catatan, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: view.php?id=$id&updated=1");
            exit();
        } else {
            $error = 'Ralat semasa mengemaskini rekod.';
        }
    }
    $deceased = array_merge($deceased, $_POST);
}

$page_title = 'Edit: ' . $deceased['nama'];
$topbar_title = 'Edit Rekod Si Mati';
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Edit Rekod</h2>
  <p><?= htmlspecialchars($deceased['nama']) ?></p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <div style="margin-bottom:1.2rem;">
      <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:0.8rem;padding-bottom:0.4rem;border-bottom:1px solid var(--border-color);">Maklumat Peribadi</div>
      <div class="form-grid">
        <div class="form-group full">
          <label>Nama Penuh <span style="color:#dc2626">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($deceased['nama']) ?>" required>
        </div>
        <div class="form-group">
          <label>No. Kad Pengenalan</label>
          <input type="text" id="no_ic" name="no_ic" value="<?= htmlspecialchars($deceased['no_ic'] ?? '') ?>" oninput="autoFillDOB(this.value)">
        </div>
        <div class="form-group">
          <label>Jantina <span style="color:#dc2626">*</span></label>
          <select name="jantina" required>
            <option value="lelaki" <?= $deceased['jantina'] === 'lelaki' ? 'selected' : '' ?>>Lelaki</option>
            <option value="perempuan" <?= $deceased['jantina'] === 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tarikh Lahir</label>
          <input type="date" id="tarikh_lahir" name="tarikh_lahir" value="<?= htmlspecialchars($deceased['tarikh_lahir'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Kewarganegaraan</label>
          <input type="text" name="kewarganegaraan" value="<?= htmlspecialchars($deceased['kewarganegaraan'] ?? 'Malaysia') ?>">
        </div>
      </div>
    </div>

    <div style="margin-bottom:1.2rem;">
      <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:0.8rem;padding-bottom:0.4rem;border-bottom:1px solid var(--border-color);">Maklumat Kematian</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Tarikh Kematian <span style="color:#dc2626">*</span></label>
          <input type="date" name="tarikh_mati" value="<?= htmlspecialchars($deceased['tarikh_mati']) ?>" required>
        </div>
        <div class="form-group">
          <label>Sebab Kematian</label>
          <input type="text" name="sebab_mati" value="<?= htmlspecialchars($deceased['sebab_mati'] ?? '') ?>">
        </div>
        <div class="form-group full">
          <label>Nota Tambahan</label>
          <textarea name="catatan"><?= htmlspecialchars($deceased['catatan'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="view.php?id=<?= $id ?>" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Kemaskini Rekod</button>
    </div>
  </form>
</div>

<script>
/* Auto-fill tarikh lahir dari nombor IC MyKad Malaysia */
function autoFillDOB(ic) {
  var clean = ic.replace(/-/g, '').replace(/\s/g, '');
  if (clean.length < 6) return;
  var yy = clean.substring(0, 2);
  var mm = clean.substring(2, 4);
  var dd = clean.substring(4, 6);
  var currentYY = parseInt(new Date().getFullYear().toString().substring(2));
  var yyyy = parseInt(yy) <= currentYY ? '20' + yy : '19' + yy;
  var month = parseInt(mm);
  var day   = parseInt(dd);
  if (month < 1 || month > 12 || day < 1 || day > 31) return;
  var dob = yyyy + '-' + mm + '-' + dd;
  var dobField = document.getElementById('tarikh_lahir');
  if (dobField) dobField.value = dob;
}
</script>

<?php include '../../includes/footer.php'; ?>
