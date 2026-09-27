<?php
require_once '../../config/db.php';
requireLogin();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$burials = mysqli_query($conn, "
    SELECT b.id_pengebumian, d.nama AS deceased_name, d.no_ic, d.jantina,
           b.tarikh_kebumi, b.dikebumikan_oleh, l.kod_lot, l.blok, c.nama AS nama_tapak
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    WHERE b.tarikh_kebumi BETWEEN '$from' AND '$to'
    ORDER BY b.tarikh_kebumi DESC
");

$lot_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(status='diduduki') AS diduduki, COUNT(*) AS total FROM tbl_lot
"));
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Laporan Pengebumian — NUR-PUSARA</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #1a1a1a; background: white; }
    .page { padding: 2cm; max-width: 210mm; margin: 0 auto; }

    .header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #20331B; }
    .header img { width: 60px; margin: 0 auto 0.5rem; }
    .header h1 { font-size: 16pt; color: #20331B; margin-bottom: 0.2rem; }
    .header h2 { font-size: 12pt; font-weight: normal; color: #555; }
    .header p { font-size: 10pt; color: #888; margin-top: 0.3rem; }

    .info-box { display: grid; grid-template-columns: repeat(4,1fr); gap: 0.5rem; margin-bottom: 1.5rem; }
    .info-item { border: 1px solid #dde3ce; border-radius: 4px; padding: 0.5rem; text-align: center; }
    .info-item .num { font-size: 18pt; font-weight: bold; color: #20331B; }
    .info-item .lbl { font-size: 8pt; color: #888; }

    table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 1rem; }
    thead tr { background: #20331B; color: white; }
    th { padding: 0.5rem 0.75rem; text-align: left; font-weight: 600; font-size: 9pt; }
    td { padding: 0.45rem 0.75rem; border-bottom: 1px solid #eee; }
    tr:nth-child(even) td { background: #f9faf6; }

    .footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #dde3ce; display: flex; justify-content: space-between; font-size: 9pt; color: #888; }
    .signature-box { margin-top: 3rem; display: flex; justify-content: flex-end; }
    .signature { text-align: center; }
    .signature-line { width: 200px; border-top: 1px solid #333; padding-top: 0.3rem; font-size: 10pt; }

    @media print {
      body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print" style="background:#20331B;color:white;padding:0.75rem 1rem;display:flex;justify-content:space-between;align-items:center;font-family:sans-serif;font-size:0.85rem;">
  <div style="display:flex;align-items:center;gap:0.6rem;"><img src="../../assets/img/logo-dark-bg.png" style="width:28px;height:28px;border-radius:4px;" onerror="this.style.display='none'"><span style="font-weight:600;">NUR-PUSARA</span><span style="opacity:0.6;font-size:0.8rem;">— Laporan Pengebumian</span></div>
  <div style="display:flex;gap:0.5rem;">
    <button onclick="window.print()" style="background:white;color:#20331B;border:none;padding:0.4rem 1rem;border-radius:4px;cursor:pointer;font-weight:600;">Cetak / Simpan PDF</button>
    <button onclick="window.close()" style="background:transparent;color:white;border:1px solid rgba(255,255,255,0.3);padding:0.4rem 1rem;border-radius:4px;cursor:pointer;">Tutup</button>
  </div>
</div>

<div class="page">
  <!-- Header -->
  <div class="header">
    <img src="../../assets/img/logo-green.png" alt="Logo">
    <h1>SISTEM NUR-PUSARA</h1>
    <h2>Jabatan Agama Islam Selangor (JAIS)</h2>
    <p>Laporan Pengebumian: <?= date('d M Y', strtotime($from)) ?> hingga <?= date('d M Y', strtotime($to)) ?></p>
    <p>Dijana pada: <?= date('d M Y, h:i A') ?> oleh <?= htmlspecialchars($_SESSION['nama_penuh'] ?? 'Admin') ?></p>
  </div>

  <!-- Stats -->
  <div class="info-box">
    <div class="info-item"><div class="num"><?= mysqli_num_rows($burials) ?></div><div class="lbl">Jumlah Pengebumian</div></div>
    <div class="info-item"><div class="num"><?= $lot_stats['total'] ?></div><div class="lbl">Jumlah Lot</div></div>
    <div class="info-item"><div class="num"><?= $lot_stats['diduduki'] ?></div><div class="lbl">Lot Tersedia</div></div>
    <div class="info-item"><div class="num"><?= $lot_stats['diduduki'] ?></div><div class="lbl">Lot Diduduki</div></div>
  </div>

  <!-- Table -->
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
      <?php $no=1; while($row = mysqli_fetch_assoc($burials)): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['deceased_name']) ?></td>
        <td><?= htmlspecialchars($row['no_ic'] ?? '—') ?></td>
        <td><?= $row['jantina'] === 'lelaki' ? 'Lelaki' : 'Perempuan' ?></td>
        <td><?= date('d M Y', strtotime($row['tarikh_kebumi'])) ?></td>
        <td><?= htmlspecialchars($row['kod_lot']) ?></td>
        <td><?= htmlspecialchars($row['nama_tapak']) ?></td>
        <td><?= htmlspecialchars($row['dikebumikan_oleh'] ?? '—') ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Signature -->
  <div class="signature-box">
    <div class="signature">
      <br><br><br>
      <div class="signature-line">
        <?= htmlspecialchars($_SESSION['nama_penuh'] ?? 'Admin') ?><br>
        <span style="font-size:9pt;color:#888;">Pegawai Bertanggungjawab</span>
      </div>
    </div>
  </div>

  <div class="footer">
    <span>SISTEM NUR-PUSARA © <?= date('Y') ?> — JAIS</span>
    <span>Laporan ini dijana secara automatik oleh sistem</span>
  </div>
</div>

</body>
</html>
