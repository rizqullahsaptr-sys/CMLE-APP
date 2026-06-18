<?php
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin();
$user = currentUser();

// Statistik ringkasan
$totalProduk   = $conn->query("SELECT COUNT(*) as c FROM produk WHERE user_id = {$user['id']}")->fetch_assoc()['c'];
$totalLaporan  = $conn->query("SELECT COUNT(*) as c FROM laporan WHERE user_id = {$user['id']}")->fetch_assoc()['c'];
$totalKampanye = $conn->query("SELECT COUNT(*) as c FROM kampanye k JOIN produk p ON k.produk_id = p.produk_id WHERE p.user_id = {$user['id']}")->fetch_assoc()['c'];
$avgSkor       = $conn->query("SELECT AVG(ab.skor_kesesuaian) as avg FROM analisis_budaya ab JOIN produk p ON ab.produk_id = p.produk_id WHERE p.user_id = {$user['id']}")->fetch_assoc()['avg'];

$produkTerbaru = $conn->query(
    "SELECT p.nama_produk, p.kategori_produk, n.nama_negara,
            ab.skor_kesesuaian, ab.tingkat_risiko,
            m.status_screening, pk.status_compliance, p.tanggal_input
     FROM produk p
     JOIN negara n ON p.negara_id = n.negara_id
     LEFT JOIN analisis_budaya ab ON p.produk_id = ab.produk_id
     LEFT JOIN merek m ON p.produk_id = m.produk_id
     LEFT JOIN packaging pk ON p.produk_id = pk.produk_id
     WHERE p.user_id = {$user['id']}
     ORDER BY p.tanggal_input DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$risikoCount = $conn->query(
    "SELECT ab.tingkat_risiko, COUNT(*) as c
     FROM analisis_budaya ab
     JOIN produk p ON ab.produk_id = p.produk_id
     WHERE p.user_id = {$user['id']}
     GROUP BY ab.tingkat_risiko"
)->fetch_all(MYSQLI_ASSOC);

$risikoMap = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0];
foreach ($risikoCount as $r) $risikoMap[$r['tingkat_risiko']] = $r['c'];

$pageTitle = 'Dashboard';
include '../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-grid-1x2 me-2" style="color:var(--accent)"></i>Dashboard</h1>
  <p>Selamat datang, <strong><?= htmlspecialchars($user['nama']) ?></strong>. Berikut ringkasan aktivitas lokalisasi Anda.</p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Total Produk</div>
          <div class="stat-value"><?= $totalProduk ?></div>
        </div>
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card success">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Rata-rata Skor</div>
          <div class="stat-value"><?= $avgSkor ? round($avgSkor,1) : '–' ?></div>
        </div>
        <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card warning">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Kampanye</div>
          <div class="stat-value"><?= $totalKampanye ?></div>
        </div>
        <div class="stat-icon"><i class="bi bi-megaphone"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card danger">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Laporan</div>
          <div class="stat-value"><?= $totalLaporan ?></div>
        </div>
        <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Distribusi Risiko -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-pie-chart" style="color:var(--accent)"></i> Distribusi Risiko Budaya</div>
      <div class="card-body-dark">
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small style="color:var(--success)">Rendah</small>
            <small style="color:var(--text-muted)"><?= $risikoMap['Rendah'] ?></small>
          </div>
          <div style="background:var(--bg-input);border-radius:4px;height:8px;">
            <div style="background:var(--success);height:100%;border-radius:4px;width:<?= $totalProduk ? ($risikoMap['Rendah']/$totalProduk*100) : 0 ?>%"></div>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small style="color:var(--warning)">Sedang</small>
            <small style="color:var(--text-muted)"><?= $risikoMap['Sedang'] ?></small>
          </div>
          <div style="background:var(--bg-input);border-radius:4px;height:8px;">
            <div style="background:var(--warning);height:100%;border-radius:4px;width:<?= $totalProduk ? ($risikoMap['Sedang']/$totalProduk*100) : 0 ?>%"></div>
          </div>
        </div>
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <small style="color:var(--danger)">Tinggi</small>
            <small style="color:var(--text-muted)"><?= $risikoMap['Tinggi'] ?></small>
          </div>
          <div style="background:var(--bg-input);border-radius:4px;height:8px;">
            <div style="background:var(--danger);height:100%;border-radius:4px;width:<?= $totalProduk ? ($risikoMap['Tinggi']/$totalProduk*100) : 0 ?>%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-lightning" style="color:var(--accent)"></i> Aksi Cepat</div>
      <div class="card-body-dark d-flex flex-column gap-2">
        <a href="produk.php?action=tambah" class="btn-primary-custom">
          <i class="bi bi-plus-circle"></i> Analisis Produk Baru
        </a>
        <a href="kampanye.php?action=tambah" class="btn-outline-custom">
          <i class="bi bi-megaphone"></i> Adaptasi Kampanye
        </a>
        <a href="consumer.php" class="btn-outline-custom">
          <i class="bi bi-people"></i> Lihat Consumer Insight
        </a>
        <a href="laporan.php" class="btn-outline-custom">
          <i class="bi bi-file-earmark-text"></i> Buat Laporan
        </a>
      </div>
    </div>
  </div>

  <!-- Info Panel -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-info-circle" style="color:var(--accent)"></i> Tentang CMLE</div>
      <div class="card-body-dark" style="font-size:.85rem;color:var(--text-muted);line-height:1.7">
        <p>CMLE membantu Anda menganalisis kesesuaian produk dan kampanye marketing terhadap budaya pasar lokal di berbagai negara.</p>
        <div class="d-flex flex-column gap-1 mt-2">
          <span><i class="bi bi-check2 me-1" style="color:var(--success)"></i>Cultural Fit Analyzer</span>
          <span><i class="bi bi-check2 me-1" style="color:var(--success)"></i>Brand Name Screening</span>
          <span><i class="bi bi-check2 me-1" style="color:var(--success)"></i>Packaging Compliance</span>
          <span><i class="bi bi-check2 me-1" style="color:var(--success)"></i>Campaign Adaptation</span>
          <span><i class="bi bi-check2 me-1" style="color:var(--success)"></i>Consumer Insight</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Produk Terbaru -->
