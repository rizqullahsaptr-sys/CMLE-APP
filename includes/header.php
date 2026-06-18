<?php
// includes/header.php
// Panggil setelah requireLogin() di setiap halaman
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'CMLE' ?> — CMLE</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --bg-dark:   #0d1117;
    --bg-card:   #161b22;
    --bg-input:  #1c2128;
    --bg-sidebar:#0d1117;
    --accent:    #2f81f7;
    --accent-glow: rgba(47,129,247,0.12);
    --text-main: #e6edf3;
    --text-muted:#8b949e;
    --border:    #30363d;
    --success:   #3fb950;
    --warning:   #d29922;
    --danger:    #f85149;
    --sidebar-w: 240px;
  }
  * { box-sizing: border-box; }
  body {
    background: var(--bg-dark);
    color: var(--text-main);
    font-family: 'Segoe UI', system-ui, sans-serif;
    margin: 0;
    min-height: 100vh;
  }

  /* Sidebar */
  .sidebar {
    position: fixed; top: 0; left: 0;
    width: var(--sidebar-w); height: 100vh;
    background: var(--bg-card);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    z-index: 100;
    overflow-y: auto;
  }
  .sidebar-logo {
    padding: 1.25rem 1.25rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: .6rem;
  }
  .sidebar-logo i { color: var(--accent); font-size: 1.4rem; }
  .sidebar-logo span { font-weight: 700; font-size: 1rem; letter-spacing: -.3px; }
  .sidebar-logo small { display: block; color: var(--text-muted); font-size: .7rem; font-weight: 400; }

  .nav-section { padding: .75rem 1rem .25rem; }
  .nav-section-label {
    color: var(--text-muted);
    font-size: .65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .8px;
  }
  .nav-item-custom {
    display: flex; align-items: center; gap: .65rem;
    padding: .55rem .85rem;
    border-radius: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: .875rem;
    margin: .15rem 0;
    transition: background .15s, color .15s;
  }
  .nav-item-custom i { font-size: 1rem; width: 18px; }
  .nav-item-custom:hover {
    background: rgba(47,129,247,.1);
    color: var(--text-main);
  }
  .nav-item-custom.active {
    background: rgba(47,129,247,.15);
    color: var(--accent);
    font-weight: 600;
  }
  .nav-item-custom.active i { color: var(--accent); }

  .sidebar-user {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid var(--border);
  }
  .sidebar-user .user-name { font-size: .875rem; font-weight: 600; }
  .sidebar-user .user-role {
    font-size: .7rem;
    color: var(--text-muted);
    text-transform: capitalize;
    background: rgba(47,129,247,.12);
    color: var(--accent);
    padding: .1rem .4rem;
    border-radius: 4px;
    display: inline-block;
    margin-top: .2rem;
  }
  .btn-logout {
    display: flex; align-items: center; gap: .4rem;
    margin-top: .6rem;
    padding: .4rem .8rem;
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    border-radius: 6px;
    font-size: .8rem;
    cursor: pointer;
    text-decoration: none;
    width: 100%;
    justify-content: center;
    transition: all .15s;
  }
  .btn-logout:hover {
    border-color: var(--danger);
    color: var(--danger);
    background: rgba(248,81,73,.07);
  }

  /* Main content */
  .main-content {
    margin-left: var(--sidebar-w);
    min-height: 100vh;
    padding: 2rem;
  }
  .page-header {
    margin-bottom: 1.75rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
  }
  .page-header h1 { font-size: 1.35rem; font-weight: 700; }
  .page-header p  { color: var(--text-muted); font-size: .875rem; margin: .25rem 0 0; }

  /* Cards */
  .card-dark {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
  }
  .card-dark .card-header-dark {
    padding: .85rem 1.25rem;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    font-size: .9rem;
    display: flex; align-items: center; gap: .5rem;
  }
  .card-dark .card-body-dark { padding: 1.25rem; }

  /* Stat cards */
  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1.25rem;
    position: relative;
    overflow: hidden;
  }
  .stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: var(--accent);
  }
  .stat-card.success::before { background: var(--success); }
  .stat-card.warning::before { background: var(--warning); }
  .stat-card.danger::before  { background: var(--danger); }
  .stat-label { color: var(--text-muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; }
  .stat-value { font-size: 2rem; font-weight: 700; line-height: 1.2; margin: .25rem 0; }
  .stat-icon  { font-size: 1.5rem; color: var(--text-muted); }

  /* Badges */
  .badge-risiko {
    padding: .3rem .65rem;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
  }
  .badge-rendah  { background: rgba(63,185,80,.15); color: var(--success); }
  .badge-sedang  { background: rgba(210,153,34,.15); color: var(--warning); }
  .badge-tinggi  { background: rgba(248,81,73,.15);  color: var(--danger); }
  .badge-aman    { background: rgba(63,185,80,.15);  color: var(--success); }
  .badge-lulus   { background: rgba(63,185,80,.15);  color: var(--success); }
  .badge-berisiko{ background: rgba(248,81,73,.15);  color: var(--danger); }
  .badge-perlu   { background: rgba(210,153,34,.15); color: var(--warning); }

  /* Table */
  .table-dark-custom {
    color: var(--text-main);
    border-color: var(--border);
    font-size: .875rem;
    width: 100%;
  }
  .table-dark-custom thead th {
    background: rgba(255,255,255,.03);
    border-color: var(--border);
    color: var(--text-muted);
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: .75rem 1rem;
  }
  .table-dark-custom tbody td {
    border-color: var(--border);
    padding: .75rem 1rem;
    vertical-align: middle;
  }
  .table-dark-custom tbody tr:hover { background: rgba(255,255,255,.02); }

  /* Form */
  .form-label { color: var(--text-muted); font-size: .85rem; }
  .form-control, .form-select {
    background: var(--bg-input);
    border: 1px solid var(--border);
    color: var(--text-main);
    border-radius: 8px;
  }
  .form-control:focus, .form-select:focus {
    background: var(--bg-input);
    border-color: var(--accent);
    color: var(--text-main);
    box-shadow: 0 0 0 3px var(--accent-glow);
  }
  .form-control::placeholder { color: var(--text-muted); }

  /* Buttons */
  .btn-primary-custom {
    background: var(--accent);
    border: none; color: #fff;
    border-radius: 8px;
    padding: .6rem 1.2rem;
    font-weight: 600;
    font-size: .875rem;
    cursor: pointer;
    transition: opacity .2s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .4rem;
  }
  .btn-primary-custom:hover { opacity: .85; color: #fff; }

  .btn-outline-custom {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    border-radius: 8px;
    padding: .55rem 1rem;
    font-size: .875rem;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .4rem;
  }
  .btn-outline-custom:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-glow);
  }

  /* Alert */
  .alert-custom {
    border-radius: 8px;
    padding: .75rem 1rem;
    font-size: .875rem;
    margin-bottom: 1rem;
    display: flex; align-items: flex-start; gap: .5rem;
  }
  .alert-success-custom { background: rgba(63,185,80,.1);  border: 1px solid rgba(63,185,80,.25);  color: var(--success); }
  .alert-danger-custom  { background: rgba(248,81,73,.1);  border: 1px solid rgba(248,81,73,.25);  color: var(--danger); }
  .alert-warning-custom { background: rgba(210,153,34,.1); border: 1px solid rgba(210,153,34,.25); color: var(--warning); }
  .alert-info-custom    { background: rgba(47,129,247,.1); border: 1px solid rgba(47,129,247,.25); color: var(--accent); }

  /* Skor gauge */
  .skor-circle {
    width: 100px; height: 100px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 700;
    border: 4px solid;
    margin: 0 auto;
  }
  .skor-high   { border-color: var(--success); color: var(--success); }
  .skor-medium { border-color: var(--warning); color: var(--warning); }
  .skor-low    { border-color: var(--danger);  color: var(--danger); }

  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .main-content { margin-left: 0; padding: 1rem; }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <i class="bi bi-globe2"></i>
    <div>
      <span>CMLE</span>
      <small>Cultural Marketing Engine</small>
    </div>
  </div>

  <div class="nav-section">
    <div class="nav-section-label">Utama</div>
    <a href="dashboard.php" class="nav-item-custom <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-section-label">Analisis</div>
    <a href="produk.php" class="nav-item-custom <?= $currentPage==='produk'?'active':'' ?>">
      <i class="bi bi-box-seam"></i> Produk &amp; Analisis
    </a>
    <a href="kampanye.php" class="nav-item-custom <?= $currentPage==='kampanye'?'active':'' ?>">
      <i class="bi bi-megaphone"></i> Kampanye Marketing
    </a>
    <a href="consumer.php" class="nav-item-custom <?= $currentPage==='consumer'?'active':'' ?>">
      <i class="bi bi-people"></i> Consumer Insight
    </a>
  </div>

  <div class="nav-section">
    <div class="nav-section-label">Laporan</div>
    <a href="laporan.php" class="nav-item-custom <?= $currentPage==='laporan'?'active':'' ?>">
      <i class="bi bi-file-earmark-text"></i> Laporan Lokalisasi
    </a>
  </div>

  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($user['nama']) ?></div>
    <span class="user-role"><?= str_replace('_',' ', $user['role']) ?></span>
    <a href="logout.php" class="btn-logout">
      <i class="bi bi-box-arrow-left"></i> Keluar
    </a>
  </div>
</nav>

<div class="main-content">
