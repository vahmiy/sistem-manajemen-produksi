<?php
// LOKASI FILE: /flower/class/proses_tambah_kain.php

session_start();
include 'koneksi.php'; 

// Cek sesi dan method POST
if (!isset($_SESSION['loggedin']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// 1. Ambil dan validasi data POST
$nama_kain = trim($_POST['nama_kain'] ?? '');
$tgl_diterima = $_POST['tgl_diterima'] ?? date('Y-m-d');
$lebar_kain = (float) ($_POST['lebar_kain'] ?? 0);
$panjang_yard = (float) ($_POST['panjang_yard'] ?? 0);
$keterangan = trim($_POST['keterangan'] ?? '');

if (empty($nama_kain) || $panjang_yard <= 0) {
    $_SESSION['status_message'] = "Nama kain dan Panjang Yard harus diisi!";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// =============================================================
// LANGKAH 1: INSERT data roll ke tabel transaksi_masuk (Tracking)
// =============================================================
$sql_masuk = "INSERT INTO transaksi_masuk (
                nama_kain, tgl_diterima, panjang_yard_awal, lebar_kain, keterangan
            ) VALUES (?, ?, ?, ?, ?)";

$stmt_masuk = mysqli_prepare($koneksi, $sql_masuk);
// Tipe data: s, s, d, d, s
mysqli_stmt_bind_param($stmt_masuk, "ssdds", $nama_kain, $tgl_diterima, $panjang_yard, $lebar_kain, $keterangan);

if (!mysqli_stmt_execute($stmt_masuk)) {
    $_SESSION['status_message'] = "Gagal mencatat transaksi masuk: " . mysqli_error($koneksi);
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}
mysqli_stmt_close($stmt_masuk);


// =============================================================
// LANGKAH 2: UPDATE/INSERT saldo total di stok_master (Saldo Tunggal)
// =============================================================

// Cek apakah nama kain sudah ada di tabel stok_master
$sql_check = "SELECT id_stok FROM stok_master WHERE nama_kain = ?";
$stmt_check = mysqli_prepare($koneksi, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $nama_kain);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    // KAIN SUDAH ADA: Lakukan UPDATE (Tambahkan stok)
    $sql_saldo = "UPDATE stok_master 
                  SET stok_saat_ini = stok_saat_ini + ? 
                  WHERE nama_kain = ?";
    $stmt_saldo = mysqli_prepare($koneksi, $sql_saldo);
    mysqli_stmt_bind_param($stmt_saldo, "ds", $panjang_yard, $nama_kain);
    $message_ops = "diperbarui";
} else {
    // KAIN BELUM ADA: Lakukan INSERT baru
    $sql_saldo = "INSERT INTO stok_master (nama_kain, stok_saat_ini) 
                  VALUES (?, ?)";
    $stmt_saldo = mysqli_prepare($koneksi, $sql_saldo);
    mysqli_stmt_bind_param($stmt_saldo, "sd", $nama_kain, $panjang_yard);
    $message_ops = "ditambahkan";
}

if (mysqli_stmt_execute($stmt_saldo)) {
    $_SESSION['status_message'] = "Penerimaan kain **{$nama_kain}** (**{$panjang_yard} Y**) berhasil dicatat. Saldo total {$message_ops}.";
    $_SESSION['status_type'] = "success";
} else {
    $_SESSION['status_message'] = "Penerimaan kain berhasil dicatat, TAPI GAGAL memperbarui saldo total: " . mysqli_error($koneksi);
    $_SESSION['status_type'] = "warning";
}

mysqli_stmt_close($stmt_saldo);
mysqli_close($koneksi);

header("Location: ../hal/tambah_kain.php");
exit;