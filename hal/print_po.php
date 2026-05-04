<?php
// LOKASI FILE: /flower/hal/print_po.php

session_start();
// ASUMSI: File koneksi.php berada di lokasi ini.
include '../class/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); exit;
}

$order_ids = [];

// 1. Ambil ID dari URL (untuk Batch Print atau Single Print)
if (isset($_GET['ids'])) {
    // Ambil multiple ID (Batch Print)
    $ids_raw = trim($_GET['ids']);
    // Bersihkan, pisahkan string berdasarkan koma, dan pastikan nilainya integer
    $order_ids = array_map('intval', explode(',', $ids_raw));
} elseif (isset($_GET['id'])) {
    // Ambil single ID (Print Satuan)
    $order_ids[] = (int)$_GET['id'];
}

// Hapus ID yang nol atau tidak valid dan pastikan array tidak kosong
$order_ids = array_filter($order_ids);
if (empty($order_ids)) {
    die("ID Purchase Order tidak ditemukan.");
}

// 2. Persiapan Query SQL IN
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));
$types = str_repeat('i', count($order_ids)); // Tentukan tipe data sebagai integer ('i')

$sql_data = "
    SELECT 
        o.id_po_unik, o.id_print, o.nama_kain, o.kebutuhan_panjang, o.satuan_panjang, 
        o.nama_file_desain, o.foto_desain, o.keterangan_tambahan, o.tgl_po_dibuat,
        o.nama_editor, o.operator_print, o.operator_press, o.jenis_mesin, o.metode_print,
        o.keterangan_po /* Menggunakan kolom 'keterangan_po' sesuai struktur final */
    FROM orders o
    WHERE o.id_order IN ($placeholders)
    ORDER BY o.id_order ASC
";

$stmt = mysqli_prepare($koneksi, $sql_data);
if ($stmt === false) {
    die("Error persiapan query SQL: " . mysqli_error($koneksi));
}

