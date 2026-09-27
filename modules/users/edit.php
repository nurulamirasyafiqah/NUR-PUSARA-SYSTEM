<?php
require_once '../../config/db.php';
requireLogin();
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM tbl_pengguna WHERE id_pengguna = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$user) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penuh = trim($_POST['nama_penuh'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $peranan      = $_POST['peranan'] ?? 'staff';
    $status    = $_POST['status'] ?? 'active';
    $kata_laluan  = $_POST['kata_laluan'] ?? '';

    if (empty($nama_penuh)) {
        $error = 'Sila isi nama penuh.';
    } else {
        if (!empty($kata_laluan)) {
            if (strlen($kata_laluan) < 6) {
                $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
            } else {
                $hashed = password_hash($kata_laluan, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_pengguna SET nama_penuh=?, emel=?, peranan=?, status=?, kata_laluan=? WHERE id_pengguna=?");
                mysqli_stmt_bind_param($stmt, 'sssssi', $nama_penuh, $email, $peranan, $status, $hashed, $id);
            }
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE tbl_pengguna SET nama_penuh=?, emel=?, peranan=?, status=? WHERE id_pengguna=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $nama_penuh, $email, $peranan, $status, $id);
        }

        if (empty($error) && mysqli_stmt_execute($stmt)) {
            header("Location: index.php?updated=1");
            exit();
        } elseif (empty($error)) {
            $error = 'Ralat semasa mengemaskini.';
        }
    }
}

$page_title = 'Edit Pengguna';
$topbar_title = 'Edit Pengguna';
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Edit Pengguna</h2>
  <p><?= htmlspecialchars($user['nama_penuh']) ?></p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Penuh <span style="color:#dc2626">*</span></label>
        <input type="text" name="nama_penuh" value="<?= htmlspecialchars($_POST['nama_penuh'] ?? $user['nama_penuh']) ?>" required>
      </div>
      <div class="form-group">
        <label>Nama Pengguna</label>
        <input type="text" value="<?= htmlspecialchars($user['nama_pengguna']) ?>" disabled style="opacity:0.6;">
        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.3rem;">Nama pengguna tidak boleh diubah</div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['emel'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Peranan</label>
        <select name="peranan" <?= $id == $_SESSION['id_pengguna'] ? 'disabled' : '' ?>>
          <option value="staff" <?= ($user['peranan'] === 'staff') ? 'selected' : '' ?>>Kakitangan</option>
          <option value="admin" <?= ($user['peranan'] === 'admin') ? 'selected' : '' ?>>Pentadbir</option>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" <?= $id == $_SESSION['id_pengguna'] ? 'disabled' : '' ?>>
          <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Aktif</option>
          <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>Tidak Aktif</option>
        </select>
      </div>
    </div>

    <div style="margin-top:1.2rem;padding-top:1.2rem;border-top:1px solid var(--border-color);">
      <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:0.8rem;">Tukar Kata Laluan (kosongkan jika tidak mahu tukar)</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Kata Laluan Baru</label>
          <input type="password" name="kata_laluan" placeholder="Minimum 6 aksara">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="index.php" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Kemaskini</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
