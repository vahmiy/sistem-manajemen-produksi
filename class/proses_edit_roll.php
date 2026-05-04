<?php
// LOKASI FILE: /flower/class/proses_edit_roll.php

session_start();
include 'koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// 1. Ambil Data POST
// Pastikan semua data numerik di-cast (float)
$id_roll = (int) $_POST['id_roll'];
$nama_kain = trim($_POST['nama_kain'] ?? ''); // Diambil dari input readonly di form edit
$tgl_diterima = $_POST['tgl_diterima'] ?? date('Y-m-d');
$lebar_kain = (float) ($_POST['lebar_kain'] ?? 0.0);
$panjang_lama = (float) $_POST['panjang_lama'];
$panjang_baru = (float) $_POST['panjang_baru'];
$keterangan = trim($_POST['keterangan'] ?? '');

if ($id_roll <= 0 || empty($nama_kain) || $panjang_baru <= 0) {
    $_SESSION['status_message'] = "Data input tidak valid. Pastikan panjang kain > 0.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_kain.php");
    exit;
}

// Hitung Perbedaan Stok: (Baru - Lama)
$perbedaan_stok = $panjang_baru - $panjang_lama;

// Mulai Transaksi
mysqli_begin_transaction($koneksi);

try {
    // =============================================================
    // LANGKAH 1: UPDATE catatan di tabel transaksi_masuk
    // =============================================================
    $sql_update_roll = "UPDATE transaksi_masuk 
                        SET tgl_diterima = ?, lebar_kain = ?, panjang_yard_awal = ?, keterangan = ?
                        WHERE id_roll = ?";
    
    $stmt_roll = mysqli_prepare($koneksi, $sql_update_roll);
    
    // KOREKSI UTAMA ADA DI SINI:
    // Query memiliki 5 placeholder:
    // 1. tgl_diterima (s - string)
    // 2. lebar_kain (d - double)
    // 3. panjang_yard_awal (d - double)
    // 4. keterangan (s - string)
    // 5. id_roll (i - integer)
    // STRING TIPE DATA HARUSNYA: "sddsi" (5 elemen)
    
    if (!$stmt_roll) {
         throw new Exception("Prepare statement Gagal: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt_roll, "sddsi", 
        $tgl_diterima, $lebar_kain, $panjang_baru, $keterangan, $id_roll
    );
    
    if (!mysqli_stmt_execute($stmt_roll)) {
        throw new Exception("Gagal memperbarui catatan roll: " . mysqli_stmt_error($stmt_roll));
    }
    mysqli_stmt_close($stmt_roll);

    // =============================================================
    // LANGKAH 2: UPDATE saldo total di stok_master (Hanya jika ada perbedaan)
    // =============================================================
    // Menggunakan ambang batas 0.001 untuk menghindari masalah presisi floating point
    if (abs($perbedaan_stok) > 0.001) { 
        $sql_update_stok = "UPDATE stok_master 
                            SET stok_saat_ini = stok_saat_ini + ? 
                            WHERE nama_kain = ?"; 
        
        $stmt_stok = mysqli_prepare($koneksi, $sql_update_stok);
        
        if (!$stmt_stok) {
            throw new Exception("Prepare statement Gagal untuk stok: " . mysqli_error($koneksi));
        }
        
        // Tipe data: d (double) untuk perbedaan_stok, s (string) untuk nama_kain
        mysqli_stmt_bind_param($stmt_stok, "ds", $perbedaan_stok, $nama_kain);
        
        if (!mysqli_stmt_execute($stmt_stok)) {
            throw new Exception("Gagal menyesuaikan saldo master: " . mysqli_stmt_error($stmt_stok));
        }
        mysqli_stmt_close($stmt_stok);
    }

    // Commit transaksi jika semua berhasil
    mysqli_commit($koneksi);

    $msg_stok = (abs($perbedaan_stok) > 0.001) ? 
                "Saldo stok disesuaikan sebesar **" . number_format($perbedaan_stok, 2) . " Y**." : 
                "Tidak ada perubahan saldo stok yang signifikan.";

    $_SESSION['status_message'] = "Roll ID **{$id_roll}** berhasil diperbarui. " . $msg_stok;
    $_SESSION['status_type'] = "success";

} catch (Exception $e) {
    // Rollback transaksi jika ada kegagalan
    mysqli_rollback($koneksi);
    
    // Menampilkan detail error di pesan status
    $_SESSION['status_message'] = "Proses Edit Gagal! Error: " . $e->getMessage() . ". Data tidak diubah.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

header("Location: ../hal/tambah_kain.php");
exit;