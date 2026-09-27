<?php
require_once '../../config/db.php';
// FAQ page is public — no requireLogin()
?>
<!DOCTYPE html>
<html lang="ms" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Soalan Lazim (FAQ) — NUR-PUSARA</title>
  <link rel="icon" type="image/png" href="../../assets/img/logo-green.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-1: #20331B;
      --green-2: #2b4522;
      --green-3: #3a5c2e;
      --gold:    #C3CA92;
      --gold-2:  #a8b074;
      --cream:   #f5f6f0;
      --white:   #ffffff;
    }

    html, body { min-height: 100%; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--cream);
      color: #1a1a1a;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    html[data-theme="dark"] body { background: #111a0e; color: #e8ede0; }

    /* ── Navbar ── */
    .navbar {
      background: var(--green-1);
      padding: 1rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      text-decoration: none;
      color: white;
    }
    .navbar-brand img { width: 32px; }
    .navbar-brand span {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }
    .navbar-right { display: flex; align-items: center; gap: 0.75rem; }
    .btn-back {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 50px;
      padding: 0.45rem 1rem;
      color: rgba(255,255,255,0.8);
      font-family: 'Inter', sans-serif;
      font-size: 0.78rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-back:hover { background: rgba(255,255,255,0.18); color: white; }
    .theme-btn {
      width: 34px; height: 34px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.2);
      background: rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.7);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background 0.2s;
    }
    .theme-btn:hover { background: rgba(255,255,255,0.15); color: white; }

    /* ── Hero strip ── */
    .faq-hero {
      background: var(--green-1);
      padding: 3rem 2rem 4rem;
      text-align: center;
      color: white;
      position: relative;
      overflow: hidden;
    }
    .faq-hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cg fill='none' stroke='rgba(195,202,146,0.06)' stroke-width='0.8'%3E%3Cpolygon points='30,3 39,12 48,12 54,21 48,30 54,39 48,48 39,48 30,57 21,48 12,48 6,39 12,30 6,21 12,12 21,12'/%3E%3C/g%3E%3C/svg%3E");
    }
    .faq-hero-content { position: relative; z-index: 1; }
    .faq-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(195,202,146,0.12);
      border: 1px solid rgba(195,202,146,0.28);
      border-radius: 50px;
      padding: 0.28rem 0.9rem;
      font-size: 0.68rem;
      font-weight: 600;
      color: var(--gold);
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-bottom: 1.2rem;
    }
    .faq-hero h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 5vw, 3.2rem);
      font-weight: 700;
      margin-bottom: 0.6rem;
    }
    .faq-hero p { font-size: 0.9rem; color: rgba(255,255,255,0.6); max-width: 460px; margin: 0 auto; line-height: 1.7; }

    /* ── Main content ── */
    .faq-main {
      max-width: 780px;
      width: 100%;
      margin: -2rem auto 0;
      padding: 0 1.5rem 4rem;
      position: relative;
      z-index: 1;
      flex: 1;
    }

    /* Category tabs */
    .faq-tabs {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-bottom: 2rem;
      background: var(--white);
      padding: 0.5rem;
      border-radius: 50px;
      box-shadow: 0 4px 20px rgba(32,51,27,0.08);
    }
    html[data-theme="dark"] .faq-tabs { background: #1a2614; }

    .faq-tab {
      flex: 1;
      min-width: 120px;
      text-align: center;
      padding: 0.55rem 1rem;
      border-radius: 50px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      background: none;
      color: #777;
      transition: background 0.2s, color 0.2s;
      font-family: 'Inter', sans-serif;
    }
    .faq-tab:hover { color: var(--green-2); }
    .faq-tab.active {
      background: var(--green-1);
      color: white;
    }

    /* FAQ category section */
    .faq-category { display: none; }
    .faq-category.show { display: block; }

    .faq-category-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--green-1);
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    html[data-theme="dark"] .faq-category-title { color: var(--gold); }

    /* Accordion items */
    .faq-item {
      background: var(--white);
      border-radius: 12px;
      margin-bottom: 0.75rem;
      border: 1.5px solid #e8ede0;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    html[data-theme="dark"] .faq-item { background: #1a2614; border-color: rgba(195,202,146,0.12); }
    .faq-item.open { border-color: var(--gold-2); box-shadow: 0 4px 16px rgba(32,51,27,0.07); }

    .faq-q {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.1rem 1.3rem;
      background: none;
      border: none;
      cursor: pointer;
      text-align: left;
      gap: 1rem;
      font-family: 'Inter', sans-serif;
    }
    .faq-q-text {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--green-1);
      line-height: 1.4;
    }
    html[data-theme="dark"] .faq-q-text { color: #e8ede0; }

    .faq-icon {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: var(--cream);
      display: flex; align-items: center; justify-content: center;
      color: var(--green-2);
      flex-shrink: 0;
      transition: background 0.2s, transform 0.25s;
    }
    html[data-theme="dark"] .faq-icon { background: rgba(195,202,146,0.1); color: var(--gold-2); }
    .faq-item.open .faq-icon { background: var(--green-1); color: white; transform: rotate(45deg); }

    .faq-a {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.2s ease;
    }
    .faq-item.open .faq-a { max-height: 400px; }

    .faq-a-inner {
      padding: 0 1.3rem 1.2rem;
      font-size: 0.84rem;
      color: #666;
      line-height: 1.75;
      border-top: 1px solid #eee;
      padding-top: 0.9rem;
    }
    html[data-theme="dark"] .faq-a-inner { color: rgba(255,255,255,0.5); border-color: rgba(255,255,255,0.08); }

    /* Contact box */
    .contact-box {
      background: var(--green-1);
      border-radius: 16px;
      padding: 2rem;
      text-align: center;
      color: white;
      margin-top: 2.5rem;
    }
    .contact-box h3 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 0.4rem;
      color: var(--gold);
    }
    .contact-box p { font-size: 0.82rem; color: rgba(255,255,255,0.6); margin-bottom: 1.2rem; }
    .contact-info { display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; }
    .contact-item { text-align: center; }
    .contact-item .label { font-size: 0.68rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.25rem; }
    .contact-item .val { font-size: 0.85rem; font-weight: 600; color: var(--gold); }

    /* Footer */
    .page-footer {
      background: var(--green-1);
      color: rgba(255,255,255,0.3);
      text-align: center;
      padding: 1.2rem;
      font-size: 0.7rem;
      flex-shrink: 0;
    }

    @media (max-width: 600px) {
      .navbar { padding: 0.9rem 1.2rem; }
      .faq-tabs { border-radius: 14px; }
      .faq-tab { flex: none; width: 100%; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <a href="../../index.php" class="navbar-brand">
      <img src="../../assets/img/logo-dark-bg.png" alt="NUR-PUSARA" onerror="this.style.display='none'">
      <span>NUR-PUSARA</span>
    </a>
    <div class="navbar-right">
      <button class="theme-btn" id="themeBtn" aria-label="Toggle tema">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <a href="../../index.php" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
      </a>
    </div>
  </nav>

  <!-- Hero strip -->
  <div class="faq-hero">
    <div class="faq-hero-content">
      <div class="faq-badge">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Pusat Bantuan
      </div>
      <h1>Soalan Lazim (FAQ)</h1>
      <p>Jawapan kepada soalan-soalan yang sering ditanya berkaitan sistem NUR-PUSARA dan perkhidmatan perkuburan JAIS</p>
    </div>
  </div>

  <!-- Main content -->
  <div class="faq-main">

    <!-- Category tabs -->
    <div class="faq-tabs">
      <button class="faq-tab active" onclick="showTab('umum')">Umum</button>
      <button class="faq-tab" onclick="showTab('carian')">Carian Arwah</button>
      <button class="faq-tab" onclick="showTab('perkuburan')">Perkuburan</button>
      <button class="faq-tab" onclick="showTab('teknikal')">Teknikal</button>
    </div>

    <!-- Tab: Umum -->
    <div class="faq-category show" id="tab-umum">
      <div class="faq-category-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Soalan Umum
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Apakah itu Sistem NUR-PUSARA?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">NUR-PUSARA (Sistem Pengurusan Tanah Perkuburan Islam Bersepadu) adalah sistem digital yang dibangunkan untuk Jabatan Agama Islam Selangor (JAIS) bagi memudahkan pengurusan rekod perkuburan, pencarian maklumat si mati dan pemetaan lot perkuburan di seluruh Selangor.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Adakah sistem ini percuma untuk orang awam?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Ya, fungsi carian awam adalah percuma dan boleh diakses tanpa pendaftaran atau log masuk. Orang awam boleh mencari maklumat lokasi kubur berdasarkan nama si mati, tahun kematian dan lokasi tapak perkuburan.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Siapakah yang boleh menggunakan sistem ini?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Sistem ini mempunyai dua jenis pengguna. Pertama, <strong>orang awam</strong> — boleh mencari maklumat lokasi kubur tanpa log masuk. Kedua, <strong>kakitangan JAIS</strong> — perlu log masuk untuk mengurus rekod si mati, pengebumian, lot perkuburan dan menjana laporan.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Di manakah sistem ini meliputi tapak perkuburan?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Sistem ini meliputi semua tapak tanah perkuburan Islam yang berada di bawah pengurusan JAIS merentas daerah di Selangor termasuk Shah Alam, Petaling Jaya, Klang, Gombak, Hulu Langat dan lain-lain.</div></div>
      </div>
    </div>

    <!-- Tab: Carian -->
    <div class="faq-category" id="tab-carian">
      <div class="faq-category-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Carian Maklumat Arwah
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Bagaimana cara mencari maklumat lokasi kubur?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Taip nama arwah atau gunakan fungsi carian lanjutan dengan memilih lokasi tapak perkuburan dan tahun kematian. Hasil carian akan memaparkan nombor lot, seksyen dan koordinat GPS lokasi kubur yang boleh dibuka dalam Google Maps.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Apakah maklumat yang dipaparkan dalam hasil carian awam?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Hasil carian awam memaparkan: nama si mati, tarikh lahir, tarikh kematian, umur semasa meninggal, nombor lot, seksyen, lokasi tapak perkuburan dan peta Google Maps. Maklumat sensitif seperti nombor kad pengenalan tidak dipaparkan kepada orang awam.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Bolehkah saya mencari menggunakan tarikh kematian?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Ya, anda boleh menapis carian menggunakan tahun kematian dan lokasi tapak perkuburan untuk mempersempit hasil carian. Fungsi carian lanjutan tersedia pada halaman carian awam.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Maklumat arwah saya tidak dijumpai, apa yang perlu dilakukan?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Jika maklumat tidak dijumpai, kemungkinan rekod belum didaftarkan dalam sistem. Sila hubungi pejabat JAIS terdekat atau hubungi Unit Tanah Kubur di talian 03-5514 3674 untuk mendapatkan bantuan lanjut.</div></div>
      </div>
    </div>

    <!-- Tab: Perkuburan -->
    <div class="faq-category" id="tab-perkuburan">
      <div class="faq-category-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
        Maklumat Perkuburan
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Bagaimana cara mendaftarkan pengebumian?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Pendaftaran pengebumian dilakukan oleh kakitangan JAIS sahaja melalui sistem log masuk. Waris perlu menghubungi pejabat JAIS atau unit tanah kubur yang berkenaan untuk mendaftarkan pengebumian dan peruntukan lot.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Apakah maksud status lot — Tersedia, Diduduki, Ditempah?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner"><strong>Tersedia</strong> — lot kosong dan boleh digunakan. <strong>Diduduki</strong> — lot telah digunakan untuk pengebumian. <strong>Penyelenggaraan</strong> — lot dalam proses penyelenggaraan dan tidak boleh digunakan buat sementara waktu.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Bolehkah saya menempah lot terlebih dahulu?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Penempahan lot perlu dilakukan melalui pejabat JAIS. Sila hubungi Unit Tanah Kubur untuk maklumat lanjut mengenai prosedur penempahan lot perkuburan.</div></div>
      </div>
    </div>

    <!-- Tab: Teknikal -->
    <div class="faq-category" id="tab-teknikal">
      <div class="faq-category-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Soalan Teknikal
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Pelayar (browser) apakah yang disokong?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Sistem ini boleh diakses menggunakan mana-mana pelayar moden termasuk Google Chrome, Mozilla Firefox, Microsoft Edge dan Safari. Disyorkan menggunakan versi terkini untuk pengalaman terbaik.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Adakah sistem ini boleh diakses melalui telefon pintar?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Ya, sistem NUR-PUSARA adalah responsif dan boleh diakses melalui telefon pintar, tablet dan komputer. Paparan akan disesuaikan secara automatik mengikut saiz skrin peranti anda.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Saya terlupa kata laluan, apa yang perlu dilakukan?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Sila hubungi pentadbir sistem (Administrator JAIS) untuk menetapkan semula kata laluan anda. Pentadbir boleh mengemas kini kata laluan melalui modul Pengurusan Pengguna dalam sistem.</div></div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          <span class="faq-q-text">Bagaimana untuk melaporkan masalah atau ralat dalam sistem?</span>
          <span class="faq-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
        </button>
        <div class="faq-a"><div class="faq-a-inner">Sila hubungi Unit Tanah Kubur JAIS melalui telefon di 03-5514 3674 atau emel ke admin@jais.gov.my untuk melaporkan sebarang masalah teknikal. Nyatakan butiran masalah dan tangkapan skrin jika ada.</div></div>
      </div>
    </div>

    <!-- Contact / Help desk box -->
    <div class="contact-box">
      <h3>Masih perlukan bantuan?</h3>
      <p>Hubungi Unit Tanah Kubur JAIS untuk bantuan lanjut</p>
      <div class="contact-info">
        <div class="contact-item">
          <div class="label">Telefon</div>
          <div class="val">03-5514 3674</div>
        </div>
        <div class="contact-item">
          <div class="label">Emel</div>
          <div class="val">admin@jais.gov.my</div>
        </div>
        <div class="contact-item">
          <div class="label">Waktu Pejabat</div>
          <div class="val">Isnin – Jumaat, 8am – 5pm</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <footer class="page-footer">
    &copy; <?= date('Y') ?> SISTEM NUR-PUSARA &mdash; Jabatan Agama Islam Selangor. Hak cipta terpelihara.
  </footer>

<script>
  /* Theme toggle */
  var MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SUN  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('nur-pusara-theme', t);
    var btn = document.getElementById('themeBtn');
    if (btn) btn.innerHTML = t === 'light' ? MOON : SUN;
  }
  applyTheme(localStorage.getItem('nur-pusara-theme') || 'light');
  document.getElementById('themeBtn').addEventListener('click', function () {
    applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  });

  /* Tab switching */
  function showTab(id) {
    document.querySelectorAll('.faq-category').forEach(function(el) { el.classList.remove('show'); });
    document.querySelectorAll('.faq-tab').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('tab-' + id).classList.add('show');
    event.currentTarget.classList.add('active');
  }

  /* Accordion toggle */
  function toggleFaq(btn) {
    var item = btn.closest('.faq-item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(function(el) { el.classList.remove('open'); });
    if (!isOpen) item.classList.add('open');
  }
</script>
</body>
</html>
