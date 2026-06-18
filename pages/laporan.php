<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ConsumerInsightLaporan.php';
requireLogin();

$user         = currentUser();
$laporanClass = new Laporan($conn);
$msg          = '';
$msgType      = 'success';
$detailLaporan = null;

// Produk milik user yang sudah dianalisis
$produkAnalisis = $conn->query(
    "SELECT p.produk_id, p.nama_produk, n.nama_negara, ab.skor_kesesuaian, ab.tingkat_risiko
     FROM produk p
     JOIN negara n ON p.negara_id = n.negara_id
     JOIN analisis_budaya ab ON p.produk_id = ab.produk_id
     WHERE p.user_id = {$user['id']}
     ORDER BY p.nama_produk"
)->fetch_all(MYSQLI_ASSOC);

// Generate laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produk_id'])) {
    $laporanId = $laporanClass->generateLaporan((int)$_POST['produk_id'], $user['id']);
    $detailLaporan = $laporanClass->getById($laporanId);
    $msg     = 'Laporan berhasil dibuat!';
    $msgType = 'success';
}

// Lihat detail
if (isset($_GET['id'])) {
    $detailLaporan = $laporanClass->getById((int)$_GET['id']);
}

$allLaporan = $laporanClass->getAll($user['id'], $user['role']);

$pageTitle = 'Laporan Lokalisasi';
include '../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-file-earmark-text me-2" style="color:var(--accent)"></i>Laporan Lokalisasi</h1>
  <p>Generate dan kelola laporan hasil analisis Cultural Fit, Brand Screening, Packaging, dan Kampanye</p>
</div>

<?php if ($msg): ?>
<div class="alert-custom alert-<?= $msgType ?>-custom">
  <i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <!-- Generate Laporan -->
  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-file-earmark-plus" style="color:var(--accent)"></i> Generate Laporan Baru</div>
      <div class="card-body-dark">
        <?php if (empty($produkAnalisis)): ?>
          <div class="alert-custom alert-warning-custom">
            <i class="bi bi-exclamation-triangle"></i>
            Belum ada produk yang dianalisis. <a href="produk.php?action=tambah" style="color:var(--warning)">Analisis produk dulu.</a>
          </div>
        <?php else: ?>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Pilih Produk</label>
              <select name="produk_id" class="form-select" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach($produkAnalisis as $p): ?>
                <option value="<?= $p['produk_id'] ?>">
                  <?= htmlspecialchars($p['nama_produk']) ?> → <?= htmlspecialchars($p['nama_negara']) ?>
                  (<?= $p['tingkat_risiko'] ?>)
                </option>
                <?php endforeach; ?>
              </select>
              <div style="font-size:.75rem;color:var(--text-muted);margin-top:.4rem">
                Laporan akan mencakup semua analisis yang tersedia untuk produk ini.
              </div>
            </div>
            <button type="submit" class="btn-primary-custom w-100">
              <i class="bi bi-file-earmark-text"></i> Generate Laporan Lengkap
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Preview Laporan Terbaru / Detail -->
  <div class="col-md-8">
    <?php if ($detailLaporan): ?>
    <div class="card-dark">
      <div class="card-header-dark">
        <i class="bi bi-file-earmark-check" style="color:var(--success)"></i>
        <?= htmlspecialchars($detailLaporan['judul_laporan']) ?>
        <span style="margin-left:auto;font-size:.75rem;color:var(--text-muted)"><?= $detailLaporan['tanggal_laporan'] ?></span>
      </div>
      <div class="card-body-dark">
        <div style="background:var(--bg-input);border-radius:8px;padding:1.25rem;font-family:monospace;font-size:.8rem;line-height:1.8;color:var(--text-muted);white-space:pre-wrap;max-height:400px;overflow-y:auto">
<?= htmlspecialchars($detailLaporan['isi_laporan']) ?>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button onclick="copyLaporan()" class="btn-outline-custom">
            <i class="bi bi-clipboard"></i> Salin Laporan
          </button>
          <button onclick="printLaporan()" class="btn-outline-custom">
            <i class="bi bi-printer"></i> Cetak
          </button>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-info-circle" style="color:var(--accent)"></i> Cara Kerja Laporan</div>
      <div class="card-body-dark" style="font-size:.875rem;color:var(--text-muted);line-height:2">
        <div><i class="bi bi-1-circle me-2" style="color:var(--accent)"></i>Pilih produk yang sudah memiliki hasil analisis</div>
        <div><i class="bi bi-2-circle me-2" style="color:var(--accent)"></i>Klik "Generate Laporan Lengkap"</div>
        <div><i class="bi bi-3-circle me-2" style="color:var(--accent)"></i>Sistem merangkum Cultural Fit, Brand Screening, Packaging, dan Kampanye</div>
        <div><i class="bi bi-4-circle me-2" style="color:var(--accent)"></i>Laporan tersimpan dan bisa disalin atau dicetak</div>
        <div><i class="bi bi-check-circle me-2" style="color:var(--success)"></i>Brand Manager dapat mereview laporan dari panel ini</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Riwayat Laporan -->
<div class="card-dark">
  <div class="card-header-dark"><i class="bi bi-clock-history" style="color:var(--accent)"></i> Riwayat Laporan</div>
  <div style="overflow-x:auto">
  <table class="table-dark-custom">
    <thead>
      <tr><th>#</th><th>Judul Laporan</th><th>Produk</th><th>Negara</th><th>Jenis</th><th>Tanggal</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php if (empty($allLaporan)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem">
        Belum ada laporan. Generate laporan pertama Anda di atas.
      </td></tr>
    <?php else: foreach($allLaporan as $i => $l): ?>
      <tr>
        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
        <td style="font-size:.85rem"><strong><?= htmlspecialchars(mb_substr($l['judul_laporan'],0,50)) ?></strong></td>
        <td style="font-size:.82rem"><?= htmlspecialchars($l['nama_produk']) ?></td>
        <td style="font-size:.82rem"><?= htmlspecialchars($l['nama_negara']) ?></td>
        <td>
          <span style="background:rgba(47,129,247,.1);color:var(--accent);padding:.2rem .6rem;border-radius:20px;font-size:.72rem">
            <?= $l['jenis_laporan'] ?>
          </span>
        </td>
        <td style="color:var(--text-muted);font-size:.8rem"><?= $l['tanggal_laporan'] ?></td>
        <td>
          <a href="laporan.php?id=<?= $l['laporan_id'] ?>" class="btn-outline-custom" style="padding:.3rem .65rem;font-size:.78rem">
            <i class="bi bi-eye"></i> Lihat
          </a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
function copyLaporan() {
    const teks = document.querySelector('pre, [style*="monospace"]')?.innerText || '';
    navigator.clipboard.writeText(teks).then(() => alert('Laporan disalin ke clipboard!'));
}
function printLaporan() { window.print(); }
</script>

<?php include '../includes/footer.php'; ?>
