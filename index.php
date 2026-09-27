<?php
require_once 'config/db.php';

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['nama_pengguna'] ?? $_POST['username'] ?? '');
    $password = $_POST['kata_laluan'] ?? $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_pengguna WHERE nama_pengguna = ? AND status = 'active'");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && password_verify($password, $user['kata_laluan'])) {
            $_SESSION['id_pengguna']   = $user['id_pengguna'];
            $_SESSION['username']  = $user['nama_pengguna'];
            $_SESSION['nama_penuh'] = $user['nama_penuh'];
            $_SESSION['role']      = $user['peranan'];
            header("Location: " . BASE_URL . "/dashboard/index.php");
            exit();
        } else {
            $error = 'Nama pengguna atau kata laluan tidak sah.';
        }
    } else {
        $error = 'Sila isi semua maklumat.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NUR-PUSARA — Sistem Pengurusan Tanah Perkuburan Islam</title>
  <link rel="icon" type="image/png" href="assets/img/logo-green.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-1: #20331B;
      --green-2: #2b4522;
      --green-3: #3a5c2e;
      --gold:    #C3CA92;
      --gold-2:  #a8b074;
      --white:   #ffffff;
    }

    html { height: 100%; }
    body { min-height: 100%; display: flex; flex-direction: column; }

    /* ── Full-screen hero ── */
    .hero {
      height: 100vh;
      width: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }

    /* Dark green layered background */
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 15% 50%, rgba(78,122,64,0.35) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 15%, rgba(195,202,146,0.12) 0%, transparent 50%),
        radial-gradient(ellipse at 60% 80%, rgba(20,40,15,0.6) 0%, transparent 55%),
        linear-gradient(160deg, #0e1f0a 0%, #182e12 35%, #20331B 65%, #172b12 100%);
      z-index: 0;
    }

    /* Islamic geometric pattern overlay */
    .hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Cg fill='none' stroke='rgba(195,202,146,0.07)' stroke-width='0.8'%3E%3Cpolygon points='40,4 52,16 64,16 72,28 64,40 72,52 64,64 52,64 40,76 28,64 16,64 8,52 16,40 8,28 16,16 28,16'/%3E%3Cpolygon points='40,14 49,22 58,22 64,31 58,40 64,49 58,58 49,58 40,66 31,58 22,58 16,49 22,40 16,31 22,22 31,22'/%3E%3Cline x1='40' y1='4' x2='40' y2='76'/%3E%3Cline x1='8' y1='40' x2='72' y2='40'/%3E%3C/g%3E%3C/svg%3E");
      z-index: 0;
    }

    /* ── Navbar ── */
    .navbar {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.3rem 2.5rem;
      background: rgba(0,0,0,0.18);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(195,202,146,0.1);
      flex-shrink: 0;
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      text-decoration: none;
      color: var(--white);
    }
    .navbar-brand img { width: 34px; }
    .navbar-brand-text {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }
    .navbar-brand-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      color: var(--white);
    }
    .navbar-brand-sub {
      font-size: 0.62rem;
      color: rgba(195,202,146,0.7);
      letter-spacing: 0.04em;
    }

    /* Staff login button in navbar */
    .btn-staff-login {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      background: rgba(195,202,146,0.12);
      border: 1.5px solid rgba(195,202,146,0.35);
      border-radius: 50px;
      padding: 0.5rem 1.2rem;
      color: var(--gold);
      font-family: 'Inter', sans-serif;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s, border-color 0.2s, color 0.2s;
      letter-spacing: 0.02em;
    }
    .btn-staff-login:hover {
      background: var(--gold);
      border-color: var(--gold);
      color: var(--green-1);
    }

    /* Theme toggle */
    .navbar-right {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .theme-btn {
      width: 36px; height: 36px;
      border-radius: 50%;
      border: 1.5px solid rgba(255,255,255,0.18);
      background: rgba(255,255,255,0.07);
      color: rgba(255,255,255,0.7);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background 0.2s, color 0.2s;
    }
    .theme-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }

    /* ── Main content — centred vertically ── */
    .hero-body {
      position: relative;
      z-index: 1;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
      text-align: center;
    }

    /* JAIS badge */
    .jais-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(195,202,146,0.12);
      border: 1px solid rgba(195,202,146,0.28);
      border-radius: 50px;
      padding: 0.3rem 1rem;
      font-size: 0.68rem;
      font-weight: 600;
      color: var(--gold);
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-bottom: 1.6rem;
    }

    /* Heading */
    .hero-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.6rem, 6.5vw, 4.8rem);
      font-weight: 700;
      line-height: 1.1;
      color: var(--white);
      margin-bottom: 1rem;
      letter-spacing: 0.01em;
    }
    .hero-title .accent { color: var(--gold); }

    /* Subtitle */
    .hero-sub {
      font-size: 0.92rem;
      color: rgba(255,255,255,0.58);
      line-height: 1.75;
      max-width: 480px;
      margin: 0 auto 2.5rem;
    }

    /* Search bar */
    .search-wrap { width: 100%; max-width: 560px; margin: 0 auto; }
    .search-box {
      display: flex;
      background: rgba(255,255,255,0.07);
      border: 1.5px solid rgba(195,202,146,0.28);
      border-radius: 50px;
      padding: 0.38rem 0.38rem 0.38rem 1.4rem;
      backdrop-filter: blur(12px);
      transition: border-color 0.2s, background 0.2s;
    }
    .search-box:focus-within {
      border-color: var(--gold);
      background: rgba(255,255,255,0.11);
    }
    .search-box input {
      flex: 1;
      background: none;
      border: none;
      outline: none;
      color: var(--white);
      font-family: 'Inter', sans-serif;
      font-size: 0.92rem;
      min-width: 0;
    }
    .search-box input::placeholder { color: rgba(255,255,255,0.38); }
    .search-box button {
      background: var(--gold);
      color: var(--green-1);
      border: none;
      border-radius: 50px;
      padding: 0.6rem 1.5rem;
      font-family: 'Inter', sans-serif;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      white-space: nowrap;
      transition: background 0.2s, transform 0.15s;
    }
    .search-box button:hover { background: #d4db9e; transform: scale(1.02); }

    .search-hint {
      font-size: 0.7rem;
      color: rgba(255,255,255,0.28);
      margin-top: 0.7rem;
      letter-spacing: 0.03em;
    }

    /* ── Footer inside hero ── */
    .hero-footer {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 1rem;
      font-size: 0.7rem;
      color: rgba(255,255,255,0.2);
      flex-shrink: 0;
      border-top: 1px solid rgba(195,202,146,0.07);
    }

    /* ── Login modal ── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(6px);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .modal-overlay.show { display: flex; }

    .modal {
      background: #fff;
      border-radius: 20px;
      padding: 2.5rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 24px 80px rgba(0,0,0,0.4);
      position: relative;
      animation: modalIn 0.22s ease;
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(16px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-close {
      position: absolute;
      top: 1.1rem; right: 1.1rem;
      width: 30px; height: 30px;
      border-radius: 50%;
      border: 1px solid #e0e0e0;
      background: none;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: #999;
      transition: background 0.15s, color 0.15s;
    }
    .modal-close:hover { background: #f5f5f5; color: #333; }

    .modal-header {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      margin-bottom: 1.5rem;
    }
    .modal-header img { width: 32px; }
    .modal-header-text {}
    .modal-header-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--green-1);
      line-height: 1.2;
    }
    .modal-header-sub {
      font-size: 0.65rem;
      color: #999;
    }

    .modal h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.7rem;
      font-weight: 700;
      color: var(--green-1);
      margin-bottom: 0.25rem;
    }
    .modal-tagline { font-size: 0.8rem; color: #888; margin-bottom: 1.6rem; }

    .modal-field { margin-bottom: 0.95rem; }
    .modal-field label {
      display: block;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #555;
      margin-bottom: 0.4rem;
    }
    .modal-field input {
      width: 100%;
      padding: 0.7rem 1rem;
      border: 1.5px solid #e4ead8;
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      outline: none;
      background: #f8f9f4;
      color: #1a1a1a;
      transition: border-color 0.2s;
    }
    .modal-field input:focus { border-color: var(--green-3); background: #fff; }

    .btn-login {
      width: 100%;
      background: var(--green-1);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 0.82rem;
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 0.4rem;
      letter-spacing: 0.03em;
      transition: background 0.2s;
    }
    .btn-login:hover { background: var(--green-2); }

    .error-box {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: 0.6rem 0.9rem;
      font-size: 0.78rem;
      color: #dc2626;
      margin-bottom: 1rem;
      text-align: center;
    }

    /* Dark mode adjustments */
    html[data-theme="dark"] .hero::before {
      background:
        radial-gradient(ellipse at 15% 50%, rgba(40,70,30,0.4) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 15%, rgba(195,202,146,0.08) 0%, transparent 50%),
        linear-gradient(160deg, #060e04 0%, #0d1a09 35%, #111e0d 65%, #0a1208 100%);
    }

    @media (max-width: 600px) {
      .navbar { padding: 1rem 1.2rem; }
      .navbar-brand-sub { display: none; }
      .hero-title { font-size: 2.4rem; }
    }

    /* ── Bulletin Board Section ── */
    .bulletin-section {
      background: #f5f6f0;
      padding: 5rem 2rem 4rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-shrink: 0;
    }
    html[data-theme="dark"] .bulletin-section {
      background: #111a0e;
    }

    .bulletin-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: var(--green-3);
      margin-bottom: 0.55rem;
    }
    html[data-theme="dark"] .bulletin-label { color: var(--gold-2); }

    .bulletin-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.7rem, 4vw, 2.4rem);
      font-weight: 700;
      color: var(--green-1);
      text-align: center;
      margin-bottom: 0.5rem;
    }
    html[data-theme="dark"] .bulletin-title { color: var(--gold); }

    .bulletin-sub {
      font-size: 0.85rem;
      color: #777;
      text-align: center;
      margin-bottom: 3rem;
      max-width: 440px;
    }
    html[data-theme="dark"] .bulletin-sub { color: rgba(255,255,255,0.45); }

    /* Two poster cards centered */
    .bulletin-grid {
      display: flex;
      gap: 2rem;
      justify-content: center;
      flex-wrap: wrap;
      max-width: 780px;
      width: 100%;
      margin-bottom: 2.5rem;
    }

    .poster-card {
      width: 340px;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 6px 28px rgba(32,51,27,0.1);
      cursor: pointer;
      transition: transform 0.22s ease, box-shadow 0.22s ease;
      background: var(--white);
      border: 1.5px solid #e8ede0;
      flex-shrink: 0;
    }
    html[data-theme="dark"] .poster-card {
      background: #1a2614;
      border-color: rgba(195,202,146,0.15);
    }
    .poster-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 48px rgba(32,51,27,0.16);
    }

    /* Poster image area — CSS-designed poster */
    .poster-img {
      width: 100%;
      height: 200px;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .poster-img-1 {
      background: linear-gradient(135deg, #20331B 0%, #3a5c2e 50%, #C3CA92 100%);
    }
    .poster-img-2 {
      background: linear-gradient(135deg, #1a2a35 0%, #2d4a5c 50%, #7ab8d4 100%);
    }

    .poster-img-content {
      text-align: center;
      color: white;
      padding: 1rem;
      position: relative;
      z-index: 1;
    }
    .poster-img-content .p-logo {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(255,255,255,0.15);
      border: 2px solid rgba(255,255,255,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.6rem;
    }
    .poster-img-content .p-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.1rem;
      font-weight: 700;
      line-height: 1.3;
    }
    .poster-img-content .p-date {
      font-size: 0.7rem;
      opacity: 0.75;
      margin-top: 0.3rem;
      letter-spacing: 0.05em;
    }

    /* Decorative circles on poster */
    .poster-circle-1 {
      position: absolute;
      width: 120px; height: 120px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.1);
      top: -20px; right: -20px;
    }
    .poster-circle-2 {
      position: absolute;
      width: 80px; height: 80px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.08);
      bottom: -10px; left: -10px;
    }

    .poster-body {
      padding: 1.1rem 1.3rem 1.3rem;
    }
    .poster-tag {
      display: inline-blok;
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--green-3);
      background: rgba(58,92,46,0.1);
      border-radius: 50px;
      padding: 0.2rem 0.65rem;
      margin-bottom: 0.5rem;
    }
    html[data-theme="dark"] .poster-tag { color: var(--gold-2); background: rgba(195,202,146,0.1); }

    .poster-body h3 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--green-1);
      margin-bottom: 0.35rem;
      line-height: 1.3;
    }
    html[data-theme="dark"] .poster-body h3 { color: var(--gold); }

    .poster-body p {
      font-size: 0.78rem;
      color: #777;
      line-height: 1.6;
    }
    html[data-theme="dark"] .poster-body p { color: rgba(255,255,255,0.45); }

    .poster-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 0.8rem;
      padding-top: 0.8rem;
      border-top: 1px solid #eee;
    }
    html[data-theme="dark"] .poster-footer { border-color: rgba(255,255,255,0.08); }

    .poster-date { font-size: 0.7rem; color: #aaa; }
    .poster-read {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--green-3);
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }
    html[data-theme="dark"] .poster-read { color: var(--gold-2); }

    /* FAQ mini button */
    .faq-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--white);
      border: 1.5px solid #dde3ce;
      border-radius: 50px;
      padding: 0.55rem 1.4rem;
      font-family: 'Inter', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--green-2);
      text-decoration: none;
      transition: background 0.2s, border-color 0.2s, color 0.2s;
      cursor: pointer;
    }
    .faq-btn:hover {
      background: var(--green-1);
      border-color: var(--green-1);
      color: var(--white);
    }
    html[data-theme="dark"] .faq-btn {
      background: rgba(195,202,146,0.08);
      border-color: rgba(195,202,146,0.2);
      color: var(--gold-2);
    }
    html[data-theme="dark"] .faq-btn:hover {
      background: var(--gold);
      border-color: var(--gold);
      color: var(--green-1);
    }

    /* Page footer */
    .page-footer {
      background: var(--green-1);
      color: rgba(255,255,255,0.35);
      text-align: center;
      padding: 1.2rem;
      font-size: 0.7rem;
      letter-spacing: 0.03em;
      flex-shrink: 0;
    }

    /* ── Lightbox ── */
    .lightbox {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.82);
      backdrop-filter: blur(8px);
      z-index: 300;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .lightbox.show { display: flex; }

    .lightbox-inner {
      background: var(--white);
      border-radius: 20px;
      width: 100%;
      max-width: 520px;
      overflow: hidden;
      box-shadow: 0 32px 80px rgba(0,0,0,0.5);
      animation: lbIn 0.22s ease;
      position: relative;
    }
    html[data-theme="dark"] .lightbox-inner {
      background: #1a2614;
    }
    @keyframes lbIn {
      from { opacity:0; transform: scale(0.93); }
      to   { opacity:1; transform: scale(1); }
    }

    .lightbox-img {
      width: 100%;
      height: 260px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .lightbox-close {
      position: absolute;
      top: 1rem; right: 1rem;
      width: 34px; height: 34px;
      border-radius: 50%;
      background: rgba(0,0,0,0.3);
      border: none;
      color: white;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      z-index: 10;
      transition: background 0.15s;
    }
    .lightbox-close:hover { background: rgba(0,0,0,0.55); }

    .lightbox-body {
      padding: 1.5rem 1.8rem 2rem;
    }
    .lightbox-tag {
      display: inline-blok;
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--green-3);
      background: rgba(58,92,46,0.1);
      border-radius: 50px;
      padding: 0.2rem 0.65rem;
      margin-bottom: 0.7rem;
    }
    html[data-theme="dark"] .lightbox-tag { color: var(--gold-2); background: rgba(195,202,146,0.1); }

    .lightbox-body h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--green-1);
      margin-bottom: 0.5rem;
    }
    html[data-theme="dark"] .lightbox-body h2 { color: var(--gold); }

    .lightbox-body p {
      font-size: 0.83rem;
      color: #666;
      line-height: 1.7;
    }
    html[data-theme="dark"] .lightbox-body p { color: rgba(255,255,255,0.5); }

    .lightbox-meta {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #eee;
      font-size: 0.72rem;
      color: #aaa;
      display: flex;
      gap: 1.5rem;
    }
    html[data-theme="dark"] .lightbox-meta { border-color: rgba(255,255,255,0.08); }

    @media (max-width: 600px) {
      .bulletin-grid { flex-direction: column; align-items: center; }
      .poster-card { width: 100%; max-width: 380px; }
    }

  </style>
