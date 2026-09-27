<?php
require_once '../../config/db.php';
requireLogin();

$id = intval($_GET['id_pengebumian'] ?? 0);
if (!$id) { header("Location: " . BASE_URL . "/modules/burial/index.php"); exit(); }

$stmt = mysqli_prepare($conn, "
    SELECT b.*, 
           d.nama AS deceased_name, d.no_ic, d.jantina,
           d.tarikh_lahir, d.tarikh_mati, d.sebab_mati, d.id_simati,
           l.kod_lot, l.blok, l.no_lot, c.nama AS nama_tapak,
           u.nama_penuh AS registered_by
    FROM tbl_pengebumian b
    JOIN tbl_simati d ON b.id_simati = d.id_simati
    JOIN tbl_lot l ON b.id_lot = l.id_lot
    JOIN tbl_tapak_kubur c ON l.id_tapak = c.id_tapak
    LEFT JOIN tbl_pengguna u ON b.dicipta_oleh = u.id_pengguna
    WHERE b.id_pengebumian = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$b = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$b) { header("Location: " . BASE_URL . "/modules/burial/index.php"); exit(); }

// Fetch next of kin
$kin_result = mysqli_query($conn, "SELECT * FROM tbl_waris WHERE id_simati = {$b['id_simati']} LIMIT 1");
$kin = mysqli_fetch_assoc($kin_result);

