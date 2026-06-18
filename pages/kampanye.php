<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/Kampanye.php';
requireLogin();

$user          = currentUser();
$kampanyeClass = new Kampanye($conn);
$action        = $_GET['action'] ?? 'list';
$msg           = '';
$msgType       = 'success';
$hasilAdaptasi = null;

// Ambil produk milik user
$produkList = $conn->query(
    "SELECT p.produk_id, p.nama_produk, n.nama_negara, n.parameter_budaya
     FROM produk p JOIN negara n ON p.negara_id = n.negara_id
     WHERE p.user_id = {$user['id']} ORDER BY p.nama_produk"
)->fetch_all(MYSQLI_ASSOC);

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produkId = (int)$_POST['produk_id'];
    $nama     = trim($_POST['nama_kampanye']);
    $elemen   = trim($_POST['elemen_kampanye']);

    if (!$produkId || !$nama || !$elemen) {
        $msg = 'Semua field wajib diisi.';
        $msgType = 'danger';
        $action = 'tambah';
    } else {
        // Ambil parameter budaya produk terpilih
        $produkData = null;
        foreach ($produkList as $p) {
            if ($p['produk_id'] === $produkId) { $produkData = $p; break; }
        }

        $param = json_decode($produkData['parameter_budaya'] ?? '{}', true);

        // Preview analisis dulu sebelum simpan
        $analisisPreview = $kampanyeClass->analisisSensitivitasBudaya($elemen, $param);
        $hasilTeks       = $kampanyeClass->generateHasilAdaptasi($elemen, $analisisPreview['temuan']);

        // Simpan ke DB
        $kampanyeClass->tambahKampanye($produkId, $nama, $elemen, $param);

        $hasilAdaptasi = [
            'nama'         => $nama,
            'produk'       => $produkData['nama_produk'],
            'negara'       => $produkData['nama_negara'],
            'elemen'       => $elemen,
            'sensitivitas' => $analisisPreview['sensitivitas'],
            'temuan'       => $analisisPreview['temuan'],
            'adaptasi'     => $hasilTeks,
        ];

        $msg    = 'Kampanye berhasil dianalisis dan disimpan!';
        $action = 'hasil';
    }
}

$pageTitle = 'Kampanye Marketing';
include '../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-megaphone me-2" style="color:var(--accent)"></i>Marketing Campaign Cultural Adaptation</h1>
  <p>Analisis sensitivitas elemen kampanye dan dapatkan rekomendasi adaptasi budaya lokal</p>
</div>

<?php if ($action === 'tambah'): ?>

<?php if ($msg): ?>
<div class="alert-custom alert-<?= $msgType ?>-custom">
  <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if (empty($produkList)): ?>
<div class="alert-custom alert-warning-custom">
  <i class="bi bi-exclamation-triangle"></i>
  Belum ada produk. <a href="produk.php?action=tambah" style="color:var(--warning)">Tambah produk terlebih dahulu.</a>
</div>
<?php else: ?>

<div class="row g-3">
  <div class="col-md-7">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-megaphone" style="color:var(--accent)"></i> Input Kampanye Marketing</div>
      <div class="card-body-dark">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Produk yang Dikampanyekan *</label>
            <select name="produk_id" class="form-select" required>
              <option value="">-- Pilih Produk --</option>
              <?php foreach($produkList as $p): ?>
              <option value="<?= $p['produk_id'] ?>"><?= htmlspecialchars($p['nama_produk']) ?> → <?= htmlspecialchars($p['nama_negara']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Nama Kampanye *</label>
            <input type="text" name="nama_kampanye" class="form-control"
              placeholder="misal: Ramadan Campaign 2025" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Elemen Kampanye *</label>
            <textarea name="elemen_kampanye" class="form-control" rows="5"
              placeholder="Deskripsikan elemen kampanye: slogan, visual, pesan utama, media, target audiens...

Contoh:
Slogan: 'Feel the Power, Taste the Freedom'
Visual: gambar perempuan berbikini di pantai
Pesan: Nikmati bir segar setelah aktifitas
Media: TikTok dan Instagram" required></textarea>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.4rem">
              Semakin detail, analisis sensitivitas akan semakin akurat.
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn-primary-custom">
              <i class="bi bi-play-circle"></i> Analisis &amp; Simpan
            </button>
            <a href="kampanye.php" class="btn-outline-custom">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-lightbulb" style="color:var(--warning)"></i> Yang Dianalisis Sistem</div>
      <div class="card-body-dark" style="font-size:.82rem;color:var(--text-muted);line-height:1.9">
        <strong style="color:var(--text-main)">Visual:</strong><br>
        Gambar/imagery yang sensitif berdasarkan tingkat religiusitas negara tujuan.<br><br>
        <strong style="color:var(--text-main)">Slogan:</strong><br>
        Kata-kata dengan konotasi negatif atau agresif.<br><br>
        <strong style="color:var(--text-main)">Pesan/Konten:</strong><br>
        Elemen yang bertentangan dengan nilai budaya atau regulasi lokal (alkohol, babi, judi, dll).<br><br>
        <strong style="color:var(--text-main)">Tingkat Sensitivitas:</strong><br>
        <span class="badge-risiko badge-rendah">Rendah</span> — aman dipublikasikan<br>
        <span class="badge-risiko badge-sedang">Sedang</span> — perlu minor revision<br>
        <span class="badge-risiko badge-tinggi">Tinggi</span> — perlu adaptasi signifikan
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php elseif ($action === 'hasil' && $hasilAdaptasi): ?>
<!-- HASIL ADAPTASI -->
<div class="alert-custom alert-success-custom mb-4">
  <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-info-circle" style="color:var(--accent)"></i> Info Kampanye</div>
      <div class="card-body-dark" style="font-size:.875rem">
        <div class="mb-2"><span style="color:var(--text-muted)">Kampanye</span><br><strong><?= htmlspecialchars($hasilAdaptasi['nama']) ?></strong></div>
        <div class="mb-2"><span style="color:var(--text-muted)">Produk</span><br><strong><?= htmlspecialchars($hasilAdaptasi['produk']) ?></strong></div>
        <div class="mb-3"><span style="color:var(--text-muted)">Negara Tujuan</span><br><strong><?= htmlspecialchars($hasilAdaptasi['negara']) ?></strong></div>

        <div style="text-align:center;padding:1rem;background:var(--bg-input);border-radius:8px">
          <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.4rem">Tingkat Sensitivitas</div>
          <?php $sens = $hasilAdaptasi['sensitivitas']; ?>
          <div style="font-size:2rem">
            <?= $sens==='Rendah' ? '🟢' : ($sens==='Sedang' ? '🟡' : '🔴') ?>
          </div>
          <span class="badge-risiko badge-<?= strtolower($sens) ?>"><?= $sens ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-search" style="color:var(--warning)"></i> Temuan Analisis</div>
      <div class="card-body-dark">
        <?php if (empty($hasilAdaptasi['temuan'])): ?>
          <div style="text-align:center;color:var(--success);font-size:.9rem;padding:1rem">
            ✅ Tidak ditemukan elemen sensitif.
          </div>
        <?php else: ?>
          <?php foreach($hasilAdaptasi['temuan'] as $t): ?>
          <div style="background:var(--bg-input);border-radius:8px;padding:.65rem .85rem;margin-bottom:.5rem;font-size:.82rem">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="font-weight:600"><?= htmlspecialchars($t['elemen']) ?></span>
              <span class="badge-risiko badge-<?= $t['status']==='Perlu Adaptasi'?'berisiko':'perlu' ?>">
                <?= $t['status'] ?>
              </span>
            </div>
            <div style="color:var(--text-muted);font-size:.75rem;margin-top:.2rem">Tipe: <?= $t['tipe'] ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-magic" style="color:var(--success)"></i> Rekomendasi Adaptasi</div>
      <div class="card-body-dark" style="font-size:.82rem;line-height:1.9;white-space:pre-line;color:var(--text-muted)">
        <?= htmlspecialchars($hasilAdaptasi['adaptasi']) ?>
      </div>
    </div>
  </div>
</div>

<div class="d-flex gap-2">
  <a href="kampanye.php?action=tambah" class="btn-primary-custom">
    <i class="bi bi-plus"></i> Kampanye Lain
  </a>
  <a href="laporan.php" class="btn-outline-custom">
    <i class="bi bi-file-earmark-text"></i> Buat Laporan
  </a>
  <a href="kampanye.php" class="btn-outline-custom ms-auto">
    <i class="bi bi-list"></i> Semua Kampanye
  </a>
</div>

<?php else: ?>
<!-- LIST KAMPANYE -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div style="color:var(--text-muted);font-size:.875rem">Riwayat analisis kampanye marketing</div>
  <a href="kampanye.php?action=tambah" class="btn-primary-custom">
    <i class="bi bi-plus-circle"></i> Adaptasi Kampanye Baru
  </a>
</div>

<div class="card-dark">
  <div style="overflow-x:auto">
  <table class="table-dark-custom">
    <thead>
      <tr><th>#</th><th>Kampanye</th><th>Produk</th><th>Negara</th><th>Hasil Adaptasi</th><th>Tanggal</th></tr>
    </thead>
    <tbody>
    <?php
    $list = $kampanyeClass->getAll();
    // Filter by user's products
    if (empty($list)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:3rem">
        Belum ada kampanye. <a href="kampanye.php?action=tambah" style="color:var(--accent)">Buat sekarang</a>
      </td></tr>
    <?php else: foreach($list as $i => $k): ?>
      <tr>
        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
        <td><strong><?= htmlspecialchars($k['nama_kampanye']) ?></strong></td>
        <td><?= htmlspecialchars($k['nama_produk']) ?></td>
        <td><?= htmlspecialchars($k['nama_negara']) ?></td>
        <td style="max-width:250px;font-size:.78rem;color:var(--text-muted)">
          <?= htmlspecialchars(mb_substr($k['hasil_adaptasi'] ?? '–', 0, 80)) ?>...
        </td>
        <td style="color:var(--text-muted);font-size:.8rem"><?= $k['tanggal_kampanye'] ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
