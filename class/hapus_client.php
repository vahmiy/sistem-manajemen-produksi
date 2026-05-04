<?php
// LOKASI FILE: /flower/class/hapus_client.php

session_start();

// Cek autentikasi (opsional, tapi disarankan)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

// Include koneksi
include 'koneksi.php'; 

// 1. Pastikan ID dikirimkan melalui URL (GET)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status_message'] = "ID Client tidak ditemukan untuk dihapus.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_client.php");
    exit;
}

// Ambil dan bersihkan ID
$id_client = (int) $_GET['id'];
$nama_client = ""; // Untuk menyimpan nama sebelum dihapus

// 2. Ambil nama client sebelum dihapus (Opsional, untuk pesan sukses yang lebih informatif)
$sql_select = "SELECT nama_client FROM clients WHERE id_client = ?";
$stmt_select = mysqli_prepare($koneksi, $sql_select);

if ($stmt_select) {
    mysqli_stmt_bind_param($stmt_select, "i", $id_client);
    mysqli_stmt_execute($stmt_select);
    $result_select = mysqli_stmt_get_result($stmt_select);
    
    if ($row = mysqli_fetch_assoc($result_select)) {
        $nama_client = $row['nama_client'];
    }
    mysqli_stmt_close($stmt_select);
}

// 3. Persiapkan dan Eksekusi Query DELETE
$sql_delete = "DELETE FROM clients WHERE id_client = ?";
$stmt_delete = mysqli_prepare($koneksi, $sql_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_client);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Jika penghapusan berhasil
        $msg_name = !empty($nama_client) ? htmlspecialchars($nama_client) : "ID: " . $id_client;
        $_SESSION['status_message'] = "Data client **" . $msg_name . "** berhasil dihapus.";
        $_SESSION['status_type'] = "success";
    } else {
        // Jika penghapusan gagal
        $_SESSION['status_message'] = "Gagal menghapus client. Terjadi kesalahan database: " . mysqli_error($koneksi);
        $_SESSION['status_type'] = "danger";
    }
    mysqli_stmt_close($stmt_delete);
} else {
    $_SESSION['status_message'] = "Error saat menyiapkan statement DELETE.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

// Arahkan kembali ke halaman list client
header("Location: ../hal/tambah_client.php");
exit;