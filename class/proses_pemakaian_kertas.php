<?php
// LOKASI FILE: /flower/class/proses_pemakaian_kertas.php

session_start();
include 'koneksi.php'; 

// Tentukan lokasi redirect (jika gagal atau sukses)
$redirect_location = "../hal/tambah_pemakaian_kertas.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); 
    exit;
}

// Ambil data dan pecah nama_kertas dan satuan dari dropdown
// Format: "Nama Kertas|Satuan"
$kertas_data = explode('|', $_POST['nama_kertas'] ?? '');

if (count($kertas_data) < 2) {
    $_SESSION['status_message'] = "Pilihan kertas tidak valid. Silakan pilih jenis kertas dari daftar.";
    $_SESSION['status_type'] = "danger";
    header("Location: " . $redirect_location);
    exit;
}

$nama_kertas = mysqli_real_escape_string($koneksi, $kertas_data[0]);
$satuan = mysqli_real_escape_string($koneksi, $kertas_data[1]);

$tgl_keluar = mysqli_real_escape_string($koneksi, $_POST['tgl_keluar'] ?? date('Y-m-d'));
$qty_keluar = (float)($_POST['qty_keluar'] ?? 0);
$tujuan_produksi = mysqli_real_escape_string($koneksi, $_POST['tujuan_produksi'] ?? 'Tidak Tercatat');
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

if ($qty_keluar <= 0) {
    $_SESSION['status_message'] = "Jumlah pemakaian harus lebih dari nol.";
    $_SESSION['status_type'] = "danger";
    header("Location: " . $redirect_location);
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // --- 1. Cek Stok Saat Ini ---
    $sql_check_stok = "SELECT stok_saat_ini FROM stok_kertas_master WHERE nama_kertas = ? AND satuan = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check_stok);
    mysqli_stmt_bind_param($stmt_check, "ss", $nama_kertas, $satuan);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        throw new Exception("Stok master untuk kertas ini ($nama_kertas) tidak ditemukan.");
    }
    
    $row_stok = mysqli_fetch_assoc($result_check);
    $stok_saat_ini = $row_stok['stok_saat_ini'];
    mysqli_stmt_close($stmt_check);

    if ($stok_saat_ini < $qty_keluar) {
        throw new Exception("Stok tidak mencukupi! Tersisa hanya " . number_format($stok_saat_ini, 2) . " $satuan. (Kurang " . number_format($qty_keluar - $stok_saat_ini, 2) . ")");
    }

    // --- 2. UPDATE stok_kertas_master (Pengurangan) ---
    $sql_update_stok = "UPDATE stok_kertas_master SET stok_saat_ini = stok_saat_ini - ? WHERE nama_kertas = ? AND satuan = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update_stok);
    mysqli_stmt_bind_param($stmt_update, "dss", $qty_keluar, $nama_kertas, $satuan);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Gagal mengurangi stok master: " . mysqli_stmt_error($stmt_update));
    }
    mysqli_stmt_close($stmt_update);

    // --- 3. INSERT ke transaksi_kertas_keluar (Histori Penggunaan) ---
    $sql_insert_keluar = "INSERT INTO transaksi_kertas_keluar 
                          (tgl_keluar, nama_kertas, qty_keluar, satuan, tujuan_produksi, keterangan) 
                          VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_keluar = mysqli_prepare($koneksi, $sql_insert_keluar);
    
    // KOREKSI TEPAT: 6 variabel, 6 tipe data (s s d s s s)
    mysqli_stmt_bind_param($stmt_keluar, "ssdsss", 
        $tgl_keluar, $nama_kertas, $qty_keluar, $satuan, $tujuan_produksi, $keterangan);
    
    if (!mysqli_stmt_execute($stmt_keluar)) {
        throw new Exception("Gagal mencatat histori pemakaian: " . mysqli_stmt_error($stmt_keluar));
    }
    mysqli_stmt_close($stmt_keluar);
    
    mysqli_commit($koneksi);
    $_SESSION['status_message'] = "Pemakaian " . number_format($qty_keluar, 2) . " $satuan kertas '$nama_kertas' untuk PO $tujuan_produksi berhasil dicatat!";
    $_SESSION['status_type'] = "success";

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['status_message'] = "Kesalahan Transaksi Pemakaian: " . $e->getMessage();
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);
header("Location: " . $redirect_location);
exit;
?>