<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Produk.php';
requireLogin();

$user        = currentUser();
$produkClass = new Produk($conn);
$action      = $_GET['action'] ?? 'list';
$msg         = '';
$msgType     = 'success';
$hasil       = null;

// Ambil daftar negara untuk dropdown
$negaraList = $conn->query("SELECT negara_id, nama_negara, kode_negara FROM negara ORDER BY nama_negara")->fetch_all(MYSQLI_ASSOC);
$kategoriList = ['Makanan & Minuman','Kosmetik & Perawatan','Fashion & Pakaian','Elektronik','Obat & Suplemen','Jasa / Layanan','Pariwisata','Lainnya'];

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['form_action'] === 'tambah_analisis') {
        // Validasi
        if (empty($_POST['nama_produk']) || empty($_POST['negara_id']) || empty($_POST['nama_merek']) || empty($_POST['jenis_kemasan'])) {
            $msg = 'Semua field wajib diisi.';
            $msgType = 'danger';
            $action = 'tambah';
        } else {
            $produkId = $produkClass->tambahProduk(
                $user['id'],
                (int)$_POST['negara_id'],
                trim($_POST['nama_produk']),
                trim($_POST['kategori_produk']),
                trim($_POST['deskripsi_produk'])
            );

            $hasil = $produkClass->jalankanAnalisisLengkap(
                $produkId,
                trim($_POST['nama_merek']),
                trim($_POST['jenis_kemasan'])
            );

            $msg    = 'Analisis berhasil dijalankan!';
            $action = 'hasil';
        }
    }
}

$pageTitle = 'Produk & Analisis';
include '../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-box-seam me-2" style="color:var(--accent)"></i>Produk &amp; Analisis</h1>
  <p>Product Cultural Fit Analyzer · Brand Name Screening · Packaging & Labeling Compliance</p>
</div>

<?php if ($msg && $action !== 'hasil'): ?>
<div class="alert-custom alert-<?= $msgType ?>-custom">
  <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if ($action === 'tambah'): ?>
