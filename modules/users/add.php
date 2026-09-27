<?php
require_once '../../config/db.php';
requireLogin();
requireAdmin();

$page_title = 'Tambah Pengguna';
$topbar_title = 'Tambah Pengguna';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penuh = trim($_POST['nama_penuh'] ?? '');
    $nama_pengguna  = trim($_POST['nama_pengguna'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $kata_laluan  = $_POST['kata_laluan'] ?? '';
    $peranan      = $_POST['peranan'] ?? 'staff';

    if (empty($nama_penuh) || empty($nama_pengguna) || empty($kata_laluan)) {
        $error = 'Sila lengkapkan semua maklumat wajib.';
    } elseif (strlen($kata_laluan) < 6) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
    } else {
        $check = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_pengguna WHERE nama_pengguna='".mysqli_real_escape_string($conn,$nama_pengguna)."'"))[0];
        if ($check > 0) {
            $error = 'Nama pengguna sudah digunakan.';
        } else {
            $hashed = password_hash($kata_laluan, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = mysqli_prepare($conn, "INSERT INTO tbl_pengguna (nama_penuh, nama_pengguna, emel, kata_laluan, peranan) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssss', $nama_penuh, $nama_pengguna, $email, $hashed, $peranan);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: index.php?added=1");
                exit();
            } else {
                $error = 'Ralat semasa menyimpan.';
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Tambah Pengguna Baru</h2>
  <p>Cipta akaun untuk kakitangan atau pentadbir baharu</p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Penuh <span style="color:#dc2626">*</span></label>
        <input type="text" name="nama_penuh" value="<?= htmlspecialchars($_POST['nama_penuh'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Nama Pengguna <span style="color:#dc2626">*</span></label>
        <input type="text" name="nama_pengguna" value="<?= htmlspecialchars($_POST['nama_pengguna'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Peranan</label>
        <select name="peranan">
          <option value="staff" <?= ($_POST['peranan'] ?? '') === 'staff' ? 'selected' : '' ?>>Kakitangan</option>
          <option value="admin" <?= ($_POST['peranan'] ?? '') === 'admin' ? 'selected' : '' ?>>Pentadbir</option>
        </select>
      </div>
      <div class="form-group full">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Kata Laluan <span style="color:#dc2626">*</span></label>
        <input type="password" name="kata_laluan" required>
      </div>
      <div class="form-group">
        <label>Sahkan Kata Laluan</label>
        <input type="password" name="password_confirm">
      </div>
    </div>
    <div class="form-actions">
      <a href="index.php" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Cipta Akaun</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