<div class="card-dark">
  <div class="card-header-dark">
    <i class="bi bi-clock-history" style="color:var(--accent)"></i> Produk Terbaru
    <a href="produk.php" class="btn-outline-custom ms-auto" style="font-size:.78rem;padding:.3rem .7rem">Lihat Semua</a>
  </div>
  <div style="overflow-x:auto">
  <table class="table-dark-custom">
    <thead>
      <tr>
        <th>Produk</th><th>Negara</th><th>Skor Budaya</th>
        <th>Brand</th><th>Packaging</th><th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($produkTerbaru)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">
        Belum ada produk. <a href="produk.php?action=tambah" style="color:var(--accent)">Tambah sekarang</a>
      </td></tr>
    <?php else: foreach ($produkTerbaru as $p): ?>
      <tr>
        <td>
          <div style="font-weight:600"><?= htmlspecialchars($p['nama_produk']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['kategori_produk']) ?></div>
        </td>
        <td><?= htmlspecialchars($p['nama_negara']) ?></td>
        <td>
          <?php if ($p['skor_kesesuaian']): ?>
            <span class="badge-risiko badge-<?= strtolower($p['tingkat_risiko']) ?>">
              <?= $p['skor_kesesuaian'] ?> — <?= $p['tingkat_risiko'] ?>
            </span>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:.8rem">Belum dianalisis</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($p['status_screening']): ?>
            <span class="badge-risiko badge-<?= $p['status_screening']==='Aman'?'aman':'berisiko' ?>">
              <?= $p['status_screening'] ?>
            </span>
          <?php else: ?><span style="color:var(--text-muted);font-size:.8rem">–</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($p['status_compliance']): ?>
            <span class="badge-risiko badge-<?= $p['status_compliance']==='Lulus'?'lulus':($p['status_compliance']==='Perlu Revisi'?'perlu':'berisiko') ?>">
              <?= $p['status_compliance'] ?>
            </span>
          <?php else: ?><span style="color:var(--text-muted);font-size:.8rem">–</span>
          <?php endif; ?>
        </td>
        <td style="color:var(--text-muted);font-size:.8rem"><?= $p['tanggal_input'] ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
