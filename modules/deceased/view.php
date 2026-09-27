<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT d.*, u.nama_penuh AS created_by_name FROM tbl_simati d LEFT JOIN tbl_pengguna u ON d.dicipta_oleh = u.id_pengguna WHERE d.id_simati = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$deceased = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$deceased) { header("Location: index.php"); exit(); }

// Get burial info
$burial = mysqli_fetch_assoc(mysqli_query($conn, "SELECT b.*, l.kod_lot, l.blok, c.nama AS nama_tapak FROM tbl_pengebumian b JOIN tbl_lot l ON b.id_lot = l.id_lot JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak WHERE b.id_simati = $id LIMIT 1"));

// Get kin
$kins = mysqli_query($conn, "SELECT * FROM tbl_waris WHERE id_simati = $id");

$page_title = 'Rekod: ' . $deceased['nama'];
$topbar_title = 'Rekod Si Mati';

include '../../includes/header.php';
?>

<?php if (isset($_GET['added'])): ?>
<div style="background:rgba(78,101,61,0.1);border:1px solid var(--clr-4);color:var(--clr-6);padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;">
  ✅ Rekod berjaya ditambah!
</div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
  <div>
    <h2><?= htmlspecialchars($deceased['nama']) ?></h2>
    <p>Rekod didaftarkan oleh <?= htmlspecialchars($deceased['created_by_name'] ?? '—') ?> pada <?= date('d M Y', strtotime($deceased['dicipta_pada'])) ?></p>
  </div>
  <div style="display:flex;gap:0.5rem;">
    <a href="edit.php?id=<?= $id ?>" class="btn-outline">Edit Rekod</a>
    <?php if (!$burial): ?>
    <a href="../burial/add.php?id_simati=<?= $id ?>" class="btn-save" style="padding:0.5rem 1rem;font-size:0.85rem;">+ Daftar Pengebumian</a>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2" style="margin-bottom:1rem;">

  <!-- Personal Info -->
  <div class="card" style="padding:1.4rem;">
    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:1rem;">Maklumat Peribadi</div>
    <div style="display:flex;flex-direction:column;gap:0.8rem;">
      <?php
      $fields = [
        'No. Kad Pengenalan' => $deceased['no_ic'] ?? '—',
        'Jantina' => ucfirst($deceased['jantina']),
        'Tarikh Lahir' => $deceased['tarikh_lahir'] ? date('d M Y', strtotime($deceased['tarikh_lahir'])) : '—',
        'Kewarganegaraan' => $deceased['kewarganegaraan'] ?? '—',
        'Tarikh Kematian' => date('d M Y', strtotime($deceased['tarikh_mati'])),
        'Sebab Kematian' => $deceased['sebab_mati'] ?? '—',
      ];
      foreach ($fields as $label => $val): ?>
        <div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.85rem;padding-bottom:0.6rem;border-bottom:1px solid var(--border-color);">
          <span style="color:var(--text-muted);font-weight:500;"><?= $label ?></span>
          <span style="color:var(--text-primary);text-align:right;"><?= htmlspecialchars($val) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if ($deceased['catatan']): ?>
        <div style="font-size:0.85rem;">
          <div style="color:var(--text-muted);font-weight:500;margin-bottom:0.3rem;">Nota</div>
          <div style="color:var(--text-primary);"><?= htmlspecialchars($deceased['catatan']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Burial Info -->
  <div class="card" style="padding:1.4rem;">
    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:1rem;">Maklumat Pengebumian</div>
    <?php if ($burial): ?>
      <div style="display:flex;flex-direction:column;gap:0.8rem;">
        <?php
        $bfields = [
          'No. Lot' => $burial['kod_lot'],
          'Tapak Perkuburan' => $burial['nama_tapak'],
          'Tarikh Pengebumian' => date('d M Y', strtotime($burial['tarikh_kebumi'])),
          'Masa' => $burial['masa_kebumi'] ? date('h:i A', strtotime($burial['masa_kebumi'])) : '—',
          'Dikebumikan Oleh' => $burial['dikebumikan_oleh'] ?? '—',
          'Saksi' => $burial['nama_saksi'] ?? '—',
        ];
        foreach ($bfields as $label => $val): ?>
          <div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.85rem;padding-bottom:0.6rem;border-bottom:1px solid var(--border-color);">
            <span style="color:var(--text-muted);font-weight:500;"><?= $label ?></span>
            <span style="color:var(--text-primary);text-align:right;font-weight:<?= $label === 'No. Lot' ? '700' : '400' ?>"><?= htmlspecialchars($val) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem;">
        <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>
        Belum ada rekod pengebumian
        <br><br>
        <a href="../burial/add.php?id_simati=<?= $id ?>" class="btn-save" style="padding:0.5rem 1rem;font-size:0.8rem;display:inline-blok;">Daftar Pengebumian</a>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Next of Kin -->
<div class="card">
  <div class="card-header">
    <h3>Waris / Next of Kin</h3>
    <a href="../kin/add.php?id_simati=<?= $id ?>" class="btn-add">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Waris
    </a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Nama</th><th>Hubungan</th><th>No. Telefon</th><th>Email</th><th>Tindakan</th></tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($kins) > 0): ?>
          <?php while ($kin = mysqli_fetch_assoc($kins)): ?>
            <tr>
              <td><strong><?= htmlspecialchars($kin['nama']) ?></strong></td>
              <td><?= htmlspecialchars($kin['hubungan']) ?></td>
              <td><?= htmlspecialchars($kin['telefon'] ?? '—') ?></td>
              <td><?= htmlspecialchars($kin['emel'] ?? '—') ?></td>
              <td>
                <div class="table-actions">
                  <a href="../kin/edit.php?id=<?= $kin['id_waris'] ?>" class="btn-icon" title="Kemaskini">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <a href="../kin/delete.php?id=<?= $kin['id_waris'] ?>&id_simati=<?= $id ?>" class="btn-icon danger btn-delete" title="Padam">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Tiada rekod waris</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top:1rem;">
  <a href="index.php" style="font-size:0.85rem;color:var(--text-muted);">← Kembali ke senarai</a>
</div>

<?php include '../../includes/footer.php'; ?>
