<?php
require_once __DIR__ . '/../config/db.php';

class Pengguna {
    private mysqli $db;
    public int    $user_id;
    public string $nama_pengguna;
    public string $email;
    public string $password;
    public string $role;
    public string $tanggal_daftar;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function login(string $email, string $password): array {
        $stmt = $this->db->prepare(
            "SELECT user_id, nama_pengguna, email, password, role FROM pengguna WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Email tidak ditemukan'];
        }

        $user = $result->fetch_assoc();
        // Support MD5 (demo) dan password_hash
        $valid = (md5($password) === $user['password']) ||
                 password_verify($password, $user['password']);

        if (!$valid) {
            return ['success' => false, 'message' => 'Password salah'];
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['nama']    = $user['nama_pengguna'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        return ['success' => true, 'user' => $user];
    }

    public function logout(): void {
        session_destroy();
    }

    public function updateProfil(int $userId, string $nama, string $email): bool {
        $stmt = $this->db->prepare(
            "UPDATE pengguna SET nama_pengguna = ?, email = ? WHERE user_id = ?"
        );
        $stmt->bind_param('ssi', $nama, $email, $userId);
        return $stmt->execute();
    }

    public function getAll(): array {
        $result = $this->db->query(
            "SELECT user_id, nama_pengguna, email, role, tanggal_daftar FROM pengguna ORDER BY tanggal_daftar DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT user_id, nama_pengguna, email, role, tanggal_daftar FROM pengguna WHERE user_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}
