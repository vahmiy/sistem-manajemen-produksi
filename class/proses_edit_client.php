<?php
// LOKASI FILE: /flower/class/proses_edit_client.php

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../hal/tambah_client.php"); 
    exit;
}

include 'koneksi.php'; 

// 1. Ambil dan Bersihkan Data
$id_client = (int) $_POST['id_client'];
$nama = trim($_POST['nama']);
$email = trim($_POST['email']);
$no_hp = trim($_POST['no_hp']);

// 2. Validasi (Sama seperti proses_client, namun kita fokus pada UPDATE)
$error_msg = "";
if (empty($id_client) || empty($nama) || empty($email) || empty($no_hp)) {
    $error_msg = "Semua kolom wajib diisi, termasuk ID client yang hilang.";
} 
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = "Format email tidak valid.";
} else {
    $no_hp_bersih = preg_replace('/[^0-9]/', '', $no_hp);
    if (!ctype_digit($no_hp_bersih) || strlen($no_hp_bersih) < 9 || strlen($no_hp_bersih) > 15) {
        $error_msg = "Nomor HP tidak valid.";
    }
}

// Jika ada error, simpan pesan dan redirect ke halaman edit
if ($error_msg) {
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    // Redirect kembali ke halaman edit (penting agar ID tetap ada di URL)
    header("Location: ../hal/edit_client.php?id=" . $id_client); 
    exit;
}

$no_hp_final = $no_hp_bersih;

// 3. Persiapkan dan Eksekusi Prepared Statement (UPDATE)
$sql = "UPDATE clients SET nama_client = ?, email_client = ?, no_hp_client = ? WHERE id_client = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // Tipe data: 3 string (s), 1 integer (i)
    mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $no_hp_final, $id_client);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['status_message'] = "Data client **" . htmlspecialchars($nama) . "** berhasil diperbarui!";
        $_SESSION['status_type'] = "success";
    } else {
        $error_db = "Gagal memperbarui client. ";
        if (mysqli_errno($koneksi) == 1062) { // 1062 adalah kode error untuk Duplikat entry (Unique Constraint)
            $error_db .= "Email client sudah terdaftar pada client lain.";
        } else {
            $error_db .= "Terjadi kesalahan database: " . mysqli_error($koneksi);
        }
        $_SESSION['status_message'] = $error_db;
        $_SESSION['status_type'] = "danger";
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['status_message'] = "Error saat menyiapkan statement UPDATE.";
    $_SESSION['status_type'] = "danger";
}

mysqli_close($koneksi);

// FINAL REDIRECT ke halaman list utama
header("Location: ../hal/tambah_client.php");
exit;