<?php
// LOKASI FILE: /flower/class/export_kain_pdf.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Akses ditolak."); 
}

// =================================================================
// PENTING: Panggil Dompdf secara MANUAL
// PASTIKAN PATH INI SESUAI DENGAN LOKASI FOLDER DOMPDF ANDA
require '../vendor/autoload.php';
// =================================================================

use Dompdf\Dompdf;
use Dompdf\Options;

// Include koneksi database
include 'koneksi.php'; 

// --- START: LOGIKA FILTER DINAMIS ---
$filter_tgl_awal = $_GET['tgl_awal'] ?? '';
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? '';
$filter_nama_kain = $_GET['nama_kain'] ?? '';

$where_clauses = [];

// Buat deskripsi filter untuk ditampilkan di PDF
$filter_info = "Semua Data";

// 1. Filter Tanggal
if (!empty($filter_tgl_awal) && !empty($filter_tgl_akhir)) {
    if (strtotime($filter_tgl_awal) && strtotime($filter_tgl_akhir)) {
        $where_clauses[] = "tgl_diterima BETWEEN '$filter_tgl_awal' AND '$filter_tgl_akhir'";
        $filter_info = "Tanggal: " . date('d M Y', strtotime($filter_tgl_awal)) . " s/d " . date('d M Y', strtotime($filter_tgl_akhir));
    }
} elseif (!empty($filter_tgl_awal)) {
    $where_clauses[] = "tgl_diterima >= '$filter_tgl_awal'";
    $filter_info = "Dari Tanggal: " . date('d M Y', strtotime($filter_tgl_awal));
} elseif (!empty($filter_tgl_akhir)) {
    $where_clauses[] = "tgl_diterima <= '$filter_tgl_akhir'";
    $filter_info = "Sampai Tanggal: " . date('d M Y', strtotime($filter_tgl_akhir));
}

// 2. Filter Nama Kain
if (!empty($filter_nama_kain) && $filter_nama_kain !== 'all') {
    $safe_nama_kain = mysqli_real_escape_string($koneksi, $filter_nama_kain);
    $where_clauses[] = "nama_kain = '$safe_nama_kain'";
    
    // Perbarui filter info
    if ($filter_info === "Semua Data") {
        $filter_info = "Jenis Kain: " . htmlspecialchars($safe_nama_kain);
    } else {
        $filter_info .= " | Jenis Kain: " . htmlspecialchars($safe_nama_kain);
    }
}

// Gabungkan semua klausa WHERE
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
// --- END: LOGIKA FILTER DINAMIS ---


// 1. Ambil data penerimaan DENGAN FILTER
$sql = "SELECT id_roll, nama_kain, tgl_diterima, lebar_kain, panjang_yard_awal AS panjang_yard, 
               (panjang_yard_awal * 0.9144) AS panjang_meter 
         FROM transaksi_masuk 
         " . $where_sql . "
         ORDER BY tgl_diterima ASC";
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Error saat mengambil data: " . mysqli_error($koneksi));
}

// 2. Buat Konten HTML untuk PDF
$total_rows = mysqli_num_rows($result);

$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Bahan Baku Kain</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 16pt; }
        h2 { font-size: 12pt; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        p.info { text-align: center; font-size: 10pt; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .footer { text-align: right; margin-top: 30px; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>LAPORAN DATA PENERIMAAN BAHAN BAKU KAIN</h1>
    <p class="info">Tanggal Export: ' . date('d F Y H:i:s') . '</p>
    <p class="info">Filter Aktif: <strong>' . $filter_info . '</strong> (Total ' . $total_rows . ' Catatan)</p>
    
    <h2>Catatan Penerimaan (Per Roll/Batch)</h2>
    <table>
        <thead>
            <tr>
                <th>Roll ID</th> <th>Nama Kain</th>
                <th>Tgl Diterima</th>
                <th>Lebar (Unit)</th>
                <th>Panjang (Yard)</th>
                <th>Panjang (Meter)</th>
            </tr>
        </thead>
        <tbody>';

if ($total_rows > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($row['id_roll']) . '</td>
                <td>' . htmlspecialchars($row['nama_kain']) . '</td>
                <td>' . date('d M Y', strtotime($row['tgl_diterima'])) . '</td>
                <td>' . number_format($row['lebar_kain'], 2) . '</td>
                <td>' . number_format($row['panjang_yard'], 2) . ' Y</td>
                <td>' . number_format($row['panjang_meter'], 2) . ' M</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" style="text-align: center;">Tidak ada data penerimaan kain sesuai filter.</td></tr>';
}
$html .= '</tbody></table>';


// 3. Tambahkan Stok Akumulasi (Tidak terpengaruh filter, tetap menampilkan semua stok)
$sql_stok = "SELECT nama_kain, stok_saat_ini AS total_stok 
             FROM stok_master 
             ORDER BY nama_kain ASC";
$result_stok = mysqli_query($koneksi, $sql_stok);

$html .= '
    <h2>Total Stok Akumulasi Saat Ini (Yard)</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Kain</th>
                <th>Total Stok (Yard)</th>
            </tr>
        </thead>
        <tbody>';

if (mysqli_num_rows($result_stok) > 0) {
    while($stok = mysqli_fetch_assoc($result_stok)) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($stok['nama_kain']) . '</td>
                <td>' . number_format($stok['total_stok'], 2) . ' Y</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="2" style="text-align: center;">Tidak ada data stok.</td></tr>';
}
$html .= '</tbody></table>';

$html .= '
    <div class="footer">
        Dibuat oleh: ' . htmlspecialchars($_SESSION['username']) . '
    </div>
</body>
</html>';

mysqli_close($koneksi);

// 4. Inisialisasi dan Render Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); 

$dompdf->render();

// 5. Output PDF (Download file)
$filename = "Laporan_Bahan_Baku_Kain_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);

exit;