</head>
<body>

<section class="hero">

  <!-- Navbar -->
  <nav class="navbar">
    <a href="#" class="navbar-brand">
      <img src="assets/img/logo-dark-bg.png" alt="NUR-PUSARA" onerror="this.style.display='none'">
      <div class="navbar-brand-text">
        <span class="navbar-brand-name">NUR-PUSARA</span>
        <span class="navbar-brand-sub">Jabatan Agama Islam Selangor</span>
      </div>
    </a>
    <div class="navbar-right">
      <button class="theme-btn" id="themeBtn" aria-label="Toggle tema">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <!-- Always show staff login — never dashboard -->
      <button class="btn-staff-login" onclick="openLogin()">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Log Masuk Kakitangan
      </button>
    </div>
  </nav>

  <!-- Hero body -->
  <div class="hero-body">

    <div class="jais-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
      Jabatan Agama Islam Selangor
    </div>

    <h1 class="hero-title">
      Cari Lokasi <span class="accent">Kubur</span><br>Dengan Mudah
    </h1>

    <p class="hero-sub">
      Sistem pengurusan tanah perkuburan Islam bersepadu —
      cari maklumat arwah dan lokasi lot perkuburan dengan cepat dan tepat.
    </p>

    <!-- Public search — redirects to search page -->
    <div class="search-wrap">
      <form method="GET" action="modules/search/index.php" class="search-box">
        <input
          type="text"
          name="q"
          placeholder="Masukkan nama arwah atau nombor IC..."
          autocomplete="off"
        >
        <button type="submit">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Cari
        </button>
      </form>
      <p class="search-hint">Carian awam — tiada log masuk diperlukan</p>
    </div>

  </div>

  <!-- Footer -->
  <div class="hero-footer">
    &copy; <?= date('Y') ?> SISTEM NUR-PUSARA &mdash; Jabatan Agama Islam Selangor
  </div>

