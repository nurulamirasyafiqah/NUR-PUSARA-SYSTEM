<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: " . BASE_URL . "/modules/burial/index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM tbl_waris WHERE id_waris = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$kin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$kin) { header("Location: " . BASE_URL . "/modules/burial/index.php"); exit(); }

$id_pengebumian = intval($_GET['id_pengebumian'] ?? $_POST['id_pengebumian'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $no_ic    = trim($_POST['no_ic'] ?? '');
    $hubungan = trim($_POST['hubungan'] ?? '');
    $telefon        = trim($_POST['telefon'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');

    if (empty($name) || empty($hubungan)) {
        $error = 'Sila isi nama dan hubungan.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE tbl_waris SET nama=?, no_ic=?, hubungan=?, telefon=?, emel=?, alamat=? WHERE id_waris=?");
        $ic_val = $no_ic ?: null;
        mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $ic_val, $hubungan, $telefon, $email, $alamat, $id);
        if (mysqli_stmt_execute($stmt)) {
            if ($id_pengebumian) {
                header("Location: " . BASE_URL . "/modules/burial/view.php?id=$id_pengebumian&kin_updated=1");
            } else {
                header("Location: " . BASE_URL . "/modules/burial/index.php?kin_updated=1");
            }
            exit();
        } else {
            $error = 'Ralat semasa mengemaskini.';
        }
    }
}

$page_title = 'Edit Waris';
$topbar_title = 'Edit Waris';
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Edit Maklumat Waris</h2>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <input type="hidden" name="id_pengebumian" value="<?= $id_pengebumian ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Nama Waris <span style="color:#dc2626">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $kin['nama']) ?>" required>
      </div>
      <div class="form-group">
        <label>Hubungan <span style="color:#dc2626">*</span></label>
        <select name="hubungan" required>
          <?php foreach(['Suami','Isteri','Anak','Bapa','Ibu','Adik-beradik','Cucu','Lain-lain'] as $r): ?>
            <option value="<?= $r ?>" <?= ($kin['hubungan'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>No. IC</label>
        <input type="text" name="no_ic" value="<?= htmlspecialchars($_POST['no_ic'] ?? $kin['no_ic'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>No. Telefon</label>
        <input type="text" name="telefon" value="<?= htmlspecialchars($_POST['telefon'] ?? $kin['telefon'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $kin['emel'] ?? '') ?>">
      </div>
      <div class="form-group full">
        <label>Alamat</label>
        <textarea name="alamat"><?= htmlspecialchars($_POST['alamat'] ?? $kin['alamat'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <a href="<?= BASE_URL ?>/modules/burial/view.php?id=<?= $id_pengebumian ?>" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Kemaskini</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
