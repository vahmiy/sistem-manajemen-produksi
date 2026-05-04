<?php
// LOKASI FILE: /flower/hal/data_mutasi_kain.php (KODE FINAL TERKOREKSI)

session_start();

// --- 1. KEAMANAN LOGIN CHECK ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Histori Mutasi Stok Kain (Masuk & Keluar)"; 
$username = htmlspecialchars($_SESSION['username']);

// Include koneksi database (Pastikan path benar)
include '../class/koneksi.php'; 

if (!isset($koneksi) || $koneksi === false) {
    die("ERROR: Gagal terkoneksi ke database. Pastikan file koneksi.php sudah benar.");
}

// --- 2. INISIALISASI & PEMERIKSAAN FILTER ---
$limit_per_halaman = 20;
$halaman_aktif = $_GET['halaman'] ?? 1;
$halaman_aktif = (int)$halaman_aktif;
if ($halaman_aktif < 1) {
    $halaman_aktif = 1;
}

$filter_tgl_awal = $_GET['tgl_awal'] ?? '';
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? '';
$filter_nama_kain = $_GET['nama_kain'] ?? '';
$filter_tipe = $_GET['tipe'] ?? 'all'; 

// Pembersihan string untuk keamanan SQL Injection
$nama_kain_safe = mysqli_real_escape_string($koneksi, $filter_nama_kain);
$tgl_awal_safe = mysqli_real_escape_string($koneksi, $filter_tgl_awal);
$tgl_akhir_safe = mysqli_real_escape_string($koneksi, $filter_tgl_akhir);

// 3. Bangun Klausa WHERE untuk Filter Nama Kain (Hanya untuk tabel dasar)
$sub_where_kain = "";
if (!empty($nama_kain_safe) && $nama_kain_safe !== 'all') {
    // Perhatikan: Filter ini hanya bekerja jika nama_kain ada di kedua tabel
    $sub_where_kain = " WHERE nama_kain = '{$nama_kain_safe}'";
}

// --- 4. LOGIKA QUERY UNION ALL ---
$sql_parts = [];

// A. Transaksi MASUK
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

// B. Transaksi KELUAR
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

// 5. Gabungkan dan Terapkan Filter Tanggal & Pagination
$total_catatan = 0;
$total_halaman = 1;
$offset = ($halaman_aktif - 1) * $limit_per_halaman; // Default offset

if (empty($sql_parts)) {
    // Jika tidak ada tipe yang dipilih, buat query kosong (0 catatan)
    $sql_union_base = "SELECT 1 AS id_transaksi, '' AS nama_kain, NULL AS tgl_transaksi, 0 AS jumlah_yard, 0 AS panjang_meter, '' AS tipe_transaksi, '' AS keterangan_final, 0 AS lebar_kain WHERE 1 = 0";
    $sql_union = $sql_union_base; // Query akhir adalah query kosong
} else {
    $sql_union_base = implode(" UNION ALL ", $sql_parts);
    $where_date_clauses = [];
    
    // Terapkan filter tanggal menggunakan variabel yang sudah di-escape
    if (!empty($tgl_awal_safe) && !empty($tgl_akhir_safe) && strtotime($tgl_awal_safe) && strtotime($tgl_akhir_safe)) {
        $where_date_clauses[] = "tgl_transaksi BETWEEN '{$tgl_awal_safe}' AND '{$tgl_akhir_safe}'";
    } elseif (!empty($tgl_awal_safe)) {
        $where_date_clauses[] = "tgl_transaksi >= '{$tgl_awal_safe}'";
    } elseif (!empty($tgl_akhir_safe)) {
        $where_date_clauses[] = "tgl_transaksi <= '{$tgl_akhir_safe}'";
    }
    
    $where_date_sql = (count($where_date_clauses) > 0) ? " WHERE " . implode(" AND ", $where_date_clauses) : "";
    
    // Query untuk MENGHITUNG TOTAL BARIS
    $sql_count = "
        SELECT COUNT(*) AS total
        FROM (
            {$sql_union_base}
        ) AS Mutasi_Base
        " . $where_date_sql;
        
    $result_count = mysqli_query($koneksi, $sql_count);
    if (!$result_count) {
        die("Query Count Database Error: " . mysqli_error($koneksi));
    }
    $row_count = mysqli_fetch_assoc($result_count);
    $total_catatan = $row_count['total']; // Total catatan setelah filter

    // Hitung ulang total halaman dan koreksi halaman aktif
    $total_halaman = ceil($total_catatan / $limit_per_halaman);
    
    // KOREKSI KRITIS: Sesuaikan halaman aktif jika melebihi batas atau jika 0 catatan
    if ($total_catatan > 0 && $halaman_aktif > $total_halaman) {
        $halaman_aktif = $total_halaman;
        $offset = ($halaman_aktif - 1) * $limit_per_halaman;
    } elseif ($total_catatan == 0) {
        $offset = 0;
    }
    
    // Sub-Query yang difilter (untuk diurutkan dan dipaginasi)
    $sql_mutasi_filtered = "
        SELECT Mutasi.*
        FROM (
            {$sql_union_base}
        ) AS Mutasi
        " . $where_date_sql;

    // Final Query: Terapkan ORDER BY, LIMIT, dan OFFSET
    $sql_union = "
        SELECT Final.* FROM (
            {$sql_mutasi_filtered}
        ) AS Final
        ORDER BY tgl_transaksi DESC, id_transaksi DESC
        LIMIT {$limit_per_halaman} OFFSET {$offset} 
    ";
}

