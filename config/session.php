<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']   ?? null,
        'nama'  => $_SESSION['nama']      ?? '',
        'email' => $_SESSION['email']     ?? '',
        'role'  => $_SESSION['role']      ?? '',
    ];
}

function isRole(string $role): bool {
    return ($_SESSION['role'] ?? '') === $role;
}

function hasRole(array $roles): bool {
    return in_array($_SESSION['role'] ?? '', $roles, true);
}
