<?php
// LOKASI FILE: /flower/class/proses_edit_kain.php

session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_kain.php"); 
    exit;
}

// 1. Ambil dan Bersihkan Data
$id_kain = (int) $_POST['id_kain'];
$nama_kain = trim($_POST['nama_kain']);
$tgl_diterima = $_POST['tgl_diterima'];
$lebar_kain = (float) $_POST['lebar_kain'];
$panjang_yard = (float) $_POST['panjang_yard'];

// 2. Validasi Dasar
$error_msg = "";
if (empty($nama_kain) || empty($tgl_diterima) || $lebar_kain <= 0 || $panjang_yard <= 0) {
    $error_msg = "Semua kolom wajib diisi dan harus bernilai positif.";
}

if ($error_msg) {
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/edit_kain.php?id=" . $id_kain); 
    exit;
}

// 3. Persiapkan dan Eksekusi Prepared Statement (UPDATE)
$sql = "UPDATE bahan_baku 
        SET nama_kain = ?, tgl_diterima = ?, lebar_kain = ?, panjang_yard = ?, stok_saat_ini = ? 
        WHERE id_kain = ?";
// Catatan: panjang_meter akan dihitung otomatis oleh MySQL karena kolomnya STORED

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // Tipe data: string, string, double, double, double, integer
    // Kita set stok_saat_ini sama dengan panjang_yard yang baru
    mysqli_stmt_bind_param($stmt, "ssdddi", $nama_kain, $tgl_diterima, $lebar_kain, $panjang_yard, $panjang_yard, $id_kain);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['status_message'] = "Catatan kain ID: **{$id_kain}** berhasil diperbarui!";
        $_SESSION['status_type'] = "success";
    } else {
        $_SESSION['status_message'] = "Gagal memperbarui catatan kain. Error: " . mysqli_error($koneksi);
        $_SESSION['status_type'] = "danger";
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['status_message'] = "Error saat menyiapkan statement UPDATE.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

// FINAL REDIRECT ke halaman list utama
header("Location: ../hal/tambah_kain.php");
exit;