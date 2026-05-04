<?php
// LOKASI FILE: /flower/class/proses_tambah_kertas.php

session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); 
    exit;
}

// Ambil dan bersihkan data
$nama_kertas = mysqli_real_escape_string($koneksi, $_POST['nama_kertas']);
$tgl_diterima = mysqli_real_escape_string($koneksi, $_POST['tgl_diterima']);
$lebar_kertas = (float)$_POST['lebar_kertas'];
$panjang_awal = (float)$_POST['panjang_awal'];
$satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
$gramasi = !empty($_POST['gramasi']) ? (int)$_POST['gramasi'] : 0; // Tambahkan gramasi

mysqli_begin_transaction($koneksi);

try {
    // --- 1. INSERT ke transaksi_kertas_masuk (Histori) ---
    $sql_insert_transaksi = "INSERT INTO transaksi_kertas_masuk 
                             (nama_kertas, tgl_diterima, gramasi, lebar_kertas, panjang_awal, satuan, keterangan, tgl_input) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt_transaksi = mysqli_prepare($koneksi, $sql_insert_transaksi);
    mysqli_stmt_bind_param($stmt_transaksi, "ssidsds", 
        $nama_kertas, $tgl_diterima, $gramasi, $lebar_kertas, $panjang_awal, $satuan, $keterangan);
    
    if (!mysqli_stmt_execute($stmt_transaksi)) {
        throw new Exception("Gagal memasukkan data transaksi: " . mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt_transaksi);

    // --- 2. UPSERT (Update/Insert) ke stok_kertas_master ---

    // Cek apakah jenis kertas dan satuan sudah ada
    $sql_check = "SELECT id_stok_kertas, stok_saat_ini FROM stok_kertas_master WHERE nama_kertas = ? AND satuan = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "ss", $nama_kertas, $satuan);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        // UPDATE: Kertas sudah ada, tambahkan stok
        $sql_update_stok = "UPDATE stok_kertas_master SET stok_saat_ini = stok_saat_ini + ? WHERE nama_kertas = ? AND satuan = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update_stok);
        mysqli_stmt_bind_param($stmt_update, "dss", $panjang_awal, $nama_kertas, $satuan);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Gagal mengupdate stok master: " . mysqli_error($koneksi));
        }
        mysqli_stmt_close($stmt_update);

    } else {
        // INSERT: Kertas baru
        $sql_insert_stok = "INSERT INTO stok_kertas_master (nama_kertas, satuan, stok_saat_ini) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert_stok);
        mysqli_stmt_bind_param($stmt_insert, "ssd", $nama_kertas, $satuan, $panjang_awal);
        
        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception("Gagal memasukkan stok master baru: " . mysqli_error($koneksi));
        }
        mysqli_stmt_close($stmt_insert);
    }
    
    mysqli_commit($koneksi);
    $_SESSION['status_message'] = "Stok kertas '$nama_kertas' sejumlah $panjang_awal $satuan berhasil ditambahkan!";
    $_SESSION['status_type'] = "success";

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['status_message'] = "Kesalahan Transaksi: " . $e->getMessage();
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);
header("Location: ../hal/tambah_kertas.php");
exit;
?>