$result_data = mysqli_query($koneksi, $sql_union);

if (!$result_data) {
    die("Query Mutasi Data Error: " . mysqli_error($koneksi));
}

// 6. Query untuk Filter dan Stok Master
$sql_unique_kain = "SELECT DISTINCT nama_kain FROM stok_master ORDER BY nama_kain ASC";
$result_unique_kain = mysqli_query($koneksi, $sql_unique_kain);

$sql_aktual = "SELECT nama_kain, stok_saat_ini AS total_netto FROM stok_master ORDER BY nama_kain ASC";
$result_aktual = mysqli_query($koneksi, $sql_aktual);

if (!$result_unique_kain || !$result_aktual) {
    die("Query Database Error (Stok Master/Filter): " . mysqli_error($koneksi));
}

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// Fungsi untuk membuat URL Query String baru (PENTING untuk Pagination!)
function build_pagination_url($koneksi, $halaman) {
    // Ambil semua parameter GET saat ini, kecuali 'halaman'
    $params = $_GET;
    $params['halaman'] = $halaman;
    
    // Map untuk memastikan semua parameter URL di-escape sebelum dimasukkan ke string query
    $query_string = http_build_query(array_map(function($v) use ($koneksi) {
        // Menggunakan urlencode untuk nilai string yang aman di URL
        return urlencode(mysqli_real_escape_string($koneksi, $v)); 
    }, $params));
    
    return "data_mutasi_kain.php?" . $query_string;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?> | Flowerindo</title>
    
    <link href="../dist/css/bootstrap.min.css" rel="stylesheet"> 
    
    <style>
        :root {
            --tblr-primary: #588f99; 
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden; 
        }
        #wrapper {
            display: flex;
        }
        #sidebar-wrapper {
            min-height: 100vh;
            transition: margin .25s ease-out; 
            background-color: #ffffff;
            box-shadow: 1px 0 10px rgba(0, 0, 0, 0.05);
            width: 18rem; 
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--tblr-primary);
        }
        #sidebar-wrapper .list-group-item {
            color: #495057;
            background-color: transparent;
            border: none;
            padding: 12px 1.5rem;
            font-weight: 500;
        }
        /* Style untuk sub-menu kain */
        #sidebar-wrapper .list-group-item.sidebar-sub-item {
            padding-left: 2.5rem; /* Indentasi */
        }
        /* Style untuk link aktif */
        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            background-color: var(--tblr-primary);
            color: #fff;
            border-radius: 4px;
            margin: 0 1rem;
            width: calc(100% - 2rem);
        }
        #page-content-wrapper {
            flex-grow: 1; 
            min-width: 0;
            width: 100%;
        }
        .toggled #sidebar-wrapper {
            margin-left: -18rem; 
        }
        @media (min-width: 992px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            .toggled #sidebar-wrapper {
                margin-left: -18rem;
            }
        }
        .table-custom-masuk { background-color: #f0fff4; } 
        .table-custom-keluar { background-color: #fff0f0; } 
        .text-masuk { color: #198754; font-weight: 700; } 
        .text-keluar { color: #dc3545; font-weight: 700; } 
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">FLOWERINDO</div>
        <div class="list-group list-group-flush p-2">
            <a href="dashboard.php" class="list-group-item list-group-item-action">
                <i class="me-2">📊</i> Dashboard
            </a>
            <a href="tambah_client.php" class="list-group-item list-group-item-action">
                <i class="me-2">👥</i> Data Client
            </a>
            <div class="list-group-item [KAIN_PARENT_CLASS]">🧵 Bahan Baku Kain</div>
            <a href="tambah_kain.php" class="list-group-item list-group-item-action sidebar-sub-item [TAMBAH_KAIN_ACTIVE]">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kain.php" class="list-group-item list-group-item-action sidebar-sub-item [PAKAI_KAIN_ACTIVE]">→ Catat Pemakaian</a>
            <a href="data_mutasi_kain.php" class="list-group-item list-group-item-action sidebar-sub-item [HISTORI_KAIN_ACTIVE]">→ Histori Kain</a>
    
            <div class="list-group-item [KERTAS_PARENT_CLASS]">📄 Bahan Baku Kertas</div>
            <a href="tambah_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item [TAMBAH_KERTAS_ACTIVE]">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item [PAKAI_KERTAS_ACTIVE]">→ Catat Pemakaian</a>
            <a href="data_transaksi_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item [HISTORI_KERTAS_ACTIVE]">→ Histori Transaksi</a>
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2">📦</i> Data Produk
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2">📈</i> Laporan Penjualan
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2">⚙️</i> Pengaturan
            </a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-outline-primary" id="sidebarToggle">
                    <i class="me-2">☰</i> Menu
                </button>
                <h4 class="d-none d-lg-block my-0 ms-3"><?php echo $title; ?></h4>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="badge bg-primary me-2">AD</span> <?php echo $username; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Profil</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="../logout.php">Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4 p-lg-5">
            
            <h2 class="mb-4"><?php echo $title; ?></h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Stok (Netto)</h6>
                            <p class="card-text fs-4 fw-bold">
                                <?php 
                                    $total_netto_all = 0;
                                    mysqli_data_seek($result_aktual, 0); 
                                    while($row = mysqli_fetch_assoc($result_aktual)) {
                                        $total_netto_all += $row['total_netto'];
                                    }
                                    echo number_format($total_netto_all, 2, ',', '.'); 
                                ?> Y
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h6 class="card-title text-primary">Stok Aktual per Jenis Kain</h6>
                            <div class="d-flex flex-wrap gap-3">
                                <?php 
                                    mysqli_data_seek($result_aktual, 0); // Reset pointer lagi untuk display
                                    if (mysqli_num_rows($result_aktual) > 0) {
                                        while($row = mysqli_fetch_assoc($result_aktual)) {
                                            echo '<span class="badge bg-secondary py-2 px-3 fs-6">'.htmlspecialchars($row['nama_kain']).': '.number_format($row['total_netto'], 2, ',', '.').' Y</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Stok master kosong.</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Tabel Detail Mutasi Stok (<?php echo $total_catatan; ?> Catatan)</span>
                            <a href="../class/export_mutasi_pdf.php?tgl_awal=<?php echo urlencode(htmlspecialchars($filter_tgl_awal)); ?>&tgl_akhir=<?php echo urlencode(htmlspecialchars($filter_tgl_akhir)); ?>&nama_kain=<?php echo urlencode(htmlspecialchars($filter_nama_kain)); ?>&tipe=<?php echo urlencode(htmlspecialchars($filter_tipe)); ?>" class="btn btn-sm btn-danger" target="_blank">
                                <i class="me-1">📄</i> Export PDF
                            </a>
                        </div>
                        <div class="card-body">

                            <form action="" method="GET" class="mb-4 row g-2 align-items-end border-bottom pb-3">
                                <div class="col-md-3 col-sm-6">
                                    <label for="tgl_range" class="form-label mb-0 small">Periode Tanggal</label>
                                    <div class="input-group input-group-sm">
                                        <input type="date" class="form-control" name="tgl_awal" value="<?php echo htmlspecialchars($filter_tgl_awal); ?>">
                                        <span class="input-group-text p-1">-</span>
                                        <input type="date" class="form-control" name="tgl_akhir" value="<?php echo htmlspecialchars($filter_tgl_akhir); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="nama_kain" class="form-label mb-0 small">Jenis Kain</label>
                                    <select class="form-select form-select-sm" name="nama_kain">
                                        <option value="all">Semua Kain</option>
                                        <?php 
                                        mysqli_data_seek($result_unique_kain, 0); 
                                        while($kain = mysqli_fetch_assoc($result_unique_kain)) {
                                            $selected = ($kain['nama_kain'] === $filter_nama_kain) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($kain['nama_kain']) . '" ' . $selected . '>' . htmlspecialchars($kain['nama_kain']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <label for="tipe" class="form-label mb-0 small">Tipe Mutasi</label>
                                    <select class="form-select form-select-sm" name="tipe">
                                        <option value="all" <?php echo ($filter_tipe === 'all') ? 'selected' : ''; ?>>Semua</option>
                                        <option value="masuk" <?php echo (strtoupper($filter_tipe) === 'MASUK') ? 'selected' : ''; ?>>Pemasukan</option>
                                        <option value="keluar" <?php echo (strtoupper($filter_tipe) === 'KELUAR') ? 'selected' : ''; ?>>Pemakaian</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-sm-3">
                                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-2 mt-md-0">Filter</button>
                                </div>
                                <div class="col-md-2 col-sm-3">
                                    <a href="data_mutasi_kain.php" class="btn btn-sm btn-outline-secondary w-100 mt-2 mt-md-0">Reset</a>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Tanggal</th>
                                            <th>Kain</th>
                                            <th>Lebar</th>
                                            <th class="text-center">Tipe</th>
                                            <th class="text-end">Jumlah (Y)</th>
                                            <th class="text-end">Jumlah (M)</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                    if ($total_catatan > 0) {
                                        // Nomor urut dihitung dari offset
                                        $no = $offset + 1;
                                        while($row = mysqli_fetch_assoc($result_data)) {
                                            $is_keluar = ($row['tipe_transaksi'] === 'KELUAR');
                                            
                                            // Menentukan kelas warna baris dan teks
                                            $row_class = $is_keluar ? 'table-custom-keluar' : 'table-custom-masuk'; 
                                            $badge_class = $is_keluar ? 'bg-danger' : 'bg-success';
                                            $text_class = $is_keluar ? 'text-keluar' : 'text-masuk';
                                            
                                            // Format angka
                                            $jumlah_yard_formatted = number_format(abs($row['jumlah_yard']), 2, ',', '.');
                                            $panjang_meter_formatted = number_format(abs($row['panjang_meter']), 2, ',', '.');
                                            $lebar_kain_formatted = ($row['lebar_kain'] !== NULL && $row['lebar_kain'] != 0) ? number_format($row['lebar_kain'], 2, ',', '.') : '-';
                                            
                                            // Tentukan aksi
                                            $aksi_html = '';
                                            if ($row['tipe_transaksi'] === 'MASUK') {
                                                $aksi_html = '
                                                     <a href="edit_transaksi_masuk.php?id=' . htmlspecialchars($row['id_transaksi']) . '" class="btn btn-sm btn-warning me-1">Edit</a>
                                                     <a href="../class/hapus_transaksi_masuk.php?id=' . htmlspecialchars($row['id_transaksi']) . '" 
                                                         class="btn btn-sm btn-danger" 
                                                         onclick="return confirm(\'Hapus Roll ID: ' . htmlspecialchars($row['id_transaksi']) . '? Ini akan mengurangi stok master!\');">Hapus</a>
                                                 ';
                                            } else {
                                                $aksi_html = '
                                                     <a href="edit_transaksi_keluar.php?id=' . htmlspecialchars($row['id_transaksi']) . '" class="btn btn-sm btn-warning me-1">Edit</a>
                                                     <a href="../class/hapus_transaksi_keluar.php?id=' . htmlspecialchars($row['id_transaksi']) . '" 
                                                         class="btn btn-sm btn-danger" 
                                                         onclick="return confirm(\'Hapus Transaksi Keluar ID: ' . htmlspecialchars($row['id_transaksi']) . '? Ini akan mengembalikan stok master!\');">Hapus</a>
                                                 ';
                                            }
                                            ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['tgl_transaksi'])); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['nama_kain']); ?></td>
                                                <td><?php echo $lebar_kain_formatted; ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $row['tipe_transaksi']; ?></span>
                                                </td>
                                                <td class="text-end fw-bold <?php echo $text_class; ?>">
                                                    <?php echo ($is_keluar ? '-' : '+') . $jumlah_yard_formatted; ?> Y
                                                </td>
                                                <td class="text-end">
                                                    <?php echo $panjang_meter_formatted; ?> M
                                                </td>
                                                <td><small><?php echo htmlspecialchars($row['keterangan_final']); ?></small></td>
                                                <td class="text-center small" style="min-width: 150px;"><?php echo $aksi_html; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada catatan mutasi kain yang sesuai dengan filter.</td></tr>';
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_halaman > 1): ?>
                            <nav aria-label="Page navigation example" class="mt-3">
                                <ul class="pagination pagination-sm justify-content-end">
                                    <li class="page-item <?php echo ($halaman_aktif <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_url($koneksi, $halaman_aktif - 1); ?>">Previous</a>
                                    </li>

                                    <?php 
                                    // Batasi tampilan tombol hanya di sekitar halaman aktif (misalnya 5 tombol)
                                    $start_page = max(1, $halaman_aktif - 2);
                                    $end_page = min($total_halaman, $halaman_aktif + 2);

                                    // Penyesuaian agar selalu menampilkan maksimal 5 tombol (jika total halaman > 5)
                                    if ($total_halaman > 5) {
                                        if ($end_page - $start_page < 4) {
                                            if ($start_page == 1) {
                                                $end_page = min($total_halaman, 5);
                                            } elseif ($end_page == $total_halaman) {
                                                $start_page = max(1, $total_halaman - 4);
                                            }
                                        }
                                    }
                                    
                                    // Tambahkan "..." jika start_page bukan 1
                                    if ($start_page > 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++): 
                                    ?>
                                    <li class="page-item <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_url($koneksi, $i); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php 
                                    // Tambahkan "..." jika end_page bukan total_halaman
                                    if ($end_page < $total_halaman) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo ($halaman_aktif >= $total_halaman) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_pagination_url($koneksi, $halaman_aktif + 1); ?>">Next</a>
                                    </li>
                                </ul>
                                <small class="text-muted d-block text-end">
                                    Halaman <?php echo $halaman_aktif; ?> dari <?php echo $total_halaman; ?>. Menampilkan <?php echo mysqli_num_rows($result_data); ?> dari total <?php echo $total_catatan; ?> catatan.
                                </small>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div> 
</div>

<script src="../dist/js/bootstrap.bundle.min.js"></script> 
<script>
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        wrapper.classList.toggle("toggled");
    });
    // Sembunyikan sidebar secara default pada layar kecil
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }
</script>
</body>
</html>
<?php 
// Tutup koneksi database di akhir skrip
mysqli_close($koneksi);
?>