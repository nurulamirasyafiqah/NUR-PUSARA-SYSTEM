<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Rekod Si Mati';
$topbar_title = 'Rekod Si Mati';

$deceased = mysqli_query($conn, "
    SELECT d.*, u.nama_penuh AS created_by_name,
           (SELECT COUNT(*) FROM tbl_pengebumian b WHERE b.id_simati = d.id_simati) AS has_burial
    FROM tbl_simati d
    LEFT JOIN tbl_pengguna u ON d.dicipta_oleh = u.id_pengguna
    ORDER BY d.dicipta_pada DESC
");

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Rekod Si Mati</h2>
  <p>Senarai semua rekod si mati yang telah didaftarkan</p>
</div>

<div class="card">
  <div class="card-header">
    <h3>Semua Rekod</h3>
    <div class="card-actions">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari nama atau IC...">
      </div>
      <a href="add.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Rekod
      </a>
    </div>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama</th>
          <th>No. IC</th>
          <th>Jantina</th>
          <th>Tarikh Kematian</th>
          <th>Sebab Kematian</th>
          <th>Status</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($deceased) > 0): ?>
          <?php $no = 1; while ($row = mysqli_fetch_assoc($deceased)): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
              <td><?= htmlspecialchars($row['no_ic'] ?? '—') ?></td>
              <td><?= $row['jantina'] === 'lelaki' ? '👨 Lelaki' : '👩 Perempuan' ?></td>
              <td><?= date('d M Y', strtotime($row['tarikh_mati'])) ?></td>
              <td><?= htmlspecialchars($row['sebab_mati'] ?? '—') ?></td>
              <td>
                <?php if ($row['has_burial']): ?>
                  <span class="badge badge-diduduki">Telah Dikebumikan</span>
                <?php else: ?>
                  <span class="badge badge-tersedia">Belum Dikebumikan</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="table-actions">
                  <a href="view.php?id=<?= $row['id_simati'] ?>" class="btn-icon" title="Lihat">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <a href="edit.php?id=<?= $row['id_simati'] ?>" class="btn-icon" title="Kemaskini">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <?php if (!$row['has_burial']): ?>
                  <a href="delete.php?id=<?= $row['id_simati'] ?>" class="btn-icon danger btn-delete" title="Padam">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center; color: var(--text-muted); padding: 2.5rem;">Tiada rekod dijumpai</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination">
    <div class="pagination-info">Menunjukkan <?= mysqli_num_rows($deceased) ?> rekod</div>
    <div class="pagination-controls">
      <button class="page-btn">‹</button>
      <button class="page-btn active">1</button>
      <button class="page-btn">›</button>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
