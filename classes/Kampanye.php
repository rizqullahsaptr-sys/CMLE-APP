<?php
require_once __DIR__ . '/../config/db.php';

class Kampanye {
    private mysqli $db;

    // Elemen kampanye yang umum sensitif per tipe
    private array $elemenSensitif = [
        'visual'  => ['gambar perempuan','sexy','kulit','topless','mini skirt','bikini'],
        'slogan'  => ['mati','kill','die','hancur','destroy','superior','terbaik di dunia'],
        'pesan'   => ['alcohol','beer','pork','babi','judi','gamble'],
    ];

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // ── TAMBAH KAMPANYE ───────────────────────────────────────
    public function tambahKampanye(int $produkId, string $namaKampanye,
                                    string $elemen, array $paramBudaya): int {
        // Analisis dan generate adaptasi
        $hasil = $this->analisisSensitivitasBudaya($elemen, $paramBudaya);
        $adaptasi = $this->generateHasilAdaptasi($elemen, $hasil['temuan']);

        $stmt = $this->db->prepare(
            "INSERT INTO kampanye (produk_id, nama_kampanye, elemen_kampanye, hasil_adaptasi, tanggal_kampanye)
             VALUES (?, ?, ?, ?, CURDATE())"
        );
        $stmt->bind_param('isss', $produkId, $namaKampanye, $elemen, $adaptasi);
        $stmt->execute();
        return $this->db->insert_id;
    }

    // ── ANALISIS SENSITIVITAS BUDAYA ──────────────────────────
    public function analisisSensitivitasBudaya(string $elemen, array $param): array {
        $elemenLc  = strtolower($elemen);
        $temuan    = [];
        $sensitif  = $param['kategori_sensitif'] ?? [];

        // Cek tiap elemen sensitif
        foreach ($this->elemenSensitif as $tipe => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($elemenLc, $kw)) {
                    $needAdapt = false;
                    if ($tipe === 'visual' && $param['religiusitas'] >= 0.7) $needAdapt = true;
                    if ($tipe === 'pesan')  $needAdapt = true;
                    if ($tipe === 'slogan') $needAdapt = true;

                    $temuan[] = [
                        'elemen' => $kw,
                        'tipe'   => $tipe,
                        'status' => $needAdapt ? 'Perlu Adaptasi' : 'Opsional',
                    ];
                }
            }
        }

        // Cek kategori sensitif negara
        foreach ($sensitif as $kat) {
            if (str_contains($elemenLc, strtolower($kat))) {
                $temuan[] = [
                    'elemen' => $kat,
                    'tipe'   => 'konten_sensitif',
                    'status' => 'Perlu Adaptasi',
                ];
            }
        }

        $jumlahAdaptasi = count(array_filter($temuan, fn($t) => $t['status'] === 'Perlu Adaptasi'));
        if ($jumlahAdaptasi === 0)      $sensitivitas = 'Rendah';
        elseif ($jumlahAdaptasi <= 2)   $sensitivitas = 'Sedang';
        else                            $sensitivitas = 'Tinggi';

        return ['temuan' => $temuan, 'sensitivitas' => $sensitivitas];
    }

    // ── GENERATE HASIL ADAPTASI ───────────────────────────────
    public function generateHasilAdaptasi(string $elemen, array $temuan): string {
        if (empty($temuan)) {
            return "✅ Elemen kampanye tidak memerlukan adaptasi khusus. Kampanye dapat digunakan langsung di pasar tujuan.";
        }

        $adaptasi = [];
        foreach ($temuan as $t) {
            if ($t['status'] === 'Perlu Adaptasi') {
                $adaptasi[] = match($t['tipe']) {
                    'visual'         => "🎨 Visual: Ganti elemen '{$t['elemen']}' dengan visual yang lebih konservatif sesuai norma lokal.",
                    'slogan'         => "💬 Slogan: Hindari kalimat '{$t['elemen']}' — ganti dengan pesan yang lebih positif dan inklusif.",
                    'pesan'          => "📝 Pesan: Elemen '{$t['elemen']}' dapat menimbulkan penolakan — revisi konten pesan.",
                    'konten_sensitif'=> "⚠️ Konten: Topik '{$t['elemen']}' sangat sensitif di pasar tujuan — hapus atau modifikasi.",
                    default          => "ℹ️ Perhatikan elemen '{$t['elemen']}' dalam konteks budaya lokal.",
                };
            } else {
                $adaptasi[] = "💡 Opsional: Elemen '{$t['elemen']}' bisa dipertahankan, namun adaptasi minor bisa meningkatkan penerimaan.";
            }
        }

        return implode("\n", $adaptasi);
    }

    // ── GET KAMPANYE ──────────────────────────────────────────
    public function getByProduk(int $produkId): array {
        $stmt = $this->db->prepare(
            "SELECT k.*, p.nama_produk, n.nama_negara
             FROM kampanye k
             JOIN produk p ON k.produk_id = p.produk_id
             JOIN negara n ON p.negara_id = n.negara_id
             WHERE k.produk_id = ?
             ORDER BY k.tanggal_kampanye DESC"
        );
        $stmt->bind_param('i', $produkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll(): array {
        $sql = "SELECT k.*, p.nama_produk, n.nama_negara
                FROM kampanye k
                JOIN produk p ON k.produk_id = p.produk_id
                JOIN negara n ON p.negara_id = n.negara_id
                ORDER BY k.tanggal_kampanye DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT k.*, p.nama_produk, n.nama_negara, n.kode_negara, n.parameter_budaya
             FROM kampanye k
             JOIN produk p ON k.produk_id = p.produk_id
             JOIN negara n ON p.negara_id = n.negara_id
             WHERE k.kampanye_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}
