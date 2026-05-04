<?php
// LOKASI FILE: /flower/class/hapus_kain.php

session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status_message'] = "ID Kain tidak ditemukan untuk dihapus.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}

$id_kain = (int) $_GET['id'];

// 1. Persiapkan dan Eksekusi Query DELETE
// Karena kolom stok_saat_ini diabaikan (hanya untuk tampilan), 
// kita cukup menghapus barisnya. Jika Anda memiliki tabel stok terpisah, 
// Anda perlu logika yang lebih kompleks di sini.

$sql_delete = "DELETE FROM bahan_baku WHERE id_kain = ?";
$stmt_delete = mysqli_prepare($koneksi, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_kain);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        $_SESSION['status_message'] = "Catatan penerimaan kain ID: **{$id_kain}** berhasil dihapus. Total stok otomatis terbarui.";
        $_SESSION['status_type'] = "success";
    } else {
        $_SESSION['status_message'] = "Gagal menghapus catatan kain. Terjadi kesalahan database: " . mysqli_error($koneksi);
        $_SESSION['status_type'] = "danger";
    }
    mysqli_stmt_close($stmt_delete);
} else {
    $_SESSION['status_message'] = "Error saat menyiapkan statement DELETE.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

// Arahkan kembali ke halaman data kain
header("Location: ../hal/tambah_kain.php");
exit;