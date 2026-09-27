<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "
    SELECT b.*, d.nama AS deceased_name, l.kod_lot, l.blok, c.nama AS nama_tapak
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    WHERE b.id_pengebumian = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$burial = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$burial) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tarikh_kebumi = $_POST['tarikh_kebumi'] ?? '';
    $masa_kebumi = $_POST['masa_kebumi'] ?? '';
    $dikebumikan_oleh      = trim($_POST['dikebumikan_oleh'] ?? '');
    $nama_saksi   = trim($_POST['nama_saksi'] ?? '');
    $catatan          = trim($_POST['catatan'] ?? '');

    if (empty($tarikh_kebumi)) {
        $error = 'Sila isi tarikh pengebumian.';
    } elseif (empty($masa_kebumi)) {
        $error = 'Sila isi masa pengebumian.';
    } else {
        $stmt = mysqli_prepare($conn, "
            UPDATE tbl_pengebumian SET tarikh_kebumi=?, masa_kebumi=?, dikebumikan_oleh=?, nama_saksi=?, catatan=?
            WHERE id_pengebumian=?
        ");
        $time_val = $masa_kebumi ?: null;
        mysqli_stmt_bind_param($stmt, 'sssssi', $tarikh_kebumi, $time_val, $dikebumikan_oleh, $nama_saksi, $catatan, $id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: view.php?id=$id&updated=1");
            exit();
        } else {
            $error = 'Ralat semasa mengemaskini rekod.';
        }
    }
}

$page_title = 'Edit Pengebumian';
$topbar_title = 'Edit Rekod Pengebumian';
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Edit Rekod Pengebumian</h2>
  <p><?= htmlspecialchars($burial['deceased_name']) ?> — <?= htmlspecialchars($burial['kod_lot']) ?> — <?= htmlspecialchars($burial['nama_tapak']) ?></p>
</div>

<?php if ($error): ?>
  <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="">

    <!-- Read-only info -->
    <div style="background:var(--bg-body);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.2rem;display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
      <div style="font-size:0.82rem;">
        <div style="color:var(--text-muted);font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Si Mati</div>
        <div style="font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($burial['deceased_name']) ?></div>
      </div>
      <div style="font-size:0.82rem;">
        <div style="color:var(--text-muted);font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">No. Lot</div>
        <div style="font-weight:600;color:var(--clr-7);"><?= htmlspecialchars($burial['kod_lot']) ?> — <?= htmlspecialchars($burial['nama_tapak']) ?></div>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label>Tarikh Pengebumian <span style="color:#dc2626">*</span></label>
        <input type="date" name="tarikh_kebumi" value="<?= htmlspecialchars($_POST['tarikh_kebumi'] ?? $burial['tarikh_kebumi']) ?>" required>
      </div>
      <div class="form-group">
        <label>Masa Pengebumian <span style="color:#dc2626">*</span></label>
        <input type="time" name="masa_kebumi" value="<?= htmlspecialchars($_POST['masa_kebumi'] ?? $burial['masa_kebumi'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Dikebumikan Oleh</label>
        <input type="text" name="dikebumikan_oleh" value="<?= htmlspecialchars($_POST['dikebumikan_oleh'] ?? $burial['dikebumikan_oleh'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Nama Saksi</label>
        <input type="text" name="nama_saksi" value="<?= htmlspecialchars($_POST['nama_saksi'] ?? $burial['nama_saksi'] ?? '') ?>">
      </div>
      <div class="form-group full">
        <label>Nota</label>
        <textarea name="catatan"><?= htmlspecialchars($_POST['catatan'] ?? $burial['catatan'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="form-actions">
      <a href="view.php?id=<?= $id ?>" class="btn-cancel">Batal</a>
      <button type="submit" class="btn-save">Kemaskini Rekod</button>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>
