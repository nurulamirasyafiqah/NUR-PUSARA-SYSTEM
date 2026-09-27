<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Rekod Pengebumian';
$topbar_title = 'Rekod Pengebumian';

// ── Search & pagination ──
$search   = trim($_GET['q'] ?? '');
$per_page = 20;
$page_num = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page_num - 1) * $per_page;

$where = '';
$params = [];
$types  = '';
if ($search !== '') {
    $where = "WHERE d.nama LIKE ? OR l.kod_lot LIKE ? OR d.no_ic LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
    $types  = 'sss';
}

// Total count (for pagination)
$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    $where
";
if ($params) {
    $cstmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($cstmt, $types, ...$params);
    mysqli_stmt_execute($cstmt);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt))['total'];
} else {
    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, $count_sql))['total'];
}
$total_pages = max(1, ceil($total_rows / $per_page));
$page_num = min($page_num, $total_pages);
$offset = ($page_num - 1) * $per_page;

// Fetch page of burials
$sql = "
    SELECT b.id_pengebumian, d.nama AS deceased_name, b.tarikh_kebumi, b.masa_kebumi,
           l.kod_lot, l.blok, l.no_lot, c.nama AS nama_tapak, b.dikebumikan_oleh, b.dicipta_pada
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    $where
    ORDER BY b.tarikh_kebumi DESC
    LIMIT $per_page OFFSET $offset
";
if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $burials = mysqli_stmt_get_result($stmt);
} else {
    $burials = mysqli_query($conn, $sql);
}

function burial_page_url($p, $search) {
    $q = ['page' => $p];
    if ($search !== '') $q['q'] = $search;
    return '?' . http_build_query($q);
}

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Rekod Pengebumian</h2>
  <p>Senarai semua rekod pengebumian dalam sistem</p>
</div>

<div class="card">
  <div class="card-header">
    <h3>Semua Pengebumian</h3>
    <div class="card-actions">
      <form method="GET" action="" class="search-box" style="display:flex;align-items:center;">
        <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;display:flex;align-items:center;" aria-label="Cari">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
        <input type="text" name="q" placeholder="Cari nama, IC, atau kod lot..." value="<?= htmlspecialchars($search) ?>">
      </form>
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
          <th>Nama Si Mati</th>
          <th>Tarikh Pengebumian</th>
          <th>Masa</th>
          <th>No. Lot</th>
          <th>Seksyen</th>
          <th>Dikebumikan Oleh</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($burials) > 0): ?>
          <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($burials)): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><strong><?= htmlspecialchars($row['deceased_name']) ?></strong></td>
              <td><?= date('d M Y', strtotime($row['tarikh_kebumi'])) ?></td>
              <td><?= $row['masa_kebumi'] ? date('h:i A', strtotime($row['masa_kebumi'])) : '—' ?></td>
              <td><span class="badge badge-diduduki"><?= htmlspecialchars($row['blok'] . '-' . str_pad($row['no_lot'], 4, '0', STR_PAD_LEFT)) ?></span></td>
              <td><?= htmlspecialchars($row['nama_tapak'] ?? '—') ?></td>
              <td><?= htmlspecialchars($row['dikebumikan_oleh'] ?? '—') ?></td>
              <td>
                <div class="table-actions">
                  <a href="view.php?id=<?= $row['id_pengebumian'] ?>" class="btn-icon" title="Lihat">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <a href="edit.php?id=<?= $row['id_pengebumian'] ?>" class="btn-icon" title="Kemaskini">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <a href="delete.php?id=<?= $row['id_pengebumian'] ?>" class="btn-icon danger btn-delete" title="Padam">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center; color: var(--text-muted); padding: 2.5rem;">Tiada rekod pengebumian dijumpai</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination">
    <div class="pagination-info">
      Menunjukkan <?= min($per_page, $total_rows - $offset) ?> daripada <?= $total_rows ?> rekod
      (Halaman <?= $page_num ?> / <?= $total_pages ?>)
    </div>
    <div class="pagination-controls">
      <a href="<?= burial_page_url(max(1, $page_num - 1), $search) ?>" class="page-btn <?= $page_num <= 1 ? 'disabled' : '' ?>">‹</a>
      <?php
        $range_start = max(1, $page_num - 2);
        $range_end   = min($total_pages, $page_num + 2);
        for ($p = $range_start; $p <= $range_end; $p++):
      ?>
        <a href="<?= burial_page_url($p, $search) ?>" class="page-btn <?= $p === $page_num ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="<?= burial_page_url(min($total_pages, $page_num + 1), $search) ?>" class="page-btn <?= $page_num >= $total_pages ? 'disabled' : '' ?>">›</a>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
