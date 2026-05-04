<?php
// LOKASI FILE: /flower/class/export_mutasi_pdf.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

// --- DEBUGGING PATH DOMPDF ---
$autoload_path = '../vendor/autoload.php';

if (!file_exists($autoload_path)) {
    die("<h1>ERROR DOMPDF (404)</h1><p>File autoload Dompdf tidak ditemukan. Harap pastikan folder /flower/vendor/ terinstal dan path: " . realpath($autoload_path) . " sudah benar.</p>");
}

// =========================================================================
// ************ AKTIVASI DOMPDF ********************************************
// =========================================================================
require_once $autoload_path; 
use Dompdf\Dompdf;
use Dompdf\Options;
// =========================================================================

// Include koneksi database (Pastikan path benar)
include 'koneksi.php'; 

if (!isset($koneksi) || $koneksi === false || mysqli_connect_error()) {
    die("ERROR: Gagal terkoneksi ke database. Pesan: " . mysqli_connect_error());
}

// --- 1. Ambil Filter dari URL (GET parameters) ---
$filter_tgl_awal = $_GET['tgl_awal'] ?? '';
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? '';
$filter_nama_kain = $_GET['nama_kain'] ?? '';
$filter_tipe = $_GET['tipe'] ?? 'all'; 

// --- 2. Bangun Klausa WHERE untuk Subquery (Nama Kain) ---
$sub_where_kain = "";
if (!empty($filter_nama_kain) && $filter_nama_kain !== 'all') {
    // Gunakan fungsi pencegahan SQL Injection
    $safe_nama_kain = mysqli_real_escape_string($koneksi, $filter_nama_kain);
    $sub_where_kain = " WHERE nama_kain = '{$safe_nama_kain}'";
}

// --- 3. Query UNION dan Nested Query (Logika SQL yang sama dengan data_mutasi_kain.php) ---
$sql_parts = [];

// Transaksi MASUK
if ($filter_tipe === 'all' || strtoupper($filter_tipe) === 'MASUK') {
    $sql_masuk = "
        SELECT 
            CAST(id_roll AS CHAR) AS id_transaksi, 
            nama_kain, 
            tgl_diterima AS tgl_transaksi,
            panjang_yard_awal AS jumlah_yard, 
            (panjang_yard_awal * 0.9144) AS panjang_meter, 
            'MASUK' AS tipe_transaksi,
            CONCAT('Roll ID: ', id_roll, ' (', keterangan, ')') AS keterangan_final,
            lebar_kain
        FROM transaksi_masuk
        " . $sub_where_kain; 
    $sql_parts[] = $sql_masuk;
}

// Transaksi KELUAR
if ($filter_tipe === 'all' || strtoupper($filter_tipe) === 'KELUAR') {
    $sql_keluar = "
        SELECT 
            CAST(id_keluar AS CHAR) AS id_transaksi,  
            nama_kain, 
            tgl_potong AS tgl_transaksi, 
            (jumlah_potong_yard * -1) AS jumlah_yard, 
            (jumlah_potong_yard * -0.9144) AS panjang_meter, 
            'KELUAR' AS tipe_transaksi,
            CONCAT('PO/Order: ', id_order) AS keterangan_final,
            NULL AS lebar_kain 
        FROM transaksi_keluar
        " . $sub_where_kain;
    $sql_parts[] = $sql_keluar;
}

if (empty($sql_parts)) {
    // Query kosong jika tidak ada tipe yang dipilih
    $sql_union = "SELECT 1 AS id_transaksi, '' AS nama_kain, NULL AS tgl_transaksi, 0 AS jumlah_yard, 0 AS panjang_meter, '' AS tipe_transaksi, '' AS keterangan_final, 0 AS lebar_kain WHERE 1 = 0";
} else {
    $sql_union_base = implode(" UNION ALL ", $sql_parts);
    $where_date_clauses = [];
    $where_date_sql = "";
    
    // Terapkan filter tanggal yang aman
    $safe_tgl_awal = mysqli_real_escape_string($koneksi, $filter_tgl_awal);
    $safe_tgl_akhir = mysqli_real_escape_string($koneksi, $filter_tgl_akhir);

    if (!empty($safe_tgl_awal) && !empty($safe_tgl_akhir) && strtotime($safe_tgl_awal) && strtotime($safe_tgl_akhir)) {
        $where_date_clauses[] = "tgl_transaksi BETWEEN '{$safe_tgl_awal}' AND '{$safe_tgl_akhir}'";
    } elseif (!empty($safe_tgl_awal)) {
        $where_date_clauses[] = "tgl_transaksi >= '{$safe_tgl_awal}'";
    } elseif (!empty($safe_tgl_akhir)) {
        $where_date_clauses[] = "tgl_transaksi <= '{$safe_tgl_akhir}'";
    }
    
    if (count($where_date_clauses) > 0) {
        $where_date_sql = " WHERE " . implode(" AND ", $where_date_clauses);
    }
    
    $sql_mutasi_filtered = "
        SELECT Mutasi.*
        FROM (
            {$sql_union_base}
        ) AS Mutasi
        " . $where_date_sql;

    $sql_union = "
        SELECT Final.* FROM (
            {$sql_mutasi_filtered}
        ) AS Final
        ORDER BY tgl_transaksi DESC, id_transaksi DESC
    ";
}

$result_data = mysqli_query($koneksi, $sql_union);

if (!$result_data) {
    die("Query Database Error: " . mysqli_error($koneksi));
}

