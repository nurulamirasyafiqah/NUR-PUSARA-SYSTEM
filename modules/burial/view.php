<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "
    SELECT b.*, d.nama AS deceased_name, d.no_ic, d.jantina, d.tarikh_mati, d.sebab_mati, d.id_simati,
           l.kod_lot, l.blok, l.id_lot, l.latitud, l.longitud,
           c.nama AS nama_tapak, c.daerah,
           u.nama_penuh AS created_by_name
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    LEFT JOIN tbl_pengguna u ON b.dicipta_oleh = u.id_pengguna
    WHERE b.id_pengebumian = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$burial = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$burial) { header("Location: index.php"); exit(); }

// Waris / next of kin for this deceased
$kins = mysqli_query($conn, "SELECT * FROM tbl_waris WHERE id_simati = " . (int)$burial['id_simati']);

$page_title = 'Pengebumian: ' . $burial['deceased_name'];
$topbar_title = 'Rekod Pengebumian';
include '../../includes/header.php';
?>

<?php if (isset($_GET['added'])): ?>
<div style="background:rgba(78,101,61,0.1);border:1px solid var(--clr-4);color:var(--clr-6);padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;">
  ✅ Rekod pengebumian berjaya didaftarkan!
</div>
<?php endif; ?>
<?php if (isset($_GET['kin_added'])): ?>
<div style="background:rgba(78,101,61,0.1);border:1px solid var(--clr-4);color:var(--clr-6);padding:0.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.85rem;">
  ✅ Waris berjaya ditambah!
</div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
  <div>
    <h2><?= htmlspecialchars($burial['deceased_name']) ?></h2>
    <p><?= htmlspecialchars($burial['kod_lot']) ?> — Blok <?= htmlspecialchars($burial['blok']) ?></p>
  </div>
  <div style="display:flex;gap:0.5rem;">
    <a href="edit.php?id=<?= $id ?>" class="btn-outline">Edit</a>
    <a href="<?= BASE_URL ?>/modules/reports/burial_report.php?id_pengebumian=<?= $id ?>" class="btn-save" style="padding:0.5rem 1rem;font-size:0.85rem;" target="_blank">🖨️ Cetak</a>
  </div>
</div>

<div class="grid-2" style="margin-bottom:1rem;">

  <div class="card" style="padding:1.4rem;">
    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:1rem;">Maklumat Si Mati</div>
    <div style="display:flex;flex-direction:column;gap:0.8rem;">
      <?php
      $fields = [
        'Nama' => $burial['deceased_name'],
        'No. IC' => $burial['no_ic'] ?? '—',
        'Jantina' => ucfirst($burial['jantina']),
        'Tarikh Kematian' => date('d M Y', strtotime($burial['tarikh_mati'])),
        'Sebab Kematian' => $burial['sebab_mati'] ?? '—',
      ];
      foreach ($fields as $label => $val): ?>
        <div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.85rem;padding-bottom:0.6rem;border-bottom:1px solid var(--border-color);">
          <span style="color:var(--text-muted);font-weight:500;"><?= $label ?></span>
          <span style="color:var(--text-primary);text-align:right;"><?= htmlspecialchars($val) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="padding:1.4rem;">
    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clr-4);margin-bottom:1rem;">Maklumat Pengebumian</div>

    <!-- Lot Badge -->
    <div style="background:var(--clr-7);color:white;border-radius:var(--radius-md);padding:1rem;text-align:center;margin-bottom:1rem;">
      <div style="font-family:var(--font-display);font-size:2.5rem;font-weight:700;line-height:1;"><?= htmlspecialchars($burial['kod_lot']) ?></div>
      <div style="font-size:0.75rem;opacity:0.7;text-transform:uppercase;letter-spacing:0.08em;">Seksyen <?= htmlspecialchars($burial['blok']) ?></div>
    </div>

    <div style="display:flex;flex-direction:column;gap:0.8rem;">
      <?php
      $bfields = [
        'Tapak Perkuburan' => $burial['nama_tapak'] . ' (' . $burial['daerah'] . ')',
        'Tarikh Pengebumian' => date('d M Y', strtotime($burial['tarikh_kebumi'])),
        'Masa' => $burial['masa_kebumi'] ? date('h:i A', strtotime($burial['masa_kebumi'])) : '—',
        'Dikebumikan Oleh' => $burial['dikebumikan_oleh'] ?? '—',
        'Saksi' => $burial['nama_saksi'] ?? '—',
        'Didaftarkan Oleh' => $burial['created_by_name'] ?? '—',
      ];
      foreach ($bfields as $label => $val): ?>
        <div style="display:flex;justify-content:space-between;gap:1rem;font-size:0.85rem;padding-bottom:0.6rem;border-bottom:1px solid var(--border-color);">
          <span style="color:var(--text-muted);font-weight:500;"><?= $label ?></span>
          <span style="color:var(--text-primary);text-align:right;"><?= htmlspecialchars($val) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($burial['latitud'] && $burial['longitud']): ?>
    <div style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/modules/lot/map.php?focus=<?= $burial['id_lot'] ?>" class="btn-outline" style="font-size:0.78rem;flex:1;text-align:center;">📍 Lihat di Peta Interaktif</a>
      <a href="https://www.google.com/maps?q=<?= $burial['latitud'] ?>,<?= $burial['longitud'] ?>&z=21&hl=ms" target="_blank" class="btn-outline" style="font-size:0.78rem;flex:1;text-align:center;">🗺️ Google Maps</a>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Waris / Next of Kin -->
<div class="card">
  <div class="card-header">
    <h3>Waris / Next of Kin</h3>
    <a href="<?= BASE_URL ?>/modules/kin/add.php?id_simati=<?= $burial['id_simati'] ?>&id_pengebumian=<?= $id ?>" class="btn-add">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Waris
    </a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Nama</th><th>Hubungan</th><th>No. Telefon</th><th>Emel</th><th>Tindakan</th></tr>
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
                  <a href="<?= BASE_URL ?>/modules/kin/edit.php?id=<?= $kin['id_waris'] ?>&id_pengebumian=<?= $id ?>" class="btn-icon" title="Kemaskini">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <a href="<?= BASE_URL ?>/modules/kin/delete.php?id=<?= $kin['id_waris'] ?>&id_pengebumian=<?= $id ?>" class="btn-icon danger btn-delete" title="Padam">
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
