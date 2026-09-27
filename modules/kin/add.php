<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Tambah Waris';
$topbar_title = 'Tambah Waris';
$error = '';
$preselected = intval($_GET['id_simati'] ?? 0);
$id_pengebumian = intval($_GET['id_pengebumian'] ?? $_POST['id_pengebumian'] ?? 0);

$deceased_list = mysqli_query($conn, "SELECT id_simati, nama FROM tbl_simati ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_simati  = intval($_POST['id_simati'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $no_ic    = trim($_POST['no_ic'] ?? '');
    $hubungan = trim($_POST['hubungan'] ?? '');
    $telefon        = trim($_POST['telefon'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');

    if (!$id_simati || empty($name) || empty($hubungan)) {
        $error = 'Sila lengkapkan maklumat wajib.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_waris (id_simati, nama, no_ic, hubungan, telefon, emel, alamat) VALUES (?,?,?,?,?,?,?)");
        $ic_val = $no_ic ?: null;
        mysqli_stmt_bind_param($stmt, 'issssss', $id_simati, $name, $ic_val, $hubungan, $telefon, $email, $alamat);
        if (mysqli_stmt_execute($stmt)) {
            if ($id_pengebumian) {
                header("Location: " . BASE_URL . "/modules/burial/view.php?id=$id_pengebumian&kin_added=1");
            } else {
                header("Location: " . BASE_URL . "/modules/burial/index.php?kin_added=1");
            }
            exit();
        } else {
            $error = 'Ralat semasa menyimpan.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Tambah Waris / Next of Kin</h2>
  <p>Daftarkan maklumat waris si mati</p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <input type="hidden" name="id_pengebumian" value="<?= $id_pengebumian ?>">
    <div class="form-group">
      <label>Si Mati <span style="color:#dc2626">*</span></label>
      <select name="id_simati" required>
        <option value="">-- Pilih Si Mati --</option>
        <?php while ($d = mysqli_fetch_assoc($deceased_list)): ?>
          <option value="<?= $d['id_simati'] ?>" <?= ($preselected == $d['id_simati'] || ($_POST['id_simati'] ?? '') == $d['id_simati']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['nama']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label>Nama Waris <span style="color:#dc2626">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Hubungan <span style="color:#dc2626">*</span></label>
        <select name="hubungan" required>
          <option value="">-- Pilih --</option>
          <?php foreach(['Suami','Isteri','Anak','Bapa','Ibu','Adik-beradik','Cucu','Lain-lain'] as $r): ?>
            <option value="<?= $r ?>" <?= ($_POST['hubungan'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>No. IC</label>
        <input type="text" name="no_ic" value="<?= htmlspecialchars($_POST['no_ic'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>No. Telefon</label>
        <input type="text" name="telefon" placeholder="cth: 012-3456789" value="<?= htmlspecialchars($_POST['telefon'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group full">
        <label>Alamat</label>
        <textarea name="alamat"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <a href="javascript:history.back()" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Simpan Waris</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
