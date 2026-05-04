<?php
// LOKASI FILE: /flower/class/proses_kain.php

session_start();
include 'koneksi.php'; // Pastikan path benar

if (!isset($_SESSION['loggedin']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_kain.php"); // Arahkan kembali jika akses tidak benar
    exit;
}

// 1. Ambil dan Bersihkan Data
$nama_kain = trim($_POST['nama_kain']);
$tgl_diterima = $_POST['tgl_diterima'];
$lebar_kain = (float) $_POST['lebar_kain'];
$panjang_yard = (float) $_POST['panjang_yard'];

// 2. Validasi Dasar
if (empty($nama_kain) || empty($tgl_diterima) || $lebar_kain <= 0 || $panjang_yard <= 0) {
    $_SESSION['status_message'] = "Semua kolom wajib diisi dan harus bernilai positif.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// 3. Proses Transaksi: INSERT data penerimaan dan UPDATE STOK

// Mulai transaksi untuk memastikan konsistensi data
mysqli_begin_transaction($koneksi);

try {
    // A. INSERT data penerimaan baru ke tabel bahan_baku (panjang_meter dihitung otomatis)
    $sql_insert = "INSERT INTO bahan_baku (nama_kain, tgl_diterima, lebar_kain, panjang_yard, stok_saat_ini) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
    
    // Stok saat INSERT pertama kali sama dengan panjang kain yang baru diterima
    mysqli_stmt_bind_param($stmt_insert, "ssdii", $nama_kain, $tgl_diterima, $lebar_kain, $panjang_yard, $panjang_yard);
    mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);

    // B. UPDATE STOK: Perbarui total stok untuk nama kain yang sama
    // Catatan: Karena kita tidak memiliki tabel stok terpisah, kita akan menggunakan SUM
    // atau jika Anda ingin tabel terpisah, ini akan lebih kompleks. 
    // Untuk tujuan pencatatan awal, kita anggap setiap baris adalah "roll" atau "batch" baru.

    // Jika Anda ingin *menghitung total stok* berdasarkan nama kain di kolom terpisah, 
    // Anda harus membuat tabel `stok_kain` yang terpisah.
    
    // **ASUMSI SEMENTARA (Sesuai dengan kolom 'stok_saat_ini' yang Anda minta):**
    // Kita anggap `stok_saat_ini` adalah *jumlah panjang* dari batch yang baru masuk.
    // Jika Anda ingin ini menjadi *total akumulasi stok*, kode akan lebih kompleks.
    // Kita akan biarkan `stok_saat_ini` sama dengan `panjang_yard` pada baris itu.

    // Commit transaksi
    mysqli_commit($koneksi);
    
    $_SESSION['status_message'] = "Penerimaan Bahan Baku Kain **" . htmlspecialchars($nama_kain) . "** berhasil dicatat (Panjang: " . $panjang_yard . " Yard).";
    $_SESSION['status_type'] = "success";

} catch (mysqli_sql_exception $exception) {
    // Rollback jika ada error
    mysqli_rollback($koneksi);
    $_SESSION['status_message'] = "Gagal mencatat data kain. Error: " . $exception->getMessage();
    $_SESSION['status_type'] = "danger";

}


// =============================================================
// LOGIKA TAMBAH/UPDATE SALDO TOTAL DI tabel stok_kain_aktual
// =============================================================

// Cek apakah nama kain sudah ada di tabel stok_kain_aktual
$sql_check = "SELECT id_stok FROM stok_kain_aktual WHERE nama_kain = ?";
$stmt_check = mysqli_prepare($koneksi, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $nama_kain);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    // KAIN SUDAH ADA: Lakukan UPDATE (Tambahkan stok)
    $sql_saldo = "UPDATE stok_kain_aktual 
                  SET stok_saat_ini = stok_saat_ini + ? 
                  WHERE nama_kain = ?";
    $status_ops = "Diperbarui";
} else {
    // KAIN BELUM ADA: Lakukan INSERT baru
    $sql_saldo = "INSERT INTO stok_kain_aktual (nama_kain, stok_saat_ini) 
                  VALUES (?, ?)";
    $status_ops = "Ditambahkan";
}

$stmt_saldo = mysqli_prepare($koneksi, $sql_saldo);

if ($status_ops == "Diperbarui") {
    mysqli_stmt_bind_param($stmt_saldo, "ds", $panjang_yard, $nama_kain);
} else {
    mysqli_stmt_bind_param($stmt_saldo, "sd", $nama_kain, $panjang_yard);
}

mysqli_stmt_execute($stmt_saldo);
mysqli_stmt_close($stmt_saldo);

mysqli_close($koneksi);
header("Location: ../hal/tambah_kain.php");
exit;
?>