// Bind parameter (multiple ID)
// Menggunakan sintaks modern (PHP 5.6+) atau fallback
if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
    mysqli_stmt_bind_param($stmt, $types, ...$order_ids);
} else {
    // Fallback untuk PHP lama
    $bind_params = array_merge([$stmt, $types], $order_ids);
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$all_data_po = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_stmt_close($stmt);
mysqli_close($koneksi);

if (empty($all_data_po)) {
    die("Data Purchase Order tidak ditemukan.");
}

// ==========================================================
// TAMPILAN PRINT DENGAN LOOPING HTML
// ==========================================================
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Order Batch (<?php echo count($all_data_po); ?> Dokumen)</title>
    
    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            margin: 0; 
            padding: 0; 
            color: #000;
            font-size: 11pt;
        }
        .page-break {
            page-break-after: always; /* Pemisah halaman untuk setiap PO */
        }
        .print-container {
            width: 760px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18pt;
            margin: 0;
        }
        .info-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .info-box td {
            padding: 2px 5px;
            vertical-align: top;
        }
        .info-box .label {
            width: 150px;
            font-weight: bold;
        }
        .rincian table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .rincian th, .rincian td {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
        }
        .rincian th {
            background-color: #f0f0f0;
        }
        .ttd-box {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            text-align: center;
            font-size: 10pt;
        }
        .ttd-col {
            width: 30%;
        }
        .ttd-spacer {
            height: 60px;
            border-bottom: 1px solid #000;
            margin: 5px 0 0 0;
        }
        .photo-container {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            width: 150px;
            height: 150px;
            float: right;
            margin-left: 20px;
        }
        .photo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Hanya cetak */
        @media print {
            .print-container { border: none; margin: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="text-end no-print" style="text-align: right; margin-bottom: 15px;">
    <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">Cetak Dokumen</button>
    <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
</div>

<?php
// Loop untuk mencetak setiap data PO
foreach ($all_data_po as $data_po) {
    // --- PENGAMBILAN DATA DALAM LOOP (Konsisten) ---
    $tgl_dibuat = date('Y-m-d H:i', strtotime($data_po['tgl_po_dibuat']));
    $id_po_unik = htmlspecialchars($data_po['id_po_unik'] ?? '-');
    $printing_number = htmlspecialchars($data_po['id_print'] ?? 'BELUM DIINPUT');
    $nama_kain = htmlspecialchars($data_po['nama_kain'] ?? '-');
    $metode_print = htmlspecialchars($data_po['metode_print'] ?? '-');
    $jenis_mesin = htmlspecialchars($data_po['jenis_mesin'] ?? '-');

    $kebutuhan = number_format($data_po['kebutuhan_panjang'], 2);
    $satuan = htmlspecialchars($data_po['satuan_panjang'] ?? 'Unit');

    $nama_file_desain = htmlspecialchars($data_po['nama_file_desain'] ?? '-');

    // Data Keterangan: Mengambil dari kolom 'keterangan_po'
    $keterangan_po = htmlspecialchars($data_po['keterangan_po'] ?? '-'); 

    // Data Keterangan Tambahan: Menggunakan 'keterangan_tambahan'
    $keterangan_tambahan = htmlspecialchars($data_po['keterangan_tambahan'] ?? '-'); 

    // Logika Fallback
    if ($keterangan_po === '-') {
        $keterangan_po = $keterangan_tambahan;
        $keterangan_tambahan = '-';
    }

    $nama_editor = htmlspecialchars($data_po['nama_editor'] ?? 'Belum Konfirmasi');
    $operator_print = htmlspecialchars($data_po['operator_print'] ?? 'Belum Konfirmasi');
    $operator_press = htmlspecialchars($data_po['operator_press'] ?? 'Belum Konfirmasi');

    $foto_desain_path = !empty($data_po['foto_desain']) ? '../uploads/desain/' . $data_po['foto_desain'] : '';
    
    // ==========================================================
    // TAMPILAN PO PER ITEM (Konsisten dengan halaman sebelumnya)
    // ==========================================================
    ?>
    <div class="print-container">
        
        <div class="header">
            <h1>ORDER PRINTING</h1>
        </div>

        <div style="overflow: hidden;">
            <div class="photo-container">
                <p style="margin: 0; font-weight: bold; font-size: 9pt;">FOTO DESAIN</p>
                <?php if (!empty($foto_desain_path) && file_exists($foto_desain_path)): ?>
                    <img src="<?php echo $foto_desain_path; ?>" alt="Foto Desain">
                <?php else: ?>
                    <div style="font-size: 9pt; color: #888; padding-top: 20px;">[Foto Tidak Tersedia]</div>
                <?php endif; ?>
            </div>

            <div class="info-box" style="margin-right: 175px;">
                <table style="width: 100%;">
                    <tr>
                        <td class="label">ID PO</td>
                        <td>: <?php echo $id_po_unik; ?></td>
                        <td class="label">Tanggal Order</td>
                        <td>: <?php echo $tgl_dibuat; ?></td>
                    </tr>
                    <tr>
                        <td class="label">WO No.</td>
                        <td>: <?php echo $printing_number; ?></td>
                        <td class="label">Desain (SPU/SKU)</td>
                        <td>: <?php echo $nama_file_desain; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Kain</td>
                        <td>: <?php echo $nama_kain; ?></td>
                        <td class="label">Metode Cetak</td>
                        <td>: <?php echo $metode_print; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Total Kebutuhan</td>
                        <td>: <?php echo $kebutuhan; ?> <?php echo $satuan; ?></td>
                        <td class="label">Mesin Produksi</td>
                        <td>: <?php echo $jenis_mesin; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <h4 style="margin-top: 15px; margin-bottom: 10px; font-size: 12pt; border-bottom: 1px solid #000;">KONTROL & TANDA TANGAN PROSES</h4>
        
        <div class="rincian">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">TAHAP KERJA</th>
                        <th style="width: 25%;">OPERATOR BERTANGGUNG JABWAB</th>
                        <th style="width: 25%;">TANDA TANGAN</th>
                        <th style="width: 25%;">TGL. / JAM SELESAI</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>DESAIN & RIP (Editor)</td>
                        <td><?php echo $nama_editor; ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>PROSES PRINTING</td>
                        <td><?php echo $operator_print; ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>HEAT TRANSFER / PRESS</td>
                        <td><?php echo $operator_press; ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>FINAL CHECK & GUDANG</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 style="margin-top: 15px; margin-bottom: 5px; font-size: 12pt; border-bottom: 1px solid #000;">KETERANGAN DAN INSTRUKSI</h4>
        <div class="info-box">
            <p style="margin: 0; font-size: 10pt;">
                Keterangan PO Umum: <?php echo $keterangan_po; ?><br>
                Instruksi Tambahan: <?php echo $keterangan_tambahan; ?>
            </p>
        </div>
        
        <div class="ttd-box">
            <div class="ttd-col">
                <p style="margin-bottom: 5px;">Diterbitkan Oleh (Admin PO)</p>
                <div class="ttd-spacer"></div>
                <p>( ................................. )</p>
            </div>
            <div class="ttd-col">
                <p style="margin-bottom: 5px;">Disetujui Oleh (QC / Supervisor)</p>
                <div class="ttd-spacer"></div>
                <p>( ................................. )</p>
            </div>
            <div class="ttd-col">
                <p style="margin-bottom: 5px;">Diterima Oleh (PIC Produksi)</p>
                <div class="ttd-spacer"></div>
                <p>( ................................. )</p>
            </div>
        </div>
        <p style="margin-top: 40px; font-size: 9pt; text-align: center;">*Work Order ini harus diisi dan ditandatangani pada setiap tahapan produksi. Simpan untuk arsip dan kontrol mutu.</p>

    </div>
    
    <?php
    // Tambahkan pemisah halaman jika ini bukan PO terakhir dalam loop
    if (next($all_data_po)) {
        echo '<div class="page-break"></div>';
    }
}
?>

</body>
</html>