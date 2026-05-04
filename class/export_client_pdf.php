<?php
// LOKASI FILE: /flower/class/export_client_pdf.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Akses ditolak."); 
}

// =================================================================
// PENTING: Panggil autoload Dompdf (Sesuaikan path jika perlu)
// Asumsi: Dompdf diinstal di folder vendor di root proyek
require '../vendor/autoload.php';
// =================================================================

use Dompdf\Dompdf;
use Dompdf\Options;

// Include koneksi database
include 'koneksi.php'; 

// 1. Ambil SEMUA data client
$sql = "SELECT id_client, nama_client, email_client, no_hp_client, tgl_dibuat FROM clients ORDER BY tgl_dibuat ASC";
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Error saat mengambil data: " . mysqli_error($koneksi));
}

// 2. Buat Konten HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Data Client</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h1 { text-align: center; margin-bottom: 20px; font-size: 16pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .footer { text-align: right; margin-top: 30px; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>LAPORAN DATA CLIENT FLOWERINDO</h1>
    <p>Tanggal Export: ' . date('d F Y H:i:s') . '</p>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Client</th>
                <th>Email</th>
                <th>Nomor HP</th>
                <th>Tgl Registrasi</th>
            </tr>
        </thead>
        <tbody>';

if (mysqli_num_rows($result) > 0) {
    $no = 1;
    while($row = mysqli_fetch_assoc($result)) {
        $html .= '
            <tr>
                <td>' . $no++ . '</td>
                <td>' . htmlspecialchars($row['nama_client']) . '</td>
                <td>' . htmlspecialchars($row['email_client']) . '</td>
                <td>' . htmlspecialchars($row['no_hp_client']) . '</td>
                <td>' . date('d M Y', strtotime($row['tgl_dibuat'])) . '</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" style="text-align: center;">Tidak ada data client.</td></tr>';
}

$html .= '
        </tbody>
    </table>
    <div class="footer">
        Dibuat oleh: ' . htmlspecialchars($_SESSION['username']) . '
    </div>
</body>
</html>';

mysqli_close($koneksi);

// 3. Inisialisasi Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// 4. Load HTML ke Dompdf
$dompdf->loadHtml($html);

// 5. Atur Ukuran Kertas dan Orientasi (A4, Portrait)
$dompdf->setPaper('A4', 'portrait');

// 6. Render HTML menjadi PDF
$dompdf->render();

// 7. Output PDF (Download file)
$filename = "Data_Client_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);

exit;