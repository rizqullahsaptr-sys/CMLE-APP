<?php
require_once __DIR__ . '/../config/db.php';

class ConsumerInsight {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $sql = "SELECT ci.*, n.nama_negara, n.kode_negara, n.region,
                       COUNT(p.produk_id) AS jumlah_produk
                FROM consumer_insight ci
                JOIN negara n ON ci.negara_id = n.negara_id
                LEFT JOIN produk p ON n.negara_id = p.negara_id
                GROUP BY ci.insight_id
                ORDER BY n.nama_negara ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getByNegara(int $negaraId): array {
        $stmt = $this->db->prepare(
            "SELECT ci.*, n.nama_negara, n.region
             FROM consumer_insight ci
             JOIN negara n ON ci.negara_id = n.negara_id
             WHERE ci.negara_id = ?
             ORDER BY ci.tanggal_update DESC"
        );
        $stmt->bind_param('i', $negaraId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updatePreferensiLokal(int $negaraId, string $segmen,
                                           string $preferensi): bool {
        // Cek apakah sudah ada
        $stmt = $this->db->prepare(
            "SELECT insight_id FROM consumer_insight WHERE negara_id = ? AND segmen_konsumen = ?"
        );
        $stmt->bind_param('is', $negaraId, $segmen);
        $stmt->execute();
        $exist = $stmt->get_result()->fetch_assoc();

        if ($exist) {
            $upd = $this->db->prepare(
                "UPDATE consumer_insight SET preferensi_lokal = ?, tanggal_update = CURDATE()
                 WHERE insight_id = ?"
            );
            $upd->bind_param('si', $preferensi, $exist['insight_id']);
            return $upd->execute();
        } else {
            $ins = $this->db->prepare(
                "INSERT INTO consumer_insight (negara_id, segmen_konsumen, preferensi_lokal, tanggal_update)
                 VALUES (?, ?, ?, CURDATE())"
            );
            $ins->bind_param('iss', $negaraId, $segmen, $preferensi);
            return $ins->execute();
        }
    }

    public function segmentasiKonsumen(int $negaraId): array {
        $data = $this->getByNegara($negaraId);
        return array_map(function($row) {
            $preferensi = explode(',', $row['preferensi_lokal']);
            return [
                'segmen'    => $row['segmen_konsumen'],
                'jumlah_preferensi' => count($preferensi),
                'preferensi_utama'  => trim($preferensi[0] ?? '-'),
                'tanggal_update'    => $row['tanggal_update'],
            ];
        }, $data);
    }
}

// ─────────────────────────────────────────────────────────────
class Laporan {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function generateLaporan(int $produkId, int $userId): int {
        // Ambil semua data analisis untuk produk ini
        $produk = $this->db->query(
            "SELECT p.*, n.nama_negara FROM produk p JOIN negara n ON p.negara_id = n.negara_id
             WHERE p.produk_id = $produkId"
        )->fetch_assoc();

        $ab = $this->db->query(
            "SELECT * FROM analisis_budaya WHERE produk_id = $produkId"
        )->fetch_assoc();

        $merek = $this->db->query(
            "SELECT * FROM merek WHERE produk_id = $produkId"
        )->fetch_assoc();

        $pkg = $this->db->query(
            "SELECT * FROM packaging WHERE produk_id = $produkId"
        )->fetch_assoc();

        $kampanye = $this->db->query(
            "SELECT * FROM kampanye WHERE produk_id = $produkId ORDER BY tanggal_kampanye DESC LIMIT 1"
        )->fetch_assoc();

        $judul = "Laporan Lokalisasi: {$produk['nama_produk']} → {$produk['nama_negara']}";
        $tanggal = date('d F Y');

        $isi  = "=== LAPORAN LOKALISASI CMLE ===\n";
        $isi .= "Produk     : {$produk['nama_produk']}\n";
        $isi .= "Kategori   : {$produk['kategori_produk']}\n";
        $isi .= "Negara     : {$produk['nama_negara']}\n";
        $isi .= "Tanggal    : $tanggal\n\n";

        if ($ab) {
            $isi .= "--- 1. CULTURAL FIT ANALYZER ---\n";
            $isi .= "Skor Kesesuaian : {$ab['skor_kesesuaian']}/100\n";
            $isi .= "Tingkat Risiko  : {$ab['tingkat_risiko']}\n";
            $isi .= "Catatan         :\n{$ab['catatan_budaya']}\n\n";
        }

        if ($merek) {
            $isi .= "--- 2. BRAND NAME SCREENING ---\n";
            $isi .= "Nama Merek      : {$merek['nama_merek']}\n";
            $isi .= "Status          : {$merek['status_screening']}\n";
            $isi .= "Rekomendasi     : {$merek['rekomendasi_merek']}\n\n";
        }

        if ($pkg) {
            $isi .= "--- 3. PACKAGING COMPLIANCE ---\n";
            $isi .= "Jenis Kemasan   : {$pkg['jenis_kemasan']}\n";
            $isi .= "Status          : {$pkg['status_compliance']}\n";
            $isi .= "Catatan Regulasi:\n{$pkg['catatan_regulasi']}\n\n";
        }

        if ($kampanye) {
            $isi .= "--- 4. KAMPANYE ADAPTASI ---\n";
            $isi .= "Kampanye        : {$kampanye['nama_kampanye']}\n";
            $isi .= "Hasil Adaptasi  :\n{$kampanye['hasil_adaptasi']}\n\n";
        }

        $isi .= "=== END OF REPORT ===";

        $stmt = $this->db->prepare(
            "INSERT INTO laporan (produk_id, user_id, judul_laporan, tanggal_laporan, isi_laporan, jenis_laporan)
             VALUES (?, ?, ?, CURDATE(), ?, 'Lengkap')"
        );
        $stmt->bind_param('iiss', $produkId, $userId, $judul, $isi);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function getAll(int $userId, string $role): array {
        if ($role === 'admin' || $role === 'brand_manager') {
            $sql = "SELECT l.*, p.nama_produk, n.nama_negara, u.nama_pengguna
                    FROM laporan l
                    JOIN produk p ON l.produk_id = p.produk_id
                    JOIN negara n ON p.negara_id = n.negara_id
                    JOIN pengguna u ON l.user_id = u.user_id
                    ORDER BY l.tanggal_laporan DESC";
            return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $this->db->prepare(
            "SELECT l.*, p.nama_produk, n.nama_negara
             FROM laporan l
             JOIN produk p ON l.produk_id = p.produk_id
             JOIN negara n ON p.negara_id = n.negara_id
             WHERE l.user_id = ?
             ORDER BY l.tanggal_laporan DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT l.*, p.nama_produk, n.nama_negara, u.nama_pengguna
             FROM laporan l
             JOIN produk p ON l.produk_id = p.produk_id
             JOIN negara n ON p.negara_id = n.negara_id
             JOIN pengguna u ON l.user_id = u.user_id
             WHERE l.laporan_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}
