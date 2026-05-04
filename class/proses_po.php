<?php
// LOKASI FILE: /flower/class/proses_po.php

session_start();
// Pastikan koneksi.php sudah disiapkan dengan variabel $koneksi
include 'koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../hal/tambah_po.php");
    exit;
}

// ===============================================
// AMBIL DAN SANITASI DATA INPUT
// ===============================================

// Data yang WAJIB
$id_po_unik = mysqli_real_escape_string($koneksi, $_POST['id_po_unik']);
$tgl_po_dibuat = mysqli_real_escape_string($koneksi, $_POST['tgl_po_dibuat']);
$id_client = mysqli_real_escape_string($koneksi, $_POST['id_client']);
$nama_kain = mysqli_real_escape_string($koneksi, $_POST['nama_kain']);
$nama_file_desain = mysqli_real_escape_string($koneksi, $_POST['nama_file_desain']);
$kebutuhan_panjang = mysqli_real_escape_string($koneksi, $_POST['kebutuhan_panjang']);
$satuan_panjang = mysqli_real_escape_string($koneksi, $_POST['satuan_panjang']);

// Data OPSIONAL
$metode_print = mysqli_real_escape_string($koneksi, $_POST['metode_print'] ?? '');
$keterangan_po = mysqli_real_escape_string($koneksi, $_POST['keterangan_po'] ?? '');
$nama_editor = mysqli_real_escape_string($koneksi, $_POST['nama_editor'] ?? '');
$operator_print = mysqli_real_escape_string($koneksi, $_POST['operator_print'] ?? '');
$operator_press = mysqli_real_escape_string($koneksi, $_POST['operator_press'] ?? '');
$jenis_mesin = mysqli_real_escape_string($koneksi, $_POST['jenis_mesin'] ?? '');
$keterangan_tambahan = mysqli_real_escape_string($koneksi, $_POST['keterangan_tambahan'] ?? '');

$foto_desain_filename = ''; // Untuk kolom 'foto_desain' di database

// Logika Upload Foto
if (isset($_FILES['foto_desain']) && $_FILES['foto_desain']['error'] == 0) {
    $target_dir = "../uploads/desain/"; 
    // Pastikan folder ini sudah dibuat: C:\xampp\htdocs\flower\uploads\desain\
    $file_extension = pathinfo($_FILES["foto_desain"]["name"], PATHINFO_EXTENSION);
    $new_filename = $id_po_unik . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["foto_desain"]["tmp_name"], $target_file)) {
        $foto_desain_filename = $new_filename;
    }
}


// ===============================================
// QUERY INSERT DATA KE TABEL 'orders'
// ===============================================
// PERBAIKAN FINAL NAMA KOLOM: 'status_order' digunakan
// ===============================================

$sql_insert = "
    INSERT INTO orders 
    (
        id_po_unik, id_client, nama_kain, nama_file_desain, 
        kebutuhan_panjang, satuan_panjang, metode_print, 
        foto_desain, keterangan_po, tgl_po_dibuat,
        status_order, nama_editor, operator_print, operator_press, jenis_mesin, keterangan_tambahan, 
        stok_terpotong
    ) 
    VALUES 
    (
        '$id_po_unik', '$id_client', '$nama_kain', '$nama_file_desain', 
        '$kebutuhan_panjang', '$satuan_panjang', '$metode_print', 
        '$foto_desain_filename', '$keterangan_po', '$tgl_po_dibuat',
        'Pending', '$nama_editor', '$operator_print', '$operator_press', '$jenis_mesin', '$keterangan_tambahan', 
        0
    )
";

if (mysqli_query($koneksi, $sql_insert)) {
    // Jika penyimpanan SUKSES: Redirect dengan status sukses dan ID PO
    header("Location: ../hal/tambah_po.php?status=sukses&po_id=" . urlencode($id_po_unik));
    exit;
} else {
    // Jika penyimpanan GAGAL: Redirect dengan status gagal
    // Jika ini gagal, ada masalah koneksi atau masalah tipe data.
    $error_msg = mysqli_error($koneksi);
    error_log("Gagal Insert PO: " . $error_msg); // Log error ke file log PHP
    header("Location: ../hal/tambah_po.php?status=gagal");
    exit;
}

mysqli_close($koneksi);
?>