-- ============================================================
-- CMLE - Database FINAL
-- ============================================================

DROP DATABASE IF EXISTS cmle_db;
CREATE DATABASE cmle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cmle_db;

-- ============================================================
-- STRUKTUR TABEL
-- ============================================================

CREATE TABLE pengguna (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengguna  VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('marketing_analyst','brand_manager','admin') NOT NULL DEFAULT 'marketing_analyst',
    tanggal_daftar DATE NOT NULL DEFAULT '2025-01-01'
);

CREATE TABLE negara (
    negara_id        INT AUTO_INCREMENT PRIMARY KEY,
    nama_negara      VARCHAR(100) NOT NULL,
    kode_negara      VARCHAR(5)   NOT NULL UNIQUE,
    bahasa_utama     VARCHAR(50)  NOT NULL,
    region           VARCHAR(50)  NOT NULL,
    parameter_budaya JSON
);

CREATE TABLE produk (
    produk_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    negara_id        INT NOT NULL,
    nama_produk      VARCHAR(150) NOT NULL,
    kategori_produk  VARCHAR(80)  NOT NULL,
    deskripsi_produk TEXT NOT NULL,
    tanggal_input    DATE NOT NULL DEFAULT '2025-01-01',
    FOREIGN KEY (user_id)   REFERENCES pengguna(user_id) ON DELETE CASCADE,
    FOREIGN KEY (negara_id) REFERENCES negara(negara_id) ON DELETE RESTRICT
);

CREATE TABLE analisis_budaya (
    analisis_id      INT AUTO_INCREMENT PRIMARY KEY,
    produk_id        INT NOT NULL UNIQUE,
    skor_kesesuaian  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tingkat_risiko   ENUM('Rendah','Sedang','Tinggi') NOT NULL,
    catatan_budaya   TEXT,
    tanggal_analisis DATE NOT NULL DEFAULT '2025-01-01',
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE
);

CREATE TABLE merek (
    merek_id          INT AUTO_INCREMENT PRIMARY KEY,
    produk_id         INT NOT NULL UNIQUE,
    nama_merek        VARCHAR(150) NOT NULL,
    status_screening  ENUM('Aman','Berisiko','Perlu Perubahan') NOT NULL,
    rekomendasi_merek TEXT,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE
);

CREATE TABLE packaging (
    packaging_id      INT AUTO_INCREMENT PRIMARY KEY,
    produk_id         INT NOT NULL UNIQUE,
    jenis_kemasan     VARCHAR(100) NOT NULL,
    status_compliance ENUM('Lulus','Perlu Revisi','Tidak Lulus') NOT NULL,
    catatan_regulasi  TEXT,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE
);

CREATE TABLE kampanye (
    kampanye_id      INT AUTO_INCREMENT PRIMARY KEY,
    produk_id        INT NOT NULL,
    nama_kampanye    VARCHAR(150) NOT NULL,
    elemen_kampanye  TEXT NOT NULL,
    hasil_adaptasi   TEXT,
    tanggal_kampanye DATE NOT NULL DEFAULT '2025-01-01',
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE
);

CREATE TABLE consumer_insight (
    insight_id       INT AUTO_INCREMENT PRIMARY KEY,
    negara_id        INT NOT NULL,
    segmen_konsumen  VARCHAR(100) NOT NULL,
    preferensi_lokal TEXT NOT NULL,
    tanggal_update   DATE NOT NULL DEFAULT '2025-01-01',
    FOREIGN KEY (negara_id) REFERENCES negara(negara_id) ON DELETE CASCADE
);

