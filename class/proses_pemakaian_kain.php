<?php
// LOKASI FILE: /flower/class/proses_pemakaian_kain.php (KODE FINAL)

session_start();
include 'koneksi.php'; 

$redirect_location = "../hal/tambah_pemakaian_kain.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); 
    exit;
}

// Ambil data dan pecah nama_kain (format: NamaKain|Satuan)
$kain_data = explode('|', $_POST['nama_kain'] ?? '');

if (count($kain_data) < 2) {
    $_SESSION['status_message'] = "Pilihan kain tidak valid. Harap pilih dari daftar.";
    $_SESSION['status_type'] = "danger";
    header("Location: " . $redirect_location);
    exit;
}

// Bersihkan dan tetapkan variabel
$nama_kain = mysqli_real_escape_string($koneksi, $kain_data[0]);
$satuan_display = mysqli_real_escape_string($koneksi, $kain_data[1]); // Hanya untuk display pesan
$tgl_keluar = mysqli_real_escape_string($koneksi, $_POST['tgl_keluar'] ?? date('Y-m-d'));
$qty_keluar = (float)($_POST['qty_keluar'] ?? 0); // Kuantitas dalam Yard
$tujuan_produksi = mysqli_real_escape_string($koneksi, $_POST['tujuan_produksi'] ?? 'PO-Tidak-Tercatat'); 
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? ''); // Keterangan diabaikan saat INSERT ke transaksi_keluar

if ($qty_keluar <= 0) {
    $_SESSION['status_message'] = "Jumlah pemakaian kain harus lebih dari nol.";
    $_SESSION['status_type'] = "danger";
    header("Location: " . $redirect_location);
    exit;
}

// --- LOGIKA UTAMA (TRANSACTION) ---
mysqli_begin_transaction($koneksi);

try {
    // 1. Cek Stok Saat Ini di stok_master
    $sql_check_stok = "SELECT stok_saat_ini FROM stok_master WHERE nama_kain = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check_stok);
    mysqli_stmt_bind_param($stmt_check, "s", $nama_kain);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        throw new Exception("Stok master untuk kain ini ($nama_kain) tidak ditemukan di tabel 'stok_master'.");
    }
    
    $row_stok = mysqli_fetch_assoc($result_check);
    $stok_saat_ini = $row_stok['stok_saat_ini'];
    mysqli_stmt_close($stmt_check);
    
    if ($stok_saat_ini < $qty_keluar) {
        throw new Exception("Stok tidak mencukupi! Tersisa hanya " . number_format($stok_saat_ini, 2) . " $satuan_display.");
    }

    // 2. UPDATE stok_master (Pengurangan)
    $sql_update_stok = "UPDATE stok_master SET stok_saat_ini = stok_saat_ini - ? WHERE nama_kain = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update_stok);
    // Tipe data: ds (d:qty_keluar, s:nama_kain)
    mysqli_stmt_bind_param($stmt_update, "ds", $qty_keluar, $nama_kain);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Gagal mengurangi stok master kain: " . mysqli_stmt_error($stmt_update));
    }
    mysqli_stmt_close($stmt_update);

    // 3. INSERT ke transaksi_keluar (Histori Penggunaan)
    // KOLOM ANDA: (id_order, nama_kain, tgl_potong, jumlah_potong_yard)
    $sql_insert_keluar = "INSERT INTO transaksi_keluar 
                          (id_order, nama_kain, tgl_potong, jumlah_potong_yard) 
                          VALUES (?, ?, ?, ?)";
    
    $stmt_keluar = mysqli_prepare($koneksi, $sql_insert_keluar);
    // Tipe data: sssd (s:id_order, s:nama_kain, s:tgl_potong, d:jumlah_potong_yard)
    mysqli_stmt_bind_param($stmt_keluar, "sssd", 
        $tujuan_produksi, $nama_kain, $tgl_keluar, $qty_keluar);
    
    if (!mysqli_stmt_execute($stmt_keluar)) {
        throw new Exception("Gagal mencatat histori pemakaian ke transaksi_keluar: " . mysqli_stmt_error($stmt_keluar));
    }
    mysqli_stmt_close($stmt_keluar);
    
    // Commit Transaksi jika semua berhasil
    mysqli_commit($koneksi);
    $_SESSION['status_message'] = "Pemakaian " . number_format($qty_keluar, 2) . " $satuan_display kain '$nama_kain' untuk $tujuan_produksi berhasil dicatat!";
    $_SESSION['status_type'] = "success";

} catch (Exception $e) {
    // Rollback jika terjadi kesalahan
    mysqli_rollback($koneksi);
    $_SESSION['status_message'] = "Kesalahan Transaksi Pemakaian Kain: " . $e->getMessage();
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);
header("Location: " . $redirect_location);
exit;
?>