// --- 4. GENERATE KONTEN HTML ---
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Mutasi Stok Kain</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 12pt; text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 8px 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; font-size: 9pt; }
        td { font-size: 8pt; }
        .masuk { background-color: #e6ffe6; }
        .keluar { background-color: #ffe6e6; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .filter-info { margin-bottom: 20px; font-size: 9pt; border: 1px solid #ccc; padding: 10px; }
    </style>
</head>
<body>
';

$html .= '<h1>Laporan Histori Mutasi Stok Kain</h1>';
$html .= '<h2>Flowerindo System - Dibuat pada: ' . date('d F Y H:i:s') . '</h2>';

// Tampilkan Filter yang Diterapkan
$html .= '<div class="filter-info">';
$html .= '<strong>Filter:</strong><br>';
$html .= 'Periode: ' . (empty($filter_tgl_awal) ? 'Awal' : date('d/m/Y', strtotime($filter_tgl_awal))) . ' s/d ' . (empty($filter_tgl_akhir) ? 'Sekarang' : date('d/m/Y', strtotime($filter_tgl_akhir))) . '<br>';
$html .= 'Jenis Kain: ' . (empty($filter_nama_kain) || $filter_nama_kain === 'all' ? 'Semua Kain' : htmlspecialchars($filter_nama_kain)) . '<br>';
$html .= 'Tipe Mutasi: ' . (strtoupper($filter_tipe) === 'ALL' ? 'MASUK & KELUAR' : strtoupper($filter_tipe)) . '<br>';
$html .= '</div>';


$html .= '<table>
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 12%;">Tanggal</th>
            <th style="width: 15%;">Kain</th>
            <th style="width: 8%;" class="text-center">Lebar</th>
            <th style="width: 10%;" class="text-center">Tipe</th>
            <th style="width: 15%;" class="text-right">Jumlah (Yard)</th>
            <th style="width: 15%;" class="text-right">Jumlah (Meter)</th>
            <th style="width: 20%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
$total_yard = 0;

if (mysqli_num_rows($result_data) > 0) {
    while($row = mysqli_fetch_assoc($result_data)) {
        $is_keluar = ($row['tipe_transaksi'] === 'KELUAR');
        $row_class = $is_keluar ? 'keluar' : 'masuk';
        $total_yard += $row['jumlah_yard']; // Hitung total mutasi
        
        $jumlah_yard_formatted = number_format($row['jumlah_yard'], 2, ',', '.');
        $panjang_meter_formatted = number_format($row['panjang_meter'], 2, ',', '.');
        $lebar_kain_formatted = ($row['lebar_kain'] !== NULL) ? number_format($row['lebar_kain'], 2, ',', '.') : '-';

        $html .= '<tr class="' . $row_class . '">
            <td class="text-center">' . $no++ . '</td>
            <td>' . date('d/m/Y', strtotime($row['tgl_transaksi'])) . '</td>
            <td>' . htmlspecialchars($row['nama_kain']) . '</td>
            <td class="text-center">' . $lebar_kain_formatted . '</td>
            <td class="text-center">' . $row['tipe_transaksi'] . '</td>
            <td class="text-right">' . $jumlah_yard_formatted . '</td>
            <td class="text-right">' . $panjang_meter_formatted . '</td>
            <td>' . htmlspecialchars($row['keterangan_final']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="8" class="text-center">Tidak ada catatan mutasi kain yang ditemukan.</td></tr>';
}

// Baris Total Mutasi
$html .= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-right" style="font-weight: bold; background-color: #f2f2f2;">TOTAL AKUMULASI NETTO (YARD)</td>
            <td class="text-right" style="font-weight: bold; background-color: #f2f2f2; color: ' . ($total_yard < 0 ? '#dc3545' : '#198754') . ';">' . number_format($total_yard, 2, ',', '.') . ' Y</td>
            <td colspan="2" style="background-color: #f2f2f2;"></td>
        </tr>
    </tfoot>
</table>';

// CATATAN: Hapus baris footer HTML ini karena penomoran halaman
// akan ditangani oleh kode Dompdf di bawah secara terpisah (lebih stabil)
// $html .= '<div class="footer">Halaman <span style="font-style: italic;">{PAGE_NUM}</span> dari <span style="font-style: italic;">{PAGE_COUNT}</span></div>';

$html .= '</body></html>';

mysqli_close($koneksi);


// =========================================================================
// ************ PROSES RENDERING DOMPDF AKTIF ********************************
// =========================================================================

try {
    // Nonaktifkan output buffering yang mungkin sedang berjalan,
    // Ini penting agar Dompdf bisa mengirim header PDF.
    if (ob_get_contents()) ob_end_clean();

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    $options->set('defaultFont', 'sans-serif'); 

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape'); // Menggunakan orientasi landscape untuk tabel lebar
    $dompdf->render();

    // Menambahkan penomoran halaman
    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->get_font("sans-serif");
    // Posisi penomoran halaman di kanan bawah (sesuaikan koordinat jika perlu)
    $canvas->page_text(700, 560, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, 8, array(0,0,0));

    // Nama file download
    $filename = 'Mutasi_Kain_' . date('Ymd_His') . '.pdf';

    // Stream PDF ke browser sebagai file yang dapat diunduh (Attachment => true)
    $dompdf->stream($filename, ["Attachment" => true]);

} catch (\Exception $e) {
    // Tangkap error Dompdf dan tampilkan
    header("Content-Type: text/html");
    die("<h1>DOMPDF RENDERING ERROR</h1><p>Terjadi kesalahan saat memproses PDF: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Hapus baris 'echo $html;' agar tidak ada output mentah
// echo $html; 
exit; 
?>