$ref = 'JAIS/NP/' . date('Y') . '/' . str_pad($id, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Sijil Pengebumian — <?= htmlspecialchars($b['deceased_name']) ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap');

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'Inter', sans-serif;
    font-size: 11pt;
    color: #1a1a1a;
    background: #f0f0e8;
    padding: 2rem;
  }

  .page {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 0 auto;
    padding: 18mm 16mm 14mm;
    box-shadow: 0 4px 24px rgba(0,0,0,0.12);
    position: relative;
    border-top: 8px solid #20331B;
  }

  /* ── Header ── */
  .doc-header {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #20331B;
    margin-bottom: 1.4rem;
  }
  .doc-header img { width: 60px; }
  .doc-header-text { flex: 1; }
  .doc-header-text h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 17pt;
    font-weight: 700;
    color: #20331B;
    line-height: 1.2;
  }
  .doc-header-text p {
    font-size: 8.5pt;
    color: #555;
    margin-top: 2px;
  }
  .doc-header-ref {
    text-align: right;
    font-size: 8pt;
    color: #666;
    line-height: 1.8;
  }

  /* ── Title bar ── */
  .title-bar {
    background: #20331B;
    color: #C3CA92;
    text-align: center;
    padding: 0.55rem 1rem;
    border-radius: 4px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 14pt;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 1.4rem;
  }

  /* ── Sections ── */
  .section-label {
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #859864;
    margin-bottom: 0.6rem;
    padding-bottom: 3px;
    border-bottom: 1px solid #dde3ce;
  }

  .field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.55rem 2rem;
    margin-bottom: 1.4rem;
  }
  .field-grid.full { grid-template-columns: 1fr; }

  .field { display: flex; flex-direction: column; gap: 2px; }
  .field label {
    font-size: 7.5pt;
    color: #888;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .field span {
    font-size: 10.5pt;
    color: #1a1a1a;
    font-weight: 500;
    border-bottom: 1px solid #e8e8e0;
    padding-bottom: 3px;
  }

  /* ── Lot badge ── */
  .lot-highlight {
    background: #f5f6f0;
    border: 1.5px solid #C3CA92;
    border-radius: 8px;
    padding: 0.75rem 1.2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.4rem;
  }
  .lot-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36pt;
    font-weight: 700;
    color: #20331B;
    line-height: 1;
  }
  .lot-details { font-size: 9pt; color: #555; line-height: 2; }
  .lot-details strong { color: #20331B; }

  /* ── Signature ── */
  .sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2.5rem;
    padding-top: 1rem;
    border-top: 1px dashed #dde3ce;
  }
  .sig-box { text-align: center; }
  .sig-line {
    border-bottom: 1px solid #aaa;
    height: 40px;
    margin-bottom: 6px;
  }
  .sig-label { font-size: 8pt; color: #666; }
  .sig-name { font-size: 9pt; font-weight: 600; color: #20331B; margin-top: 2px; }

  /* ── Footer ── */
  .doc-footer {
    position: absolute;
    bottom: 12mm;
    left: 16mm;
    right: 16mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 7.5pt;
    color: #aaa;
    border-top: 1px solid #e8e8e0;
    padding-top: 6px;
  }

  /* ── Print button (screen only) ── */
  .print-bar {
    width: 210mm;
    margin: 0 auto 1rem;
    display: flex;
    gap: 0.6rem;
    justify-content: flex-end;
  }
  .btn-print {
    background: #20331B;
    color: #C3CA92;
    border: none;
    padding: 0.5rem 1.4rem;
    border-radius: 6px;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
  }
  .btn-back {
    background: transparent;
    color: #555;
    border: 1px solid #ccc;
    padding: 0.5rem 1.2rem;
    border-radius: 6px;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
  }

  @media print {
    body { background: white; padding: 0; }
    .print-bar { display: none; }
    .page {
      box-shadow: none;
      border-top: 8px solid #20331B;
      margin: 0;
      width: 100%;
      min-height: 100vh;
    }
  }
</style>
</head>
<body>

<div class="print-bar">
  <a href="javascript:window.close()" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="page">

  <!-- Header -->
  <div class="doc-header">
    <img src="../../assets/img/logo-green.png" alt="JAIS">
    <div class="doc-header-text">
      <h2>Jabatan Agama Islam Selangor</h2>
      <p>Bahagian Pengurusan Masjid — Unit Tanah Kubur</p>
      <p>No. 3, Jalan Pusat Bandar Timur, Seksyen 25, 40150 Shah Alam, Selangor</p>
    </div>
    <div class="doc-header-ref">
      <strong>No. Rujukan:</strong><br><?= $ref ?><br>
      <strong>Tarikh Cetak:</strong><br><?= date('d M Y') ?>
    </div>
  </div>

  <!-- Title -->
  <div class="title-bar">Sijil Rekod Pengebumian Islam</div>

  <!-- Lot highlight -->
  <div class="lot-highlight">
    <div class="lot-num"><?= htmlspecialchars($b['blok'] . '-' . str_pad($b['no_lot'], 4, '0', STR_PAD_LEFT)) ?></div>
    <div class="lot-details">
      <strong><?= htmlspecialchars($b['nama_tapak']) ?> — Blok <?= htmlspecialchars($b['blok']) ?></strong><br>
      Tarikh Pengebumian: <strong><?= date('d M Y', strtotime($b['tarikh_kebumi'])) ?></strong><br>
      Masa: <strong><?= $b['masa_kebumi'] ? date('h:i A', strtotime($b['masa_kebumi'])) : '—' ?></strong>
    </div>
  </div>

  <!-- Deceased info -->
  <div class="section-label">Maklumat Si Mati</div>
  <div class="field-grid">
    <div class="field">
      <label>Nama Penuh</label>
      <span><?= htmlspecialchars($b['deceased_name']) ?></span>
    </div>
    <div class="field">
      <label>No. Kad Pengenalan</label>
      <span><?= htmlspecialchars($b['no_ic']) ?></span>
    </div>
    <div class="field">
      <label>Jantina</label>
      <span><?= ucfirst($b['jantina']) ?></span>
    </div>
    <div class="field">
      <label>Tarikh Lahir</label>
      <span><?= $b['tarikh_lahir'] ? date('d M Y', strtotime($b['tarikh_lahir'])) : '—' ?></span>
    </div>
    <div class="field">
      <label>Tarikh Kematian</label>
      <span><?= date('d M Y', strtotime($b['tarikh_mati'])) ?></span>
    </div>
    <div class="field">
      <label>Sebab Kematian</label>
      <span><?= htmlspecialchars($b['sebab_mati'] ?? '—') ?></span>
    </div>
  </div>

  <!-- Burial info -->
  <div class="section-label">Maklumat Pengebumian</div>
  <div class="field-grid">
    <div class="field">
      <label>Dikebumikan Oleh</label>
      <span><?= htmlspecialchars($b['dikebumikan_oleh'] ?? '—') ?></span>
    </div>
    <div class="field">
      <label>Saksi</label>
      <span><?= htmlspecialchars($b['nama_saksi'] ?? '—') ?></span>
    </div>
    <div class="field">
      <label>Didaftarkan Oleh</label>
      <span><?= htmlspecialchars($b['registered_by'] ?? '—') ?></span>
    </div>
    <div class="field">
      <label>Tarikh Daftar</label>
      <span><?= date('d M Y', strtotime($b['dicipta_pada'])) ?></span>
    </div>
  </div>

  <!-- Next of kin -->
  <?php if ($kin): ?>
  <div class="section-label">Waris Terdekat</div>
  <div class="field-grid">
    <div class="field">
      <label>Nama Waris</label>
      <span><?= htmlspecialchars($kin['nama']) ?></span>
    </div>
    <div class="field">
      <label>Hubungan</label>
      <span><?= htmlspecialchars($kin['hubungan']) ?></span>
    </div>
    <div class="field">
      <label>No. Telefon</label>
      <span><?= htmlspecialchars($kin['telefon'] ?? '—') ?></span>
    </div>
    <div class="field">
      <label>E-mel</label>
      <span><?= htmlspecialchars($kin['emel'] ?? '—') ?></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- Signatures -->
  <div class="sig-row">
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-label">Tandatangan Pegawai</div>
      <div class="sig-name"><?= htmlspecialchars($b['registered_by'] ?? 'Pegawai JAIS') ?></div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-label">Tandatangan Waris / Wakil</div>
      <div class="sig-name"><?= $kin ? htmlspecialchars($kin['nama']) : '________________________' ?></div>
    </div>
  </div>

  <!-- Footer -->
  <div class="doc-footer">
    <span>SISTEM NUR-PUSARA — Jabatan Agama Islam Selangor</span>
    <span>Dicetak: <?= date('d M Y, h:i A') ?></span>
    <span><?= $ref ?></span>
  </div>

</div>
</body>
</html>
