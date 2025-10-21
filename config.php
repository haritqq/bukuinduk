<?php
// Pengaturan koneksi database
$db_host = 'localhost';
$db_user = 'root'; // Default username XAMPP
$db_pass = '';     // Default password XAMPP
$db_name = 'buku_induk_pegawai';

// Membuat koneksi
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Memulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>