<?php
require_once '../../config/db.php';
requireLogin();
requireAdmin();

$page_title = 'Pengurusan Pengguna';
$topbar_title = 'Pengurusan Pengguna';

$users = mysqli_query($conn, "SELECT * FROM tbl_pengguna ORDER BY dicipta_pada DESC");
include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Pengurusan Pengguna</h2>
  <p>Urus akaun kakitangan dan pentadbir sistem</p>
</div>

<div class="card">
  <div class="card-header">
    <h3>Semua Pengguna</h3>
    <div class="card-actions">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari pengguna...">
      </div>
      <a href="add.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Pengguna
      </a>
    </div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>#</th><th>Nama</th><th>Username</th><th>Email</th><th>Peranan</th><th>Status</th><th>Tindakan</th></tr>
      </thead>
      <tbody>
        <?php $no=1; while($u = mysqli_fetch_assoc($users)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:0.5rem;">
              <div class="user-avatar" style="width:32px;height:32px;font-size:0.8rem;"><?= strtoupper(substr($u['nama_penuh'],0,1)) ?></div>
              <strong><?= htmlspecialchars($u['nama_penuh']) ?></strong>
            </div>
          </td>
          <td><?= htmlspecialchars($u['nama_pengguna']) ?></td>
          <td><?= htmlspecialchars($u['emel'] ?? '—') ?></td>
          <td><span class="badge <?= $u['peranan']==='admin' ? 'badge-diduduki' : 'badge-diduduki' ?>"><?= ucfirst($u['peranan']) ?></span></td>
          <td><span class="badge <?= $u['status']==='active' ? 'badge-diduduki' : 'badge-penyelenggaraan' ?>"><?= $u['status']==='active' ? 'Aktif' : 'Tidak Aktif' ?></span></td>
          <td>
            <div class="table-actions">
              <a href="edit.php?id=<?= $u['id_pengguna'] ?>" class="btn-icon" title="Kemaskini">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php if ($u['id_pengguna'] != $_SESSION['id_pengguna']): ?>
              <a href="delete.php?id=<?= $u['id_pengguna'] ?>" class="btn-icon danger btn-delete" title="Padam">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
