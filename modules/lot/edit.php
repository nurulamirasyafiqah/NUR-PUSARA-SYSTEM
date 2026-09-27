<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM tbl_lot WHERE id_lot = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lot = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$lot) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $catatan  = trim($_POST['catatan'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE tbl_lot SET status=?, catatan=? WHERE id_lot=?");
    mysqli_stmt_bind_param($stmt, 'ssi', $status, $catatan, $id);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?updated=1");
        exit();
    } else {
        $error = 'Ralat semasa mengemaskini lot.';
    }
}

$page_title = 'Edit Lot ' . $lot['kod_lot'];
$topbar_title = 'Edit Lot';
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Edit Lot <?= htmlspecialchars($lot['kod_lot']) ?></h2>
  <p>Blok <?= htmlspecialchars($lot['blok']) ?></p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">
    <div class="form-group">
      <label>No. Lot</label>
      <input type="text" value="<?= htmlspecialchars($lot['kod_lot']) ?>" disabled style="opacity:0.6;">
    </div>
    <div class="form-group">
      <label>Seksyen</label>
      <input type="text" value="Blok <?= htmlspecialchars($lot['blok']) ?>" disabled style="opacity:0.6;">
    </div>
    <div class="form-group">
      <label>Status <span style="color:#dc2626">*</span></label>
      <select name="status" required>
        <option value="diduduki" <?= $lot['status'] === 'diduduki' ? 'selected' : '' ?>>Diduduki</option>
        <option value="penyelenggaraan" <?= $lot['status'] === 'penyelenggaraan' ? 'selected' : '' ?>>Penyelenggaraan</option>
      </select>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">* Status "Diduduki" ditetapkan secara automatik apabila pengebumian didaftarkan</div>
    </div>
    <div class="form-group">
      <label>Nota</label>
      <textarea name="catatan"><?= htmlspecialchars($lot['catatan'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <a href="index.php" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Kemaskini Lot</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
