<?php
// flower/class/hapus_transaksi_keluar.php
session_start();
include 'koneksi.php'; // Pastikan ini mengarah ke file koneksi database Anda

// 1. Cek keamanan dan ID
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['status_message'] = "ID Transaksi Keluar tidak ditemukan.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/data_mutasi_kain.php");
    exit;
}

$id_transaksi = mysqli_real_escape_string($koneksi, $_GET['id']);
$berhasil = false;

// Mulai Transaksi Database (PENTING untuk menjaga integritas data)
mysqli_begin_transaction($koneksi);

try {
    // 2. Ambil data transaksi lama yang akan dihapus (untuk mendapatkan nama kain dan kuantitas)
    $sql_get = "SELECT nama_kain, jumlah_potong_yard FROM transaksi_keluar WHERE id_keluar = '{$id_transaksi}'";
    $result_get = mysqli_query($koneksi, $sql_get);
    $data_lama = mysqli_fetch_assoc($result_get);

    if (!$data_lama) {
        throw new Exception("Data transaksi keluar ID: {$id_transaksi} tidak ditemukan.");
    }

    $nama_kain = mysqli_real_escape_string($koneksi, $data_lama['nama_kain']);
    $jumlah_yard_lama = $data_lama['jumlah_potong_yard']; // Ini adalah nilai KELUAR, jadi DITAMBAH kembali

    // 3. Update Stok Master (Menambahkan stok karena transaksi 'Keluar' dibatalkan/dihapus)
    $sql_update_stok = "
        UPDATE stok_master 
        SET stok_saat_ini = stok_saat_ini + {$jumlah_yard_lama} 
        WHERE nama_kain = '{$nama_kain}'
    ";
    if (!mysqli_query($koneksi, $sql_update_stok)) {
        throw new Exception("Gagal update stok master: " . mysqli_error($koneksi));
    }
    
    // 4. Hapus Transaksi Keluar
    $sql_delete = "DELETE FROM transaksi_keluar WHERE id_keluar = '{$id_transaksi}'";
    if (!mysqli_query($koneksi, $sql_delete)) {
        throw new Exception("Gagal menghapus transaksi keluar: " . mysqli_error($koneksi));
    }
    
    // 5. Commit Transaksi jika semua berhasil
    mysqli_commit($koneksi);
    $berhasil = true;
    
} catch (Exception $e) {
    // Rollback jika terjadi kesalahan
    mysqli_rollback($koneksi);
    $error_message = "Gagal Hapus Transaksi Keluar! " . $e->getMessage();
}

// 6. Redirect ke halaman data dengan pesan
if ($berhasil) {
    $_SESSION['status_message'] = "Transaksi Keluar ID: **{$id_transaksi}** berhasil dihapus. Stok master dikembalikan **{$jumlah_yard_lama} Y**.";
    $_SESSION['status_type'] = "success";
} else {
    $_SESSION['status_message'] = $error_message;
    $_SESSION['status_type'] = "danger";
}

header("Location: ../hal/data_mutasi_kain.php");
exit;
?>