</section>



<!-- ═══════════════════════════════════════════
     BULLETIN BOARD SECTION
═══════════════════════════════════════════ -->
<section class="bulletin-section">

  <div class="bulletin-label">Papan Pengumuman</div>
  <h2 class="bulletin-title">Pengumuman Terkini</h2>
  <p class="bulletin-sub">Maklumat dan pemberitahuan rasmi daripada Jabatan Agama Islam Selangor</p>

  <div class="bulletin-grid">

    <!-- Poster 1 -->
    <div class="poster-card" onclick="openLightbox(1)">
      <div class="poster-img poster-img-1">
        <div class="poster-circle-1"></div>
        <div class="poster-circle-2"></div>
        <div class="poster-img-content">
          <div class="p-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
          </div>
          <div class="p-title">Sistem NUR-PUSARA<br>Kini Beroperasi</div>
          <div class="p-date">JABATAN AGAMA ISLAM SELANGOR</div>
        </div>
      </div>
      <div class="poster-body">
        <div class="poster-tag">Pengumuman Rasmi</div>
        <h3>Pelancaran Sistem Pengurusan Tanah Perkuburan Islam Digital</h3>
        <p>JAIS dengan bangga mengumumkan pelancaran sistem NUR-PUSARA bagi memudahkan pengurusan dan pencarian maklumat perkuburan Islam di Selangor.</p>
        <div class="poster-footer">
          <span class="poster-date">1 Jun 2026</span>
          <span class="poster-read">Baca selanjutnya
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </span>
        </div>
      </div>
    </div>

    <!-- Poster 2 -->
    <div class="poster-card" onclick="openLightbox(2)">
      <div class="poster-img poster-img-2">
        <div class="poster-circle-1"></div>
        <div class="poster-circle-2"></div>
        <div class="poster-img-content">
          <div class="p-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
          <div class="p-title">Carian Maklumat<br>Arwah Secara Digital</div>
          <div class="p-date">JABATAN AGAMA ISLAM SELANGOR</div>
        </div>
      </div>
      <div class="poster-body">
        <div class="poster-tag">Perkhidmatan Awam</div>
        <h3>Kini Orang Awam Boleh Mencari Maklumat Arwah Secara Dalam Talian</h3>
        <p>Waris dan orang awam kini boleh mencari lokasi kubur, maklumat pengebumian serta kedudukan lot di peta perkuburan tanpa perlu hadir ke pejabat.</p>
        <div class="poster-footer">
          <span class="poster-date">15 Jun 2026</span>
          <span class="poster-read">Baca selanjutnya
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </span>
        </div>
      </div>
    </div>

  </div>

  <!-- FAQ mini button -->
  <a href="modules/faq/index.php" class="faq-btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    Soalan Lazim (FAQ) &amp; Bantuan
  </a>