<!-- ═══════════════════════ FORM TAMBAH & ANALISIS ═══════════════════════ -->
<div class="row g-3">
  <div class="col-md-8">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-plus-circle" style="color:var(--accent)"></i> Input Data Produk &amp; Analisis</div>
      <div class="card-body-dark">
        <form method="POST">
          <input type="hidden" name="form_action" value="tambah_analisis">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Nama Produk *</label>
              <input type="text" name="nama_produk" class="form-control" placeholder="misal: Teh Tarik Kita" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori Produk *</label>
              <select name="kategori_produk" class="form-select" required>
                <?php foreach($kategoriList as $k): ?>
                <option><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Negara Tujuan Lokalisasi *</label>
            <select name="negara_id" class="form-select" required>
              <option value="">-- Pilih Negara --</option>
              <?php foreach($negaraList as $n): ?>
              <option value="<?= $n['negara_id'] ?>"><?= htmlspecialchars($n['nama_negara']) ?> (<?= $n['kode_negara'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Deskripsi Produk *</label>
            <textarea name="deskripsi_produk" class="form-control" rows="3"
              placeholder="Deskripsikan produk: bahan, manfaat, target pasar, nilai jual utama..."></textarea>
          </div>

          <hr style="border-color:var(--border);margin:1.5rem 0">
          <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
            <i class="bi bi-info-circle me-1"></i>Data di bawah untuk Brand Screening &amp; Packaging Compliance
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Nama Merek *</label>
              <input type="text" name="nama_merek" class="form-control" placeholder="misal: KitaTea" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Jenis Kemasan *</label>
              <input type="text" name="jenis_kemasan" class="form-control"
                placeholder="misal: Sachet berlabel halal, bahasa lokal" required>
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn-primary-custom">
              <i class="bi bi-play-circle"></i> Jalankan Analisis Lengkap
            </button>
            <a href="produk.php" class="btn-outline-custom">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-lightbulb" style="color:var(--warning)"></i> Tips Pengisian</div>
      <div class="card-body-dark" style="font-size:.82rem;color:var(--text-muted);line-height:1.8">
        <strong style="color:var(--text-main)">Deskripsi Produk:</strong><br>
        Masukkan kata kunci seperti "halal", "keluarga", bahan utama, dan posisi produk. Ini mempengaruhi skor analisis.<br><br>
        <strong style="color:var(--text-main)">Nama Merek:</strong><br>
        Sistem akan cek potensi konotasi negatif dalam bahasa negara tujuan.<br><br>
        <strong style="color:var(--text-main)">Jenis Kemasan:</strong><br>
        Contoh: <code style="font-size:.78rem">sachet berlabel halal bahasa Indonesia</code> atau <code style="font-size:.78rem">botol kaca FDA nutrition</code>
      </div>
    </div>
  </div>
</div>

<?php elseif ($action === 'hasil' && $hasil): ?>
<!-- ═══════════════════════ HASIL ANALISIS ═══════════════════════ -->
<div class="alert-custom alert-success-custom mb-4">
  <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?> Berikut hasil analisis lengkap untuk produk yang baru ditambahkan.
</div>

<div class="row g-3 mb-4">
  <!-- 1. Cultural Fit -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-globe" style="color:var(--accent)"></i> Cultural Fit Analyzer</div>
      <div class="card-body-dark text-center">
        <?php
          $skor  = $hasil['cultural']['skor'];
          $cls   = $skor >= 75 ? 'skor-high' : ($skor >= 50 ? 'skor-medium' : 'skor-low');
          $risiko = $hasil['cultural']['risiko'];
        ?>
        <div class="skor-circle <?= $cls ?> mb-3"><?= $skor ?></div>
        <div class="mb-2">
          <span class="badge-risiko badge-<?= strtolower($risiko) ?>"><?= $risiko ?> Risk</span>
        </div>
        <hr style="border-color:var(--border)">
        <div style="text-align:left;font-size:.8rem;color:var(--text-muted);line-height:1.8;white-space:pre-line">
          <?= htmlspecialchars($hasil['cultural']['catatan']) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Brand Screening -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-shield-check" style="color:var(--accent)"></i> Brand Name Screening</div>
      <div class="card-body-dark text-center">
        <?php $bs = $hasil['brand']['status']; ?>
        <div style="font-size:3rem;margin-bottom:.5rem">
          <?= $bs==='Aman' ? '✅' : ($bs==='Berisiko' ? '🚨' : '⚠️') ?>
        </div>
        <span class="badge-risiko badge-<?= $bs==='Aman'?'aman':'berisiko' ?> mb-3 d-inline-block">
          <?= $bs ?>
        </span>
        <hr style="border-color:var(--border)">
        <div style="text-align:left;font-size:.8rem;color:var(--text-muted);line-height:1.7">
          <?= htmlspecialchars($hasil['brand']['rekomendasi']) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Packaging -->
  <div class="col-md-4">
    <div class="card-dark h-100">
      <div class="card-header-dark"><i class="bi bi-box" style="color:var(--accent)"></i> Packaging Compliance</div>
      <div class="card-body-dark text-center">
        <?php $ps = $hasil['packaging']['status']; ?>
        <div style="font-size:3rem;margin-bottom:.5rem">
          <?= $ps==='Lulus' ? '✅' : ($ps==='Tidak Lulus' ? '❌' : '⚠️') ?>
        </div>
        <span class="badge-risiko badge-<?= $ps==='Lulus'?'lulus':($ps==='Perlu Revisi'?'perlu':'berisiko') ?> mb-3 d-inline-block">
          <?= $ps ?>
        </span>
        <hr style="border-color:var(--border)">
        <div style="text-align:left;font-size:.8rem;color:var(--text-muted);line-height:1.7;white-space:pre-line">
          <?= htmlspecialchars($hasil['packaging']['catatan']) ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex gap-2">
  <a href="produk.php?action=tambah" class="btn-primary-custom">
    <i class="bi bi-plus"></i> Analisis Produk Lain
  </a>
  <a href="kampanye.php?action=tambah" class="btn-outline-custom">
    <i class="bi bi-megaphone"></i> Adaptasi Kampanye
  </a>
  <a href="laporan.php" class="btn-outline-custom">
    <i class="bi bi-file-earmark-text"></i> Buat Laporan
  </a>
  <a href="produk.php" class="btn-outline-custom ms-auto">
    <i class="bi bi-list"></i> Semua Produk
  </a>
</div>

<?php else: ?>
<!-- ═══════════════════════ LIST PRODUK ═══════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div style="color:var(--text-muted);font-size:.875rem">Daftar produk yang telah dianalisis</div>
  <a href="produk.php?action=tambah" class="btn-primary-custom">
    <i class="bi bi-plus-circle"></i> Analisis Produk Baru
  </a>
</div>

<div class="card-dark">
  <div style="overflow-x:auto">
  <table class="table-dark-custom">
    <thead>
      <tr>
        <th>#</th><th>Produk</th><th>Negara</th>
        <th>Skor Budaya</th><th>Brand</th><th>Packaging</th><th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $produkList = $produkClass->getAll($user['id'], $user['role']);
    if (empty($produkList)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem">
        Belum ada produk. <a href="produk.php?action=tambah" style="color:var(--accent)">Analisis produk pertama</a>
      </td></tr>
    <?php else: foreach ($produkList as $i => $p): ?>
      <tr>
        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
        <td>
          <div style="font-weight:600"><?= htmlspecialchars($p['nama_produk']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['kategori_produk']) ?></div>
        </td>
        <td><?= htmlspecialchars($p['nama_negara']) ?></td>
        <td>
          <?php if ($p['skor_kesesuaian']): ?>
            <span class="badge-risiko badge-<?= strtolower($p['tingkat_risiko']) ?>">
              <?= $p['skor_kesesuaian'] ?>/100
            </span>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= $p['tingkat_risiko'] ?></div>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:.8rem">–</span>
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
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
