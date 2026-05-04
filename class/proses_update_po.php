<?php
// LOKASI FILE: /flower/class/proses_update_po.php

session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_po.php");
    exit;
}

$id_order = (int) $_POST['id_order'];
$status_order = $_POST['status_order'];

// Data Produksi
$nama_editor = trim($_POST['nama_editor'] ?? '');
$operator_print = trim($_POST['operator_print'] ?? '');
$operator_press = trim($_POST['operator_press'] ?? '');
$jenis_mesin = trim($_POST['jenis_mesin'] ?? '');
$keterangan_tambahan = trim($_POST['keterangan_tambahan'] ?? '');

$success = false;

// 1. Ambil data PO lama (khususnya untuk cek status dan kebutuhan stok)
// *Menggunakan query yang sama untuk cek dan mengambil data lama*
$sql_po_old = "SELECT id_po_unik, status_order, stok_terpotong, nama_kain, kebutuhan_panjang, satuan_panjang 
             FROM orders 
             WHERE id_order = ?";
$stmt_po_old = mysqli_prepare($koneksi, $sql_po_old);
mysqli_stmt_bind_param($stmt_po_old, "i", $id_order);
mysqli_stmt_execute($stmt_po_old);
$result_po_old = mysqli_stmt_get_result($stmt_po_old);
$po_old = mysqli_fetch_assoc($result_po_old);
mysqli_stmt_close($stmt_po_old);

if (!$po_old) {
    $_SESSION['status_message'] = "PO tidak ditemukan.";
    $_SESSION['status_type'] = "danger";
    header("Location: ../hal/tambah_po.php");
    exit;
}

// =============================================================
// LOGIKA PENGURANGAN STOK (DITUJUKAN KE TABEL SALDO TUNGGAL: stok_master)
// =============================================================
$message_stok = "";

// Status baru yang di-submit dari form
$status_order = $_POST['status_order']; 

// Kondisi: Status BARU adalah 'Selesai' DAN stok BELUM pernah terpotong (stok_terpotong = 0)
if ($status_order == 'Selesai' && $po_old['stok_terpotong'] == 0) {
    
    $kebutuhan = $po_old['kebutuhan_panjang'];
    $nama_kain = $po_old['nama_kain'];

    $yard_to_subtract = $kebutuhan;
    if ($po_old['satuan_panjang'] == 'Meter') {
        $yard_to_subtract = $kebutuhan * 1.09361; 
    }
    
    // ----------------------------------------------------------------
    // LANGKAH 1: Cek Stok Total di stok_master
    // ----------------------------------------------------------------
    $sql_check_total = "SELECT stok_saat_ini FROM stok_master WHERE nama_kain = ?";
    $stmt_check_total = mysqli_prepare($koneksi, $sql_check_total);
    mysqli_stmt_bind_param($stmt_check_total, "s", $nama_kain);
    mysqli_stmt_execute($stmt_check_total);
    $result_check_total = mysqli_stmt_get_result($stmt_check_total);
    $data_stok = mysqli_fetch_assoc($result_check_total);
    mysqli_stmt_close($stmt_check_total);

    $total_stok = $data_stok ? $data_stok['stok_saat_ini'] : 0;
    
    if ($total_stok >= $yard_to_subtract) {
        
        // ----------------------------------------------------------------
        // LANGKAH 2A: Kurangi Saldo Total di stok_master
        // ----------------------------------------------------------------
        $sql_update_stok = "UPDATE stok_master 
                            SET stok_saat_ini = stok_saat_ini - ? 
                            WHERE nama_kain = ?"; 
        
        $stmt_stok = mysqli_prepare($koneksi, $sql_update_stok);
        mysqli_stmt_bind_param($stmt_stok, "ds", $yard_to_subtract, $nama_kain);
        
        if (mysqli_stmt_execute($stmt_stok) && mysqli_stmt_affected_rows($stmt_stok) > 0) {
            
            // ----------------------------------------------------------------
            // LANGKAH 2B: Catat Pemakaian di tabel transaksi_keluar (Tracking)
            // ----------------------------------------------------------------
            $sql_log = "INSERT INTO transaksi_keluar (id_order, nama_kain, tgl_potong, jumlah_potong_yard)
                        VALUES (?, ?, NOW(), ?)";
            $stmt_log = mysqli_prepare($koneksi, $sql_log);
            // NOW() akan otomatis mengisi tanggal dan waktu saat ini.
            mysqli_stmt_bind_param($stmt_log, "isd", $id_order, $nama_kain, $yard_to_subtract);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);
            
            // Tandai PO ini sebagai 'stok_terpotong' = 1 di tabel 'orders'
            $sql_mark = "UPDATE orders SET stok_terpotong = 1 WHERE id_order = ?";
            $stmt_mark = mysqli_prepare($koneksi, $sql_mark);
            mysqli_stmt_bind_param($stmt_mark, "i", $id_order);
            mysqli_stmt_execute($stmt_mark);
            mysqli_stmt_close($stmt_mark);

            $message_stok = "Stok kain **{$nama_kain}** di saldo aktual dikurangi **" . number_format($yard_to_subtract, 2) . " Yard** (berhasil dipotong dan dicatat).";
            
        } else {
             $message_stok = "GAGAL PEMOTONGAN! Nama kain '{$nama_kain}' tidak ditemukan di tabel saldo atau stok gagal diupdate.";
             $status_order = $po_old['status_order']; // Kembalikan status
        }
        mysqli_stmt_close($stmt_stok);
        
    } else {
        $message_stok = "GAGAL! Stok **{$nama_kain}** tidak mencukupi (**" . number_format($total_stok, 2) . " Y** tersedia). Stok tidak berkurang.";
        $status_order = $po_old['status_order']; // Kembalikan status
    }

} elseif ($status_order == 'Selesai' && $po_old['stok_terpotong'] == 1) {
    $message_stok = "Stok kain telah dipotong sebelumnya untuk PO ini.";
}

// =============================================================
// 3. UPDATE DATA PO UTAMA
// =============================================================
// KOREKSI 4: Hapus duplikasi pengambilan data PO lama (baris 50-57) dan gunakan $po_old['id_po_unik']
// dari pengambilan data yang pertama.

$sql_update = "UPDATE orders 
                SET status_order = ?, nama_editor = ?, operator_print = ?, 
                    operator_press = ?, jenis_mesin = ?, keterangan_tambahan = ?
                WHERE id_order = ?";
                
$stmt_update = mysqli_prepare($koneksi, $sql_update);
// Tipe data: s, s, s, s, s, s, i
mysqli_stmt_bind_param($stmt_update, "ssssssi", 
    $status_order, $nama_editor, $operator_print, 
    $operator_press, $jenis_mesin, $keterangan_tambahan, $id_order
);

if (mysqli_stmt_execute($stmt_update)) {
    $_SESSION['status_message'] = "Detail Purchase Order **" . $po_old['id_po_unik'] . "** berhasil diperbarui. " . $message_stok;
    $_SESSION['status_type'] = "success";
    $success = true;
} else {
    $_SESSION['status_message'] = "Gagal memperbarui detail PO. Error: " . mysqli_error($koneksi);
    $_SESSION['status_type'] = "danger";
}
mysqli_stmt_close($stmt_update);
mysqli_close($koneksi);

header("Location: ../hal/detail_po.php?id=" . $id_order);
exit;