</section>

<!-- Page footer -->
<footer class="page-footer">
  &copy; <?= date('Y') ?> SISTEM NUR-PUSARA &mdash; Jabatan Agama Islam Selangor. Hak cipta terpelihara.
</footer>


<!-- ═══════════════════════════════════════════
     LIGHTBOX — Poster fullview
═══════════════════════════════════════════ -->
<div class="lightbox" id="lightbox" onclick="closeLightboxOverlay(event)">
  <div class="lightbox-inner" id="lightboxInner">
    <button class="lightbox-close" onclick="closeLightbox()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="lightbox-img" id="lightboxImg"></div>
    <div class="lightbox-body">
      <div class="lightbox-tag" id="lightboxTag"></div>
      <h2 id="lightboxTitle"></h2>
      <p id="lightboxDesc"></p>
      <div class="lightbox-meta">
        <span id="lightboxDate"></span>
        <span>Jabatan Agama Islam Selangor</span>
      </div>
    </div>
  </div>
</div>


<!-- Login modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeOnOverlay(event)">
  <div class="modal" id="loginModal">

    <button class="modal-close" onclick="closeLogin()" aria-label="Tutup">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="modal-header">
      <img src="assets/img/logo-green.png" alt="NUR-PUSARA" onerror="this.style.display='none'">
      <div class="modal-header-text">
        <div class="modal-header-name">NUR-PUSARA System</div>
        <div class="modal-header-sub">Jabatan Agama Islam Selangor</div>
      </div>
    </div>

    <h2>Log Masuk</h2>
    <p class="modal-tagline">Untuk kakitangan dan pentadbir JAIS sahaja</p>

    <?php if ($error): ?>
      <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="modal-field">
        <label>Nama Pengguna</label>
        <input type="text" name="nama_pengguna" placeholder="Masukkan nama pengguna" required autofocus>
      </div>
      <div class="modal-field">
        <label>Kata Laluan</label>
        <input type="password" name="kata_laluan" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Log Masuk</button>
    </form>

  </div>
</div>


<script>
  /* Theme toggle */
  var MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SUN  = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

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

  /* Login modal */
  function openLogin() {
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeLogin() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = '';
  }
  function closeOnOverlay(e) {
    if (e.target === document.getElementById('modalOverlay')) closeLogin();
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLogin();
  });

  /* Auto-open modal on login error */
  <?php if ($error): ?>openLogin();<?php endif; ?>

  /* ── Lightbox for bulletin posters ── */
  var posters = {
    1: {
      tag: 'Pengumuman Rasmi',
      title: 'Pelancaran Sistem Pengurusan Tanah Perkuburan Islam Digital',
      desc: 'Jabatan Agama Islam Selangor (JAIS) dengan penuh kebanggaan mengumumkan pelancaran rasmi Sistem NUR-PUSARA — satu platform digital bersepadu bagi memudahkan pengurusan rekod perkuburan Islam, pencarian maklumat si mati dan pemetaan lot perkuburan di seluruh Selangor. Sistem ini dibangunkan sebagai sebahagian daripada usaha transformasi digital JAIS bagi meningkatkan kualiti perkhidmatan kepada waris dan orang awam.',
      date: '1 Jun 2026',
      bg: 'linear-gradient(135deg, #20331B 0%, #3a5c2e 50%, #C3CA92 100%)',
      icon: '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>'
    },
    2: {
      tag: 'Perkhidmatan Awam',
      title: 'Kini Orang Awam Boleh Mencari Maklumat Arwah Secara Dalam Talian',
      desc: 'Mulai hari ini, waris dan orang awam boleh mengakses maklumat pengebumian secara dalam talian melalui portal carian awam NUR-PUSARA. Cari lokasi kubur, nombor lot, seksyen dan koordinat GPS tapak perkuburan tanpa perlu hadir ke pejabat JAIS. Perkhidmatan ini adalah percuma dan boleh diakses bila-bila masa melalui laman web ini.',
      date: '15 Jun 2026',
      bg: 'linear-gradient(135deg, #1a2a35 0%, #2d4a5c 50%, #7ab8d4 100%)',
      icon: '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'
    }
  };

  function openLightbox(id) {
    var p = posters[id];
    if (!p) return;
    document.getElementById('lightboxImg').style.background = p.bg;
    document.getElementById('lightboxImg').innerHTML =
      '<div style="text-align:center;color:white;position:relative;z-index:1;">' +
      '<div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,0.15);border:2px solid rgba(255,255,255,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 0.8rem;">' +
      '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + p.icon + '</svg></div>' +
      '<div style="font-family:Cormorant Garamond,serif;font-size:1.2rem;font-weight:700;line-height:1.3;">' + p.title + '</div>' +
      '<div style="font-size:0.68rem;opacity:0.65;margin-top:0.4rem;letter-spacing:0.05em;">JABATAN AGAMA ISLAM SELANGOR</div></div>';
    document.getElementById('lightboxTag').textContent  = p.tag;
    document.getElementById('lightboxTitle').textContent = p.title;
    document.getElementById('lightboxDesc').textContent  = p.desc;
    document.getElementById('lightboxDate').textContent  = p.date;
    document.getElementById('lightbox').classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
    document.body.style.overflow = '';
  }

  function closeLightboxOverlay(e) {
    if (e.target === document.getElementById('lightbox')) closeLightbox();
  }

  /* Close lightbox with Escape (separate from modal) */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeLightbox();
      closeLogin();
    }
  });

</script>

</body>
</html>
