<?php
// LOKASI FILE: /flower/class/hapus_roll_masuk.php

session_start();
include 'koneksi.php';

// Pastikan pengguna login dan data dikirim via GET
if (!isset($_SESSION['loggedin']) || $_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// 1. Ambil data dari URL (GET parameters)
$id_roll = (int) $_GET['id'] ?? 0;
$nama_kain = trim($_GET['kain'] ?? '');
$panjang_yard = (float) $_GET['panjang'] ?? 0.00; // Panjang yang harus dikembalikan/dikurangi

if ($id_roll <= 0 || empty($nama_kain) || $panjang_yard <= 0) {
    $_SESSION['status_message'] = "Data roll tidak valid untuk dihapus.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// Mulai Transaksi untuk memastikan kedua langkah berhasil atau gagal semua
mysqli_begin_transaction($koneksi);

try {
    // =============================================================
    // LANGKAH 1: Hapus catatan dari tabel transaksi_masuk
    // =============================================================
    $sql_delete = "DELETE FROM transaksi_masuk WHERE id_roll = ?";
    $stmt_delete = mysqli_prepare($koneksi, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id_roll);
    
    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Gagal menghapus catatan roll.");
    }
    
    // Cek apakah ada baris yang benar-benar terhapus
    if (mysqli_stmt_affected_rows($stmt_delete) == 0) {
        throw new Exception("Catatan roll tidak ditemukan atau sudah terhapus.");
    }
    mysqli_stmt_close($stmt_delete);

    // =============================================================
    // LANGKAH 2: Kurangi saldo total di stok_master
    // =============================================================
    $sql_update_stok = "UPDATE stok_master 
                        SET stok_saat_ini = stok_saat_ini - ? 
                        WHERE nama_kain = ?"; 
    
    $stmt_stok = mysqli_prepare($koneksi, $sql_update_stok);
    // Kita kurangi saldo sebesar panjang roll yang dihapus
    mysqli_stmt_bind_param($stmt_stok, "ds", $panjang_yard, $nama_kain);
    
    if (!mysqli_stmt_execute($stmt_stok)) {
        throw new Exception("Gagal mengurangi saldo master.");
    }
    
    // Pastikan saldo master terpengaruh
    if (mysqli_stmt_affected_rows($stmt_stok) == 0) {
        throw new Exception("Saldo master tidak terpengaruh, cek nama kain.");
    }
    mysqli_stmt_close($stmt_stok);

    // Commit transaksi jika semua berhasil
    mysqli_commit($koneksi);

    $_SESSION['status_message'] = "Catatan Roll ID **{$id_roll}** dan saldo stok **{$nama_kain}** (**" . number_format($panjang_yard, 2) . " Y**) berhasil dihapus.";
    $_SESSION['status_type'] = "success";

} catch (Exception $e) {
    // Rollback transaksi jika ada kegagalan
    mysqli_rollback($koneksi);
    
    $_SESSION['status_message'] = "Proses Hapus Gagal! Error: " . $e->getMessage() . ". Data saldo tidak diubah.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

header("Location: ../hal/tambah_kain.php");
exit;