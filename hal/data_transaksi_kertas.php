<?php
// LOKASI FILE: /flower/hal/data_transaksi_kertas.php

session_start();
include '../class/koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$title = "Histori Transaksi Bahan Baku Kertas"; 

// --- 1. Ambil Parameter Filter ---
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01'); // Default awal bulan
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d'); // Default hari ini
$jenis_kertas = isset($_GET['jenis_kertas']) ? $_GET['jenis_kertas'] : '';

// --- 2. Query Utama (Menggabungkan Masuk dan Keluar) ---
// Gunakan UNION ALL untuk menggabungkan dua tabel dengan kolom yang identik
$query_masuk = "
    SELECT 
        tgl_diterima AS tanggal,
        nama_kertas,
        panjang_awal AS kuantitas,
        satuan,
        'MASUK' AS jenis_transaksi,
        CONCAT('Diterima Roll/Box. Lebar: ', lebar_kertas, 'cm. ', keterangan) AS detail,
        tgl_diterima AS tgl_sort
    FROM 
        transaksi_kertas_masuk
    WHERE 
        tgl_diterima BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        AND nama_kertas LIKE '%$jenis_kertas%'
";

$query_keluar = "
    SELECT 
        tgl_keluar AS tanggal,
        nama_kertas,
        qty_keluar AS kuantitas,
        satuan,
        'KELUAR' AS jenis_transaksi,
        CONCAT('Dipakai untuk: ', tujuan_produksi, '. ', keterangan) AS detail,
        tgl_keluar AS tgl_sort
    FROM 
        transaksi_kertas_keluar
    WHERE 
        tgl_keluar BETWEEN '$tgl_mulai' AND '$tgl_selesai'
        AND nama_kertas LIKE '%$jenis_kertas%'
";

// Gabungkan dan urutkan
$sql_transaksi = "($query_masuk) UNION ALL ($query_keluar) ORDER BY tgl_sort DESC, jenis_transaksi ASC";
$result_transaksi = mysqli_query($koneksi, $sql_transaksi);


// --- 3. Ambil Daftar Semua Kertas untuk Filter Dropdown ---
$sql_filter_kertas = "SELECT DISTINCT nama_kertas FROM stok_kertas_master ORDER BY nama_kertas ASC";
$result_filter_kertas = mysqli_query($koneksi, $sql_filter_kertas);

// --- 4. Tentukan Kelas Aktif Sidebar ---
$kertas_parent_class = "sidebar-item-kertas-parent"; 
$histori_kertas_active = "sidebar-sub-item-active";   
?>

<!doctype html>
<html lang="en">
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
                <button class="btn btn-outline-primary" id="sidebarToggle"><i class="me-2">☰</i> Menu</button>
                <h4 class="d-none d-lg-block my-0 ms-3"><?php echo $title; ?></h4>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <span class="badge bg-primary me-2">AD</span> <?php echo $username; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item text-danger" href="../logout.php">Logout</a></div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid p-4 p-lg-5">
            <h2 class="mb-4 text-primary">Histori Masuk & Keluar Bahan Baku Kertas</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="tgl_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="tgl_selesai" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="tgl_selesai" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="jenis_kertas" class="form-label">Filter Jenis Kertas</label>
                            <select class="form-select" id="jenis_kertas" name="jenis_kertas">
                                <option value="">-- Semua Jenis Kertas --</option>
                                <?php while($filter = mysqli_fetch_assoc($result_filter_kertas)): ?>
                                    <option value="<?php echo htmlspecialchars($filter['nama_kertas']); ?>" 
                                            <?php echo ($jenis_kertas == $filter['nama_kertas']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($filter['nama_kertas']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Tanggal</th>
                                    <th style="width: 15%;">Jenis Transaksi</th>
                                    <th style="width: 25%;">Kertas</th>
                                    <th style="width: 15%;">Kuantitas</th>
                                    <th>Detail / Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($result_transaksi) > 0):
                                    while($data = mysqli_fetch_assoc($result_transaksi)):
                                        $is_masuk = ($data['jenis_transaksi'] == 'MASUK');
                                        $badge_color = $is_masuk ? 'success' : 'danger';
                                        $qty_sign = $is_masuk ? '+' : '-';
                                ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge_color; ?>"><?php echo $data['jenis_transaksi']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($data['nama_kertas']); ?></td>
                                    <td class="fw-bold text-<?php echo $badge_color; ?>">
                                        <?php echo $qty_sign . ' ' . number_format($data['kuantitas'], 2) . ' ' . $data['satuan']; ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($data['detail'])); ?></td>
                                </tr>
                                <?php 
                                    endwhile; 
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-4">
                                        Tidak ada data transaksi kertas pada rentang tanggal dan filter yang dipilih.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    Data digabungkan dari histori penerimaan (MASUK) dan pemakaian (KELUAR).
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="tambah_kertas.php" class="btn btn-outline-secondary">← Kembali ke Pengelolaan Kertas</a>
            </div>
        </div> 
    </div>
</div>
<script src="../dist/js/bootstrap.bundle.min.js"></script> 
<script>
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        document.getElementById("wrapper").classList.toggle("toggled");
    });
</script>
</body>
</html>
<?php 
// Koneksi ditutup di awal (setelah semua query selesai) atau pastikan ditutup di akhir.
// Jika Anda menutup koneksi di awal (setelah mengambil semua result), pastikan tidak ada kode lain yang membutuhkannya.
// mysqli_close($koneksi); // Sudah ditutup sebelumnya jika menggunakan struktur seperti di tambah_kertas.php
?>