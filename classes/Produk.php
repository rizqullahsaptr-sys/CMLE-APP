<?php
require_once __DIR__ . '/../config/db.php';

class Produk {
    private mysqli $db;

    // Kata-kata sensitif per kategori (simulasi database konotasi)
    private array $kataNegatif = [
        'alkohol'  => ['beer','wine','whisky','vodka','bir','rum','gin','sake','miras','minuman keras'],
        'babi'     => ['pork','pig','babi','ham','bacon','lard'],
        'judi'     => ['casino','gambling','bet','taruhan','judi','togel'],
        'seksual'  => ['sex','sexy','erotic','seductive','sensual'],
        'ofensif'  => ['kill','death','mati','bunuh','hate','benci'],
    ];

    // Kata konotasi negatif nama merek per bahasa (simulasi)
    private array $konotasiMerek = [
        'id' => ['mati','babi','anjing','bangsat','gila','bodoh','jelek','kotor'],
        'jp' => ['shinu','aku','baka','kichiku'],
        'ar' => ['khara','ibn','khinzir'],
        'en' => ['die','dead','kill','ugly','stupid','fail','bad'],
        'de' => ['schlecht','tot','dumm','hässlich'],
        'zh' => ['si','gui','zang'],
    ];

    // Map kode negara -> kode bahasa (untuk lookup konotasi)
    private array $bahasaMap = [
        'ID' => 'id', 'JP' => 'jp', 'SA' => 'ar',
        'US' => 'en', 'DE' => 'de', 'CN' => 'zh',
        'IN' => 'en', 'BR' => 'en',
    ];

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // ── TAMBAH PRODUK ─────────────────────────────────────────
    public function tambahProduk(int $userId, int $negaraId, string $nama,
                                  string $kategori, string $deskripsi): int {
        $stmt = $this->db->prepare(
            "INSERT INTO produk (user_id, negara_id, nama_produk, kategori_produk, deskripsi_produk, tanggal_input)
             VALUES (?, ?, ?, ?, ?, CURDATE())"
        );
        $stmt->bind_param('iisss', $userId, $negaraId, $nama, $kategori, $deskripsi);
        $stmt->execute();
        return $this->db->insert_id;
    }

