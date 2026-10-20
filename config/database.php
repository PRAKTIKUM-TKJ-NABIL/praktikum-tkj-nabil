<?php
// ============================================================
// BACA KONFIGURASI DARI FILE .env
// ============================================================

// Tentukan lokasi file .env (satu level di atas folder config)
$env_file = __DIR__ . '/../.env';

// Cek apakah file .env ada
if (!file_exists($env_file)) {
    die("Error: File .env tidak ditemukan. Silakan copy .env.example menjadi .env dan isi konfigurasi database.");
}

// Baca file .env
$env = parse_ini_file($env_file);

// Validasi apakah parsing berhasil
if ($env === false) {
    die("Error: Gagal membaca file .env. Periksa format penulisannya.");
}

// Ambil nilai dari .env dengan fallback default
$host     = $env['DB_HOST']     ?? 'localhost';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';
$database = $env['DB_NAME']     ?? 'keuangan_db';

// ============================================================
// BUAT KONEKSI
// ============================================================
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ============================================================
// KONFIGURASI TAMBAHAN
// ============================================================

// Set timezone PHP
date_default_timezone_set('Asia/Jakarta');

// Set timezone MySQL agar konsisten dengan PHP (penting untuk hosting)
$conn->query("SET time_zone = '+07:00'");

// Set charset
$conn->set_charset("utf8");

// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>