<?php
// LOKASI FILE: /flower/class/proses_client.php

// Tiga baris ini akan menampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// PENTING: Koneksi.php ada di folder yang sama (class), jadi langsung include
include 'koneksi.php'; 

// Cek hanya method POST yang diizinkan
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirect kembali ke halaman form: Mundur (../) ke /flower/ lalu masuk ke /hal/
    header("Location: ../hal/tambah_client.php"); 
    exit;
}

// 1. Ambil dan Bersihkan Data
$nama = trim($_POST['nama']);
$email = trim($_POST['email']);
$no_hp = trim($_POST['no_hp']);

// 2. Validasi PHP
$error_msg = "";

if (empty($nama) || empty($email) || empty($no_hp)) {
    $error_msg = "Semua kolom wajib diisi.";
} 
// Validasi Email
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = "Format email tidak valid.";
}
// Validasi Nomor HP
else {
    $no_hp_bersih = preg_replace('/[^0-9]/', '', $no_hp);

    if (!ctype_digit($no_hp_bersih) || strlen($no_hp_bersih) < 9 || strlen($no_hp_bersih) > 15) {
        $error_msg = "Nomor HP tidak valid. Hanya angka (9-15 digit) yang diperbolehkan.";
    }
}

// Jika ada error, simpan pesan dan redirect
if ($error_msg) {
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    
    // REDIRECT ERROR
    header("Location: ../hal/tambah_client.php"); 
    exit;
}

// Data sudah bersih dan valid, lanjutkan ke database
$no_hp_final = $no_hp_bersih;

// 3. Persiapkan dan Eksekusi Prepared Statement (Mencegah SQL Injection)
$sql = "INSERT INTO clients (nama_client, email_client, no_hp_client) VALUES (?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $no_hp_final);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['status_message'] = "Data client **" . htmlspecialchars($nama) . "** berhasil ditambahkan!";
        $_SESSION['status_type'] = "success";
    } else {
        $error_db = "Gagal menyimpan client. ";
        if (mysqli_errno($koneksi) == 1062) {
            $error_db .= "Email client sudah terdaftar.";
        } else {
            $error_db .= "Terjadi kesalahan database: " . mysqli_error($koneksi);
        }
        $_SESSION['status_message'] = $error_db;
        $_SESSION['status_type'] = "danger";
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['status_message'] = "Error saat menyiapkan statement.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

// FINAL REDIRECT BERHASIL
header("Location: ../hal/tambah_client.php");
exit;