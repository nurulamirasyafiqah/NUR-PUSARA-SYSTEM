<?php
require_once '../../config/db.php';
requireLogin();

$page_title = 'Laporan';
$topbar_title = 'Laporan';

// Filter
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$burials = mysqli_query($conn, "
    SELECT b.id_pengebumian, d.nama AS deceased_name, d.no_ic, d.jantina,
           b.tarikh_kebumi, b.masa_kebumi, b.dikebumikan_oleh,
           l.kod_lot, l.blok, c.nama AS nama_tapak
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    WHERE b.tarikh_kebumi BETWEEN '$from' AND '$to'
    ORDER BY b.tarikh_kebumi DESC
");

$total_burials = mysqli_num_rows($burials);

$lot_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(status='diduduki') AS diduduki,
           SUM(status='penyelenggaraan') AS penyelenggaraan, COUNT(*) AS total
    FROM tbl_lot
"));

include '../../includes/header.php';
?>

<div class="page-header">
  <h2>Laporan Sistem</h2>
  <p>Jana dan cetak laporan pengebumian dan lot perkuburan</p>
</div>

<!-- Filter -->
<div class="card" style="padding:1.2rem;margin-bottom:1.2rem;">
  <form method="GET" action="" style="display:flex;align-items:flex-end;gap:1rem;flex-wrap:wrap;">
    <div class="form-group" style="margin:0;flex:1;min-width:150px;">
      <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.3rem;text-transform:uppercase;letter-spacing:0.05em;">Dari Tarikh</label>
      <input type="date" name="from" value="<?= $from ?>" style="padding:0.6rem 0.8rem;border:1.5px solid var(--border-input);border-radius:var(--radius-sm);background:var(--bg-input);color:var(--text-primary);font-family:var(--font-body);font-size:0.85rem;outline:none;width:100%;">
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:150px;">
      <label style="display:block;font-size:0.78rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.3rem;text-transform:uppercase;letter-spacing:0.05em;">Hingga Tarikh</label>
      <input type="date" name="to" value="<?= $to ?>" style="padding:0.6rem 0.8rem;border:1.5px solid var(--border-input);border-radius:var(--radius-sm);background:var(--bg-input);color:var(--text-primary);font-family:var(--font-body);font-size:0.85rem;outline:none;width:100%;">
    </div>
    <button type="submit" class="btn-save" style="padding:0.6rem 1.2rem;">Tapis</button>
    <a href="export_pdf.php?from=<?= $from ?>&to=<?= $to ?>" class="btn-outline" target="_blank" style="padding:0.6rem 1.2rem;">🖨️ Cetak PDF</a>
  </form>
</div>

<!-- Lot Stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem;margin-bottom:1.2rem;">
  <?php
  $lstat = [
    ['label'=>'Jumlah Lot','val'=>$lot_stats['total'],'color'=>'var(--clr-1)'],
    ['label'=>'Lot Diduduki','val'=>$lot_stats['diduduki'],'color'=>'var(--clr-7)'],
    ['label'=>'Penyelenggaraan','val'=>$lot_stats['penyelenggaraan'],'color'=>'#d97706'],
  ];
  foreach ($lstat as $s): ?>
  <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:1rem;text-align:center;">
    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:<?= $s['color'] ?>;"><?= $s['val'] ?></div>
    <div style="font-size:0.75rem;color:var(--text-muted);"><?= $s['label'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Burial Report Table -->
<div class="card">
  <div class="card-header">
    <h3>Laporan Pengebumian: <?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></h3>
    <span class="badge badge-diduduki"><?= $total_burials ?> rekod</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Si Mati</th>
          <th>No. IC</th>
          <th>Jantina</th>
          <th>Tarikh Pengebumian</th>
          <th>No. Lot</th>
          <th>Seksyen</th>
          <th>Dikebumikan Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        mysqli_data_seek($burials, 0);
        while ($row = mysqli_fetch_assoc($burials)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($row['deceased_name']) ?></strong></td>
            <td><?= htmlspecialchars($row['no_ic'] ?? '—') ?></td>
            <td><?= $row['jantina'] === 'lelaki' ? 'Lelaki' : 'Perempuan' ?></td>
            <td><?= date('d M Y', strtotime($row['tarikh_kebumi'])) ?></td>
            <td><span class="badge badge-diduduki"><?= htmlspecialchars($row['kod_lot']) ?></span></td>
            <td><?= htmlspecialchars($row['nama_tapak']) ?></td>
            <td><?= htmlspecialchars($row['dikebumikan_oleh'] ?? '—') ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if ($total_burials === 0): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">Tiada rekod dalam tempoh ini</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