    // ── GET PRODUK ────────────────────────────────────────────
    public function getAll(int $userId, string $role): array {
        if ($role === 'admin') {
            $sql = "SELECT p.*, n.nama_negara, u.nama_pengguna,
                           ab.skor_kesesuaian, ab.tingkat_risiko,
                           m.status_screening, pk.status_compliance
                    FROM produk p
                    JOIN negara n ON p.negara_id = n.negara_id
                    JOIN pengguna u ON p.user_id = u.user_id
                    LEFT JOIN analisis_budaya ab ON p.produk_id = ab.produk_id
                    LEFT JOIN merek m ON p.produk_id = m.produk_id
                    LEFT JOIN packaging pk ON p.produk_id = pk.produk_id
                    ORDER BY p.tanggal_input DESC";
            return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $this->db->prepare(
            "SELECT p.*, n.nama_negara,
                    ab.skor_kesesuaian, ab.tingkat_risiko,
                    m.status_screening, pk.status_compliance
             FROM produk p
             JOIN negara n ON p.negara_id = n.negara_id
             LEFT JOIN analisis_budaya ab ON p.produk_id = ab.produk_id
             LEFT JOIN merek m ON p.produk_id = m.produk_id
             LEFT JOIN packaging pk ON p.produk_id = pk.produk_id
             WHERE p.user_id = ?
             ORDER BY p.tanggal_input DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.*, n.nama_negara, n.kode_negara, n.bahasa_utama,
                    n.region, n.parameter_budaya
             FROM produk p
             JOIN negara n ON p.negara_id = n.negara_id
             WHERE p.produk_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    // ── JALANKAN ANALISIS LENGKAP ─────────────────────────────
    public function jalankanAnalisisLengkap(int $produkId,
                                             string $namaMerek,
                                             string $jeniKemasan): array {
        $produk = $this->getById($produkId);
        if (!$produk) return ['error' => 'Produk tidak ditemukan'];

        $paramBudaya = json_decode($produk['parameter_budaya'], true);
        $kodeNegara  = $produk['kode_negara'];

        // 1. Cultural Fit Analyzer
        $cultural = $this->hitungSkorKesesuaian($produk, $paramBudaya);
        $this->simpanAnalisisBudaya($produkId, $cultural);

        // 2. Brand Name Cultural Screening
        $brand = $this->screeningNamaMerek($namaMerek, $kodeNegara);
        $this->simpanMerek($produkId, $namaMerek, $brand);

        // 3. Packaging & Labeling Compliance
        $packaging = $this->cekCompliance($jeniKemasan, $paramBudaya, $kodeNegara);
        $this->simpanPackaging($produkId, $jeniKemasan, $packaging);

        return [
            'cultural'  => $cultural,
            'brand'     => $brand,
            'packaging' => $packaging,
        ];
    }

    // ── PRODUCT CULTURAL FIT ANALYZER ─────────────────────────
    public function hitungSkorKesesuaian(array $produk, array $param): array {
        $skor         = 70.0; // baseline
        $catatanList  = [];
        $kategori     = strtolower($produk['kategori_produk']);
        $deskripsi    = strtolower($produk['deskripsi_produk']);
        $teks         = $kategori . ' ' . $deskripsi;

        $kategoriSensitif = $param['kategori_sensitif'] ?? [];

        // Cek kategori sensitif
        foreach ($this->kataNegatif as $kat => $kata) {
            foreach ($kata as $k) {
                if (str_contains($teks, $k)) {
                    if (in_array($kat, $kategoriSensitif)) {
                        $skor -= 20;
                        $catatanList[] = "⚠️ Mengandung elemen '$k' yang sensitif di negara tujuan (kategori: $kat).";
                    } else {
                        $skor -= 5;
                        $catatanList[] = "ℹ️ Perhatikan elemen '$k', mungkin perlu penyesuaian konteks.";
                    }
                }
            }
        }

        // Bonus: religiusitas tinggi + produk halal/islami → skor naik
        if ($param['religiusitas'] >= 0.8) {
            if (str_contains($teks, 'halal') || str_contains($teks, 'syariah')) {
                $skor += 15;
                $catatanList[] = "✅ Produk memiliki atribut halal/syariah yang relevan di negara tujuan.";
            } else {
                $skor -= 5;
                $catatanList[] = "ℹ️ Negara tujuan memiliki religiusitas tinggi. Pertimbangkan sertifikasi halal.";
            }
        }

        // Kolektivisme tinggi → produk keluarga/komunitas lebih diterima
        if ($param['kolektivisme'] >= 0.7) {
            if (str_contains($teks, 'keluarga') || str_contains($teks, 'komunitas') || str_contains($teks, 'family')) {
                $skor += 10;
                $catatanList[] = "✅ Pendekatan berbasis keluarga/komunitas sangat diterima di negara tujuan.";
            }
        }

        // Clamp skor 0-100
        $skor = max(0, min(100, $skor));

        $risiko = 'Rendah';
        if ($skor < 75) $risiko = 'Sedang';
        if ($skor < 50) $risiko = 'Tinggi';

        if (empty($catatanList)) {
            $catatanList[] = "✅ Produk menunjukkan kesesuaian budaya yang baik dengan negara tujuan.";
        }

        return [
            'skor'   => round($skor, 2),
            'risiko' => $risiko,
            'catatan' => implode("\n", $catatanList),
        ];
    }

    // ── BRAND NAME CULTURAL SCREENING ─────────────────────────
    public function screeningNamaMerek(string $namaMerek, string $kodeNegara): array {
        $kode    = $this->bahasaMap[$kodeNegara] ?? 'en';
        $daftar  = $this->konotasiMerek[$kode] ?? [];
        $merekLc = strtolower($namaMerek);
        $status  = 'Aman';
        $temuan  = [];
        $rekomendasi = '';

        foreach ($daftar as $kata) {
            if (str_contains($merekLc, $kata)) {
                $temuan[] = $kata;
                $status = 'Berisiko';
            }
        }

        // Cek pelafalan aneh (huruf berulang 3+, sangat pendek, atau hanya angka)
        if ($status === 'Aman') {
            if (preg_match('/(.)\1{2,}/', $namaMerek) || strlen($namaMerek) < 2 || preg_match('/^\d+$/', $namaMerek)) {
                $status = 'Perlu Perubahan';
                $temuan[] = 'pelafalan tidak wajar';
            }
        }

        if ($status !== 'Aman') {
            $prefix  = substr(strtoupper($namaMerek), 0, 2);
            $rekomendasi  = "Alternatif yang disarankan: {$prefix}Nova, {$prefix}Prime, {$prefix}Global, {$prefix}Connect. ";
            $rekomendasi .= "Hindari penggunaan kata: " . implode(', ', $temuan) . " dalam penamaan merek di wilayah ini.";
        } else {
            $rekomendasi = "Nama merek '{$namaMerek}' aman digunakan di negara tujuan. Tidak ditemukan konotasi negatif.";
        }

        return [
            'status'      => $status,
            'temuan'      => $temuan,
            'rekomendasi' => $rekomendasi,
        ];
    }

    // ── PACKAGING & LABELING COMPLIANCE ───────────────────────
    public function cekCompliance(string $jeniKemasan, array $param, string $kodeNegara): array {
        $pelanggaran = [];
        $catatan     = [];
        $jenis       = strtolower($jeniKemasan);

        // Regulasi umum
        if (!str_contains($jenis, 'label') && !str_contains($jenis, 'berlabel')) {
            $pelanggaran[] = 'Label produk wajib ada dalam bahasa lokal negara tujuan';
        }

        if (!str_contains($jenis, 'daur ulang') && !str_contains($jenis, 'recycle') && !str_contains($jenis, 'ramah lingkungan')) {
            $catatan[] = 'ℹ️ Pertimbangkan kemasan ramah lingkungan untuk meningkatkan penerimaan pasar.';
        }

        // Regulasi spesifik negara
        if ($param['sensitif_alkohol'] ?? false) {
            if (str_contains($jenis, 'botol kaca') || str_contains($jenis, 'glass bottle')) {
                $pelanggaran[] = 'Kemasan botol kaca identik minuman beralkohol — sensitif di negara tujuan';
            }
        }

        if (in_array($kodeNegara, ['SA', 'ID'])) {
            if (!str_contains($jenis, 'halal')) {
                $catatan[] = '⚠️ Sertifikasi halal sangat direkomendasikan untuk pasar ini.';
            }
        }

        if ($kodeNegara === 'JP') {
            if (!str_contains($jenis, 'jepang') && !str_contains($jenis, 'kanji') && !str_contains($jenis, 'japanese')) {
                $pelanggaran[] = 'Label wajib menggunakan huruf Jepang (Kanji/Hiragana) sesuai regulasi JAS';
            }
        }

        if ($kodeNegara === 'US') {
            if (!str_contains($jenis, 'fda') && !str_contains($jenis, 'nutrition') && !str_contains($jenis, 'nutrisi')) {
                $pelanggaran[] = 'Wajib mencantumkan Nutrition Facts sesuai regulasi FDA';
            }
        }

        $jumlah = count($pelanggaran);
        if ($jumlah === 0)      $status = 'Lulus';
        elseif ($jumlah <= 2)   $status = 'Perlu Revisi';
        else                    $status = 'Tidak Lulus';

        $catatanFinal = implode("\n", array_merge(
            array_map(fn($v) => "❌ $v", $pelanggaran),
            $catatan
        ));

        if (empty($catatanFinal)) {
            $catatanFinal = '✅ Kemasan dan label telah memenuhi seluruh regulasi negara tujuan.';
        }

        return [
            'status'     => $status,
            'pelanggaran' => $pelanggaran,
            'catatan'    => $catatanFinal,
        ];
    }

    // ── SIMPAN KE DATABASE ────────────────────────────────────
    private function simpanAnalisisBudaya(int $produkId, array $data): void {
        // Upsert
        $stmt = $this->db->prepare(
            "INSERT INTO analisis_budaya (produk_id, skor_kesesuaian, tingkat_risiko, catatan_budaya, tanggal_analisis)
             VALUES (?, ?, ?, ?, CURDATE())
             ON DUPLICATE KEY UPDATE skor_kesesuaian=VALUES(skor_kesesuaian),
             tingkat_risiko=VALUES(tingkat_risiko), catatan_budaya=VALUES(catatan_budaya),
             tanggal_analisis=CURDATE()"
        );
        $stmt->bind_param('idss', $produkId, $data['skor'], $data['risiko'], $data['catatan']);
        $stmt->execute();
    }

    private function simpanMerek(int $produkId, string $namaMerek, array $data): void {
        $stmt = $this->db->prepare(
            "INSERT INTO merek (produk_id, nama_merek, status_screening, rekomendasi_merek)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nama_merek=VALUES(nama_merek),
             status_screening=VALUES(status_screening), rekomendasi_merek=VALUES(rekomendasi_merek)"
        );
        $stmt->bind_param('isss', $produkId, $namaMerek, $data['status'], $data['rekomendasi']);
        $stmt->execute();
    }

    private function simpanPackaging(int $produkId, string $jenis, array $data): void {
        $stmt = $this->db->prepare(
            "INSERT INTO packaging (produk_id, jenis_kemasan, status_compliance, catatan_regulasi)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE jenis_kemasan=VALUES(jenis_kemasan),
             status_compliance=VALUES(status_compliance), catatan_regulasi=VALUES(catatan_regulasi)"
        );
        $stmt->bind_param('isss', $produkId, $jenis, $data['status'], $data['catatan']);
        $stmt->execute();
    }
}