CREATE TABLE laporan (
    laporan_id      INT AUTO_INCREMENT PRIMARY KEY,
    produk_id       INT NOT NULL,
    user_id         INT NOT NULL,
    judul_laporan   VARCHAR(200) NOT NULL,
    tanggal_laporan DATE NOT NULL DEFAULT '2025-01-01',
    isi_laporan     TEXT NOT NULL,
    jenis_laporan   ENUM('Cultural Fit','Brand Screening','Packaging','Kampanye','Lengkap') NOT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(produk_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES pengguna(user_id) ON DELETE CASCADE
);

-- ============================================================
-- PENGGUNA
-- admin123   => 0192023a7bbd73250516f069df18b500
-- analyst123 => 4918538313cfcb69dc5e7b97df4fd985
-- brand123   => f9819c578781e1ce59d8450d60e5f85a
-- ============================================================
INSERT INTO pengguna (nama_pengguna, email, password, role, tanggal_daftar) VALUES
('Admin CMLE',        'admin@cmle.id',     '0192023a7bbd73250516f069df18b500', 'admin',             '2025-01-01'),
('Rizqullah Saputra', 'rizqullah@cmle.id', '4918538313cfcb69dc5e7b97df4fd985', 'marketing_analyst', '2025-01-01'),
('Brand Manager',     'brand@cmle.id',     'f9819c578781e1ce59d8450d60e5f85a', 'brand_manager',     '2025-01-01');

-- ============================================================
-- NEGARA
-- ============================================================
INSERT INTO negara (nama_negara, kode_negara, bahasa_utama, region, parameter_budaya) VALUES
('Indonesia',       'ID', 'Bahasa Indonesia', 'Asia Tenggara',   '{"religiusitas":0.9,"kolektivisme":0.8,"formalitas":0.7,"sensitif_alkohol":true,"sensitif_babi":true,"warna_favorit":["hijau","putih"],"warna_pantangan":[],"kategori_sensitif":["alkohol","judi","babi"]}'),
('Jepang',          'JP', 'Bahasa Jepang',    'Asia Timur',      '{"religiusitas":0.3,"kolektivisme":0.7,"formalitas":0.9,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["putih","merah"],"warna_pantangan":["hitam"],"kategori_sensitif":["informalitas","death_imagery"]}'),
('Arab Saudi',      'SA', 'Bahasa Arab',      'Timur Tengah',    '{"religiusitas":1.0,"kolektivisme":0.7,"formalitas":0.8,"sensitif_alkohol":true,"sensitif_babi":true,"warna_favorit":["hijau","emas"],"warna_pantangan":[],"kategori_sensitif":["alkohol","babi","gambar_perempuan"]}'),
('Amerika Serikat', 'US', 'Bahasa Inggris',   'Amerika Utara',   '{"religiusitas":0.4,"kolektivisme":0.3,"formalitas":0.4,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["biru","merah"],"warna_pantangan":[],"kategori_sensitif":["rasisme","stereotip"]}'),
('India',           'IN', 'Bahasa Hindi',     'Asia Selatan',    '{"religiusitas":0.8,"kolektivisme":0.7,"formalitas":0.6,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["oranye","kuning"],"warna_pantangan":["hitam","putih"],"kategori_sensitif":["sapi","alkohol_daerah"]}'),
('Jerman',          'DE', 'Bahasa Jerman',    'Eropa Barat',     '{"religiusitas":0.3,"kolektivisme":0.4,"formalitas":0.7,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["hitam","merah","emas"],"warna_pantangan":[],"kategori_sensitif":["nazi_simbol"]}'),
('Brasil',          'BR', 'Bahasa Portugis',  'Amerika Selatan', '{"religiusitas":0.7,"kolektivisme":0.6,"formalitas":0.4,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["hijau","kuning"],"warna_pantangan":["ungu_pemakaman"],"kategori_sensitif":[]}'),
('China',           'CN', 'Bahasa Mandarin',  'Asia Timur',      '{"religiusitas":0.2,"kolektivisme":0.8,"formalitas":0.7,"sensitif_alkohol":false,"sensitif_babi":false,"warna_favorit":["merah","emas"],"warna_pantangan":["hijau_topi","putih"],"kategori_sensitif":["politik","taiwan","tibet"]}');

-- ============================================================
-- PRODUK
-- user_id=2 Marketing Analyst | user_id=3 Brand Manager
-- ============================================================
INSERT INTO produk (user_id, negara_id, nama_produk, kategori_produk, deskripsi_produk, tanggal_input) VALUES
(2, 3, 'NusaTea Premium',       'Makanan & Minuman',    'Minuman teh premium berbahan dasar daun teh Jawa, sertifikasi halal MUI, tanpa alkohol, cocok untuk keluarga muslim, kemasan ramah lingkungan.',                                                      '2025-03-10'),
(2, 2, 'GlowNusa Serum',        'Kosmetik & Perawatan', 'Serum wajah berbahan aloe vera dan kunyit lokal Indonesia. Formula minimalis, kemasan premium berlabel japanese. Dermatologically tested.',                                                             '2025-03-15'),
(2, 4, 'KripikNusa Spicy',      'Makanan & Minuman',    'Keripik singkong rasa pedas level 3, nutrition facts tercantum FDA compliant, packaging modern, target Gen Z Amerika.',                                                                                 '2025-03-20'),
(2, 1, 'BatikModern Collection','Fashion & Pakaian',    'Koleksi batik modern untuk keluarga muslim urban, bahan katun premium, motif kontemporer, ramah lingkungan dan halal proses produksinya.',                                                             '2025-04-01'),
(2, 5, 'VitaNusa Herbal',       'Obat & Suplemen',      'Suplemen herbal berbahan rempah nusantara, tidak mengandung sapi atau produk turunannya, cocok untuk konsumen vegetarian India.',                                                                       '2025-04-10'),
(3, 7, 'KopiBrasi Blend',       'Makanan & Minuman',    'Kopi single origin Flores blend, kemasan premium ramah lingkungan, target kelas menengah urban Brasil yang menyukai kopi specialty.',                                                                   '2025-04-15'),
(3, 6, 'JamuWell Capsule',      'Obat & Suplemen',      'Kapsul jamu tradisional Indonesia dalam kemasan modern farmasi, berlabel bilingual Jerman-Inggris, tidak mengandung alkohol.',                                                                         '2025-04-20'),
(3, 8, 'TempeChip Snack',       'Makanan & Minuman',    'Keripik tempe premium tanpa MSG, kemasan modern dengan keterangan vegetarian, target pasar health-conscious China urban.',                                                                              '2025-05-01');

-- ============================================================
-- ANALISIS BUDAYA
-- ============================================================
INSERT INTO analisis_budaya (produk_id, skor_kesesuaian, tingkat_risiko, catatan_budaya, tanggal_analisis) VALUES
(1, 92.00, 'Rendah', '✅ Produk memiliki atribut halal/syariah yang relevan di negara tujuan.\n✅ Pendekatan berbasis keluarga/komunitas sangat diterima.\n✅ Tidak ditemukan elemen sensitif dalam deskripsi produk.', '2025-03-10'),
(2, 75.00, 'Rendah', '✅ Produk menunjukkan kesesuaian budaya yang baik dengan negara tujuan.\nℹ️ Pertimbangkan mencantumkan keterangan bahan dalam huruf Kanji untuk meningkatkan kepercayaan konsumen Jepang.', '2025-03-15'),
(3, 68.00, 'Sedang', 'ℹ️ Produk makanan untuk pasar AS wajib mencantumkan Nutrition Facts.\n⚠️ Pastikan label spicy disesuaikan dengan standar FDA.', '2025-03-20'),
(4, 88.00, 'Rendah', '✅ Produk memiliki atribut halal/syariah yang relevan di negara tujuan.\n✅ Nilai lokal batik mendukung penerimaan produk di Indonesia.', '2025-04-01'),
(5, 71.00, 'Sedang', '✅ Produk tidak mengandung sapi — sangat positif untuk pasar India.\nℹ️ Sertifikasi vegetarian/vegan akan sangat membantu penetrasi pasar.', '2025-04-10'),
(6, 83.00, 'Rendah', '✅ Produk menunjukkan kesesuaian budaya yang baik untuk pasar Brasil.\n✅ Pendekatan berbasis komunitas sangat relevan di Brasil.\nℹ️ Pertimbangkan kampanye yang menekankan kebersamaan saat minum kopi.', '2025-04-15'),
(7, 78.00, 'Rendah', '✅ Produk tidak mengandung alkohol — sesuai ekspektasi pasar Jerman untuk produk herbal.\nℹ️ Sertifikasi GMP Eropa akan meningkatkan kepercayaan konsumen Jerman.', '2025-04-20'),
(8, 62.00, 'Sedang', '⚠️ Tempe masih asing di pasar China — perlu edukasi konsumen.\nℹ️ Hindari klaim yang berpotensi menyentuh isu politik.\n✅ Produk vegetarian memiliki segmen berkembang pesat di China urban.', '2025-05-01');

-- ============================================================
-- MEREK
-- ============================================================
INSERT INTO merek (produk_id, nama_merek, status_screening, rekomendasi_merek) VALUES
(1, 'NusaTea',     'Aman',           'Nama merek NusaTea aman digunakan di Arab Saudi. Tidak ditemukan konotasi negatif dalam bahasa Arab.'),
(2, 'GlowNusa',    'Aman',           'Nama merek GlowNusa aman digunakan di Jepang. Pelafalan mudah bagi konsumen Jepang.'),
(3, 'KripikNusa',  'Perlu Perubahan','Kata Kripik sulit dilafalkan konsumen Amerika. Alternatif: NusaChips, SpicyNusa, KRNova.'),
(4, 'BatikModern', 'Aman',           'Nama merek BatikModern aman di Indonesia. Kata Batik memiliki asosiasi budaya kuat dan positif.'),
(5, 'VitaNusa',    'Aman',           'Nama merek VitaNusa aman di India. Vita memiliki konotasi positif dalam banyak bahasa.'),
(6, 'KopiBrasi',   'Aman',           'Nama merek KopiBrasi aman digunakan di Brasil. Konotasi kopi kuat dan mudah dilafalkan dalam bahasa Portugis.'),
(7, 'JamuWell',    'Aman',           'Nama merek JamuWell aman di Jerman. Well memiliki konotasi kesehatan positif. Jamu terdengar eksotis dan unik.'),
(8, 'TempeChip',   'Aman',           'Nama merek TempeChip aman di China. Tidak ditemukan konotasi negatif dalam bahasa Mandarin.');

-- ============================================================
-- PACKAGING
-- ============================================================
INSERT INTO packaging (produk_id, jenis_kemasan, status_compliance, catatan_regulasi) VALUES
(1, 'Kotak karton berlabel halal, teks Arab dan Inggris',                          'Lulus',       '✅ Kemasan memenuhi seluruh regulasi Arab Saudi.\n✅ Label halal tersertifikasi meningkatkan kepercayaan konsumen.'),
(2, 'Botol kaca premium berlabel kanji, bilingual Jepang-Inggris',                 'Lulus',       '✅ Label menggunakan huruf Jepang sesuai regulasi JAS.\n✅ Kemasan premium sesuai ekspektasi konsumen Jepang.'),
(3, 'Kemasan plastik modern dengan nutrition facts, berat oz dan gram',             'Perlu Revisi','❌ Wajib mencantumkan Nutrition Facts lengkap sesuai FDA.\nℹ️ Pertimbangkan kemasan ramah lingkungan.'),
(4, 'Tas kain premium dengan hangtag batik, label perawatan bahasa Indonesia',      'Lulus',       '✅ Kemasan memenuhi seluruh regulasi Indonesia.'),
(5, 'Botol plastik HDPE berlabel bilingual Hindi-Inggris, keterangan vegetarian',  'Lulus',       '✅ Kemasan memenuhi seluruh regulasi India.\n✅ Label vegetarian sangat positif untuk pasar India.'),
(6, 'Kaleng aluminium berlabel bilingual Portugis-Inggris, ramah lingkungan',      'Lulus',       '✅ Kemasan memenuhi regulasi Brasil.\nℹ️ Sertifikasi daur ulang meningkatkan nilai produk di pasar Brasil.'),
(7, 'Botol plastik farmasi HDPE berlabel bilingual Jerman-Inggris',                'Lulus',       '✅ Kemasan sesuai standar farmasi Eropa.\nℹ️ Pertimbangkan penambahan QR code untuk informasi produk digital.'),
(8, 'Standing pouch berlabel bilingual Mandarin-Inggris, keterangan vegetarian',   'Perlu Revisi','❌ Label wajib mencantumkan informasi nutrisi sesuai GB 28050 (standar label pangan China).\nℹ️ Sertifikasi vegetarian China berbeda dengan standar internasional — perlu verifikasi.');

-- ============================================================
-- KAMPANYE
-- ============================================================
INSERT INTO kampanye (produk_id, nama_kampanye, elemen_kampanye, hasil_adaptasi, tanggal_kampanye) VALUES
(1, 'Ramadan Campaign 2025',
 'Slogan: Kehangatan di Setiap Tegukan\nVisual: keluarga muslim berkumpul saat iftar\nPesan: Teh premium halal untuk momen berharga bersama keluarga\nMedia: Instagram dan YouTube Arab',
 '✅ Elemen kampanye tidak memerlukan adaptasi khusus. Sangat relevan dengan nilai keluarga dan religiusitas Arab Saudi.',
 '2025-03-12'),
(2, 'Sakura Season Launch',
 'Slogan: Beauty from the Heart of Nature\nVisual: perempuan menggunakan serum dengan latar taman\nPesan: Natural Indonesian beauty secret, kini hadir di Jepang\nMedia: LINE dan Twitter Jepang',
 '✅ Elemen kampanye sesuai budaya Jepang.\n💡 Tambahkan elemen visual musim sakura untuk resonansi emosional lebih kuat.',
 '2025-03-17'),
(3, 'Spice Up Your Life',
 'Slogan: Feel the Heat, Live the Adventure\nVisual: anak muda Amerika challenge makan pedas\nPesan: The most exotic spicy snack from Indonesia\nMedia: TikTok dan Instagram US',
 '✅ Konsep challenge TikTok sesuai budaya Gen Z Amerika.\n💡 Pertimbangkan kolaborasi food influencer untuk jangkauan lebih luas.',
 '2025-03-22'),
(4, 'Batik for Everyone',
 'Slogan: Warisan Nusantara, Gaya Masa Kini\nVisual: keluarga muda muslim berpakaian batik di acara formal\nPesan: Tampil elegan dengan batik kontemporer\nMedia: Instagram dan TikTok Indonesia',
 '✅ Elemen kampanye sangat relevan dengan pasar Indonesia.',
 '2025-04-03'),
(5, 'Natural Wellness Campaign',
 'Slogan: Ancient Wisdom, Modern Health\nVisual: rempah nusantara dan konsumen sehat aktif\nPesan: Suplemen herbal alami untuk gaya hidup sehat\nMedia: YouTube dan Instagram India',
 '✅ Sesuai nilai kesehatan holistik konsumen India.\n💡 Sertakan testimoni konsumen vegetarian untuk memperkuat positioning.',
 '2025-04-12'),
(6, 'Cafezinho Culture Campaign',
 'Slogan: O Sabor do Mundo em Sua Xicara\nVisual: teman-teman santai minum kopi bersama di kafe urban\nPesan: Kopi specialty single origin yang membawa pengalaman baru\nMedia: Instagram dan WhatsApp Brasil',
 '✅ Pendekatan komunal sangat sesuai budaya Brasil yang kolektif.\n✅ Penggunaan bahasa Portugis lokal menunjukkan respek terhadap budaya lokal.\n💡 Pertimbangkan kolaborasi dengan influencer kopi Brasil.',
 '2025-04-17'),
(7, 'Natur und Gesundheit Campaign',
 'Slogan: Die Kraft der Natur aus Indonesien\nVisual: tanaman herbal segar, lab testing, sertifikat produk\nPesan: Jamu tradisional Indonesia dengan standar farmasi modern Eropa\nMedia: Facebook dan Instagram Jerman',
 '✅ Penekanan pada sertifikasi dan standar ilmiah sesuai ekspektasi konsumen Jerman.\n✅ Visual tanaman herbal segar selaras tren wellness di Jerman.\n💡 Tambahkan referensi penelitian ilmiah untuk meningkatkan kredibilitas.',
 '2025-04-22'),
(8, 'Healthy Snack China Launch',
 'Slogan: Jian Kang Ling Shi, Yin Ni Feng Wei\nVisual: anak muda urban China menikmati snack sehat di kantor\nPesan: Plant-based snack premium untuk gaya hidup sehat modern\nMedia: WeChat, Xiaohongshu dan Douyin',
 '⚠️ Hindari semua referensi yang berpotensi politis di pasar China.\n✅ Pendekatan plant-based sesuai tren health-conscious di China urban.\n💡 Platform Xiaohongshu sangat efektif untuk produk makanan premium.',
 '2025-05-03');

-- ============================================================
-- CONSUMER INSIGHT
-- ============================================================
INSERT INTO consumer_insight (negara_id, segmen_konsumen, preferensi_lokal, tanggal_update) VALUES
-- Dari sistem/admin (data awal)
(1, 'Millennial Muslim Urban',       'Produk halal, harga terjangkau, influencer lokal, platform: TikTok & Instagram',                                                              '2025-06-01'),
(2, 'Pekerja Kantoran 25-45',        'Kualitas premium, kemasan minimalis, brand trust tinggi, platform: LINE & Twitter',                                                           '2025-06-01'),
(3, 'Keluarga Konservatif',          'Halal certified, merek mapan, tidak ada visual perempuan tanpa hijab',                                                                        '2025-06-01'),
(4, 'Gen Z & Millennial',            'Sustainability, brand values, social media presence kuat, platform: Instagram & TikTok',                                                      '2025-06-01'),
(5, 'Kelas Menengah Urban',          'Value for money, hindari produk sapi non-halal, lokalitas penting',                                                                           '2025-06-01'),
-- Ditambah Brand Manager
(6, 'Urban Coffee Enthusiast',       'Kopi specialty, pengalaman premium, komunitas, platform: Instagram & WhatsApp, harga bisa lebih tinggi kalau story-nya kuat',               '2025-04-16'),
(7, 'Konsumen Wellness Jerman',      'Sertifikasi dan bukti ilmiah wajib ada, natural & organic, transparansi bahan, platform: Facebook & health magazine online',                '2025-04-21'),
(8, 'Health-Conscious Urban China',  'Plant-based trend berkembang pesat, kemasan premium, platform: Xiaohongshu & Douyin, Key Opinion Leader sangat berpengaruh',               '2025-05-02'),
-- Ditambah Admin (insight strategis)
(1, 'Gen Z Indonesia',               'Konten video pendek, kolaborasi artis lokal, harga promo flash sale, platform: TikTok utama, YouTube sekunder',                              '2025-05-10'),
(4, 'Hispanic American 25-40',       'Produk dengan heritage story kuat, bilingual packaging Inggris-Spanyol disukai, platform: Instagram & Facebook',                             '2025-05-15');

-- ============================================================
-- LAPORAN
-- user_id=2 Analyst | user_id=3 Brand Manager | user_id=1 Admin
-- ============================================================
INSERT INTO laporan (produk_id, user_id, judul_laporan, tanggal_laporan, isi_laporan, jenis_laporan) VALUES
(1, 2, 'Laporan Lokalisasi: NusaTea Premium ke Arab Saudi', '2025-03-13',
'=== LAPORAN LOKALISASI CMLE ===
Produk     : NusaTea Premium
Negara     : Arab Saudi
Dibuat oleh: Rizqullah Saputra (Marketing Analyst)
Tanggal    : 13 Maret 2025

--- 1. CULTURAL FIT ---
Skor: 92.00/100 | Risiko: Rendah
✅ Atribut halal relevan. ✅ Pendekatan keluarga diterima.

--- 2. BRAND SCREENING ---
Merek: NusaTea | Status: Aman

--- 3. PACKAGING ---
Status: Lulus — Kotak karton berlabel halal.

--- 4. KAMPANYE ---
Ramadan Campaign 2025 — ✅ Siap digunakan langsung.
=== END OF REPORT ===', 'Lengkap'),

(3, 2, 'Laporan Lokalisasi: KripikNusa Spicy ke Amerika Serikat', '2025-03-23',
'=== LAPORAN LOKALISASI CMLE ===
Produk     : KripikNusa Spicy
Negara     : Amerika Serikat
Dibuat oleh: Rizqullah Saputra (Marketing Analyst)
Tanggal    : 23 Maret 2025

--- 1. CULTURAL FIT ---
Skor: 68.00/100 | Risiko: Sedang
ℹ️ Wajib Nutrition Facts. ⚠️ Label spicy perlu penyesuaian FDA.

--- 2. BRAND SCREENING ---
Merek: KripikNusa | Status: Perlu Perubahan
Alternatif: NusaChips, SpicyNusa.

--- 3. PACKAGING ---
Status: Perlu Revisi — Nutrition Facts belum lengkap.

--- 4. KAMPANYE ---
Spice Up Your Life — ✅ Konsep TikTok challenge sesuai Gen Z.
=== END OF REPORT ===', 'Lengkap'),

(6, 3, 'Laporan Lokalisasi: KopiBrasi Blend ke Brasil', '2025-04-18',
'=== LAPORAN LOKALISASI CMLE ===
Produk     : KopiBrasi Blend
Negara     : Brasil
Dibuat oleh: Brand Manager
Tanggal    : 18 April 2025

--- 1. CULTURAL FIT ---
Skor: 83.00/100 | Risiko: Rendah
✅ Kesesuaian budaya baik. ✅ Pendekatan komunal relevan.

--- 2. BRAND SCREENING ---
Merek: KopiBrasi | Status: Aman

--- 3. PACKAGING ---
Status: Lulus — Kaleng aluminium bilingual, ramah lingkungan.

--- 4. KAMPANYE ---
Cafezinho Culture Campaign — ✅ Sangat sesuai budaya Brasil.

REKOMENDASI BRAND MANAGER:
Siap soft launch Q3 2025. Prioritas distribusi di Sao Paulo & Rio.
=== END OF REPORT ===', 'Lengkap'),

(7, 3, 'Laporan Lokalisasi: JamuWell Capsule ke Jerman', '2025-04-23',
'=== LAPORAN LOKALISASI CMLE ===
Produk     : JamuWell Capsule
Negara     : Jerman
Dibuat oleh: Brand Manager
Tanggal    : 23 April 2025

--- 1. CULTURAL FIT ---
Skor: 78.00/100 | Risiko: Rendah

--- 2. BRAND SCREENING ---
Merek: JamuWell | Status: Aman

--- 3. PACKAGING ---
Status: Lulus — Standar farmasi Eropa terpenuhi.

--- 4. KAMPANYE ---
Natur und Gesundheit — ✅ Pendekatan ilmiah sesuai ekspektasi Jerman.

REKOMENDASI BRAND MANAGER:
Perlu sertifikasi GMP Eropa sebelum launch. Target Q4 2025.
Fokus distribusi di toko natural health dan apoteker independen.
=== END OF REPORT ===', 'Lengkap'),

(8, 1, 'Laporan Monitoring: TempeChip Snack ke China', '2025-05-05',
'=== LAPORAN MONITORING ADMIN ===
Produk     : TempeChip Snack
Negara     : China
Dibuat oleh: Admin CMLE
Tanggal    : 5 Mei 2025

--- STATUS ANALISIS ---
Cultural Fit : 62.00/100 — Sedang
Brand        : Aman
Packaging    : Perlu Revisi

--- TEMUAN KRITIS ---
⚠️ Skor budaya 62 — risiko sedang, perlu strategi edukasi.
❌ Packaging belum memenuhi standar GB 28050 China.

--- KEPUTUSAN ADMIN ---
TAHAN launch sampai packaging compliance terpenuhi.
Minta Brand Manager kembangkan strategi edukasi pasar China.
Review ulang setelah packaging direvisi.
=== END OF REPORT ===', 'Lengkap'),

(4, 1, 'Laporan Monitoring: BatikModern Collection ke Indonesia', '2025-04-05',
'=== LAPORAN MONITORING ADMIN ===
Produk     : BatikModern Collection
Negara     : Indonesia
Dibuat oleh: Admin CMLE
Tanggal    : 5 April 2025

--- STATUS ANALISIS ---
Cultural Fit : 88.00/100 — Rendah
Brand        : Aman
Packaging    : Lulus

--- KESIMPULAN ---
✅ Profil lokalisasi terbaik bulan ini.
✅ Semua parameter lulus tanpa catatan kritis.
✅ Direkomendasikan untuk approval brand manager dan launch segera.
=== END OF REPORT ===', 'Lengkap');

-- ============================================================
-- VERIFIKASI AKHIR
-- ============================================================
SELECT 'pengguna'         AS tabel, COUNT(*) AS total FROM pengguna
UNION ALL SELECT 'negara',          COUNT(*) FROM negara
UNION ALL SELECT 'produk',          COUNT(*) FROM produk
UNION ALL SELECT 'analisis_budaya', COUNT(*) FROM analisis_budaya
UNION ALL SELECT 'merek',           COUNT(*) FROM merek
UNION ALL SELECT 'packaging',       COUNT(*) FROM packaging
UNION ALL SELECT 'kampanye',        COUNT(*) FROM kampanye
UNION ALL SELECT 'consumer_insight',COUNT(*) FROM consumer_insight
UNION ALL SELECT 'laporan',         COUNT(*) FROM laporan;
