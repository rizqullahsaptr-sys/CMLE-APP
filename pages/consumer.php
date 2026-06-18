<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../classes/ConsumerInsightLaporan.php';
requireLogin();

$user    = currentUser();
$ciClass = new ConsumerInsight($conn);
$msg     = '';
$msgType = 'success';

$negaraList = $conn->query("SELECT negara_id, nama_negara, kode_negara FROM negara ORDER BY nama_negara")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = $ciClass->updatePreferensiLokal(
        (int)$_POST['negara_id'],
        trim($_POST['segmen_konsumen']),
        trim($_POST['preferensi_lokal'])
    );
    $msg     = $ok ? 'Data consumer insight berhasil diperbarui!' : 'Gagal menyimpan data.';
    $msgType = $ok ? 'success' : 'danger';
}

$allInsight = $ciClass->getAll();

$pageTitle = 'Consumer Insight';
include '../includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-people me-2" style="color:var(--accent)"></i>Local Consumer Insight Integration</h1>
  <p>Data segmentasi dan preferensi konsumen lokal per negara tujuan</p>
</div>

<?php if ($msg): ?>
<div class="alert-custom alert-<?= $msgType ?>-custom">
  <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <!-- Form Update -->
  <div class="col-md-4">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-plus-circle" style="color:var(--accent)"></i> Tambah / Update Insight</div>
      <div class="card-body-dark">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Negara</label>
            <select name="negara_id" class="form-select" required>
              <option value="">-- Pilih Negara --</option>
              <?php foreach($negaraList as $n): ?>
              <option value="<?= $n['negara_id'] ?>"><?= htmlspecialchars($n['nama_negara']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Segmen Konsumen</label>
            <input type="text" name="segmen_konsumen" class="form-control"
              placeholder="misal: Millennial Muslim Urban" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Preferensi Lokal</label>
            <textarea name="preferensi_lokal" class="form-control" rows="4"
              placeholder="misal: Produk halal, harga terjangkau, influencer lokal, platform TikTok & Instagram" required></textarea>
          </div>
          <button type="submit" class="btn-primary-custom w-100">
            <i class="bi bi-cloud-upload"></i> Simpan Insight
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Peta Insight -->
  <div class="col-md-8">
    <div class="card-dark">
      <div class="card-header-dark"><i class="bi bi-globe" style="color:var(--accent)"></i> Insight per Negara</div>
      <div style="overflow-x:auto">
      <table class="table-dark-custom">
        <thead>
          <tr><th>Negara</th><th>Region</th><th>Segmen Utama</th><th>Produk Dianalisis</th><th>Update</th></tr>
        </thead>
        <tbody>
        <?php if (empty($allInsight)): ?>
          <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">Belum ada data insight</td></tr>
        <?php else: foreach($allInsight as $ci): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($ci['nama_negara']) ?></strong>
              <div style="font-size:.72rem;color:var(--text-muted)"><?= $ci['kode_negara'] ?></div>
            </td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($ci['region']) ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($ci['segmen_konsumen']) ?></td>
            <td style="text-align:center">
              <span style="background:rgba(47,129,247,.12);color:var(--accent);padding:.2rem .6rem;border-radius:20px;font-size:.78rem;font-weight:600">
                <?= $ci['jumlah_produk'] ?>
              </span>
            </td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= $ci['tanggal_update'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</div>

<!-- Detail Preferensi per Negara -->
<div class="card-dark">
  <div class="card-header-dark"><i class="bi bi-card-list" style="color:var(--accent)"></i> Detail Preferensi Konsumen</div>
  <div class="card-body-dark">
    <div class="row g-3">
    <?php foreach($allInsight as $ci): ?>
      <div class="col-md-4">
        <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:1rem">
          <div style="font-weight:600;margin-bottom:.25rem"><?= htmlspecialchars($ci['nama_negara']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.6rem"><?= htmlspecialchars($ci['segmen_konsumen']) ?></div>
          <div style="font-size:.8rem;color:var(--text-muted);line-height:1.7">
            <?= htmlspecialchars($ci['preferensi_lokal']) ?>
          </div>
          <div style="margin-top:.6rem;font-size:.7rem;color:var(--text-muted)">
            Update: <?= $ci['tanggal_update'] ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($allInsight)): ?>
      <div class="col-12 text-center" style="color:var(--text-muted);padding:1rem">Belum ada data.</div>
    <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
