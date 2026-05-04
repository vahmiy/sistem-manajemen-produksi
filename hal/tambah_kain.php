<?php
// LOKASI FILE: /flower/hal/tambah_kain.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Input Bahan Baku Kain"; 
$username = htmlspecialchars($_SESSION['username']);

// Include koneksi database (Pastikan path benar)
include '../class/koneksi.php'; 

// --- 1. LOGIKA PAGINATION UNTUK TABEL ROLL TERBARU ---
$limit = 10; // Batasan record per halaman
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// a. Ambil total record (untuk menghitung total halaman)
$sql_total = "SELECT COUNT(id_roll) AS total FROM transaksi_masuk";
$result_total = mysqli_query($koneksi, $sql_total);
$data_total = mysqli_fetch_assoc($result_total);
$total_records = $data_total['total'];
$total_pages = ceil($total_records / $limit);

// b. Ambil data roll terbaru DENGAN LIMIT dan OFFSET
$sql_data = "SELECT id_roll, nama_kain, tgl_diterima, lebar_kain, panjang_yard_awal
             FROM transaksi_masuk 
             ORDER BY tgl_diterima DESC, id_roll DESC 
             LIMIT $start, $limit"; 
$result_data = mysqli_query($koneksi, $sql_data);

// --- 2. Query Total Penerimaan (STOK BRUTO) ---
$sql_bruto = "SELECT nama_kain, SUM(panjang_yard_awal) AS total_bruto 
              FROM transaksi_masuk 
              GROUP BY nama_kain 
              ORDER BY nama_kain ASC";
$result_bruto = mysqli_query($koneksi, $sql_bruto);

// --- 3. Query Total Stok AKTUAL (STOK NETTO) ---
$sql_aktual = "SELECT nama_kain, stok_saat_ini AS total_netto
               FROM stok_master 
               ORDER BY nama_kain ASC";
$result_aktual = mysqli_query($koneksi, $sql_aktual);


// --- Cek Error Query ---
if (!$result_data || !$result_bruto || !$result_aktual) {
    die("Query Error: " . mysqli_error($koneksi));
}


// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

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
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
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
                                    mysqli_data_seek($result_aktual, 0); // Pastikan pointer di awal
                                    while($row = mysqli_fetch_assoc($result_aktual)) {
                                        $total_netto_all += $row['total_netto'];
                                    }
                                    echo number_format($total_netto_all, 2); 
                                ?> Y
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Penerimaan (Bruto)</h6>
                            <p class="card-text fs-4 fw-bold">
                                <?php 
                                    $total_bruto_all = 0;
                                    mysqli_data_seek($result_bruto, 0); // Pastikan pointer di awal
                                    while($row = mysqli_fetch_assoc($result_bruto)) {
                                        $total_bruto_all += $row['total_bruto'];
                                    }
                                    echo number_format($total_bruto_all, 2); 
                                ?> Y
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Stok Aktual per Jenis Kain</h6>
                            <div class="d-flex flex-wrap gap-3">
                                <?php 
                                    mysqli_data_seek($result_aktual, 0); // Reset pointer lagi untuk display
                                    while($row = mysqli_fetch_assoc($result_aktual)) {
                                        echo '<span class="badge bg-secondary">'.htmlspecialchars($row['nama_kain']).': '.number_format($row['total_netto'], 2).' Y</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white fw-bold">Input Penerimaan Kain Baru (Per Roll)</div>
                        <div class="card-body">
                            <form action="../class/proses_tambah_kain.php" method="POST">
                                <div class="mb-3">
                                    <label for="nama_kain" class="form-label">Nama Kain</label>
                                    <input type="text" class="form-control" name="nama_kain" placeholder="Contoh: Katun Twill" required>
                                </div>
                                <div class="mb-3">
                                    <label for="tgl_diterima" class="form-label">Tanggal Diterima</label>
                                    <input type="date" class="form-control" name="tgl_diterima" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="panjang_yard" class="form-label">Panjang Kain (Yard)</label>
                                    <input type="number" step="0.01" class="form-control" name="panjang_yard" placeholder="Contoh: 120.50" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lebar_kain" class="form-label">Lebar Kain (cm/inch)</label>
                                    <input type="number" step="0.01" class="form-control" name="lebar_kain" placeholder="Contoh: 60">
                                </div>
                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan / Grade</label>
                                    <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Grade A">
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">Simpan Roll Baru</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white fw-bold">10 Catatan Roll Masuk Terbaru (Tracking FIFO)</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Roll ID</th>
                                            <th>Nama Kain</th>
                                            <th>Lebar</th>
                                            <th>Panjang (Y)</th>
                                            <th>Tgl Diterima</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                    if (mysqli_num_rows($result_data) > 0) {
                                        while($row = mysqli_fetch_assoc($result_data)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $row['id_roll']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_kain']); ?></td>
                                                <td><?php echo number_format($row['lebar_kain'], 2); ?></td>
                                                <td><?php echo number_format($row['panjang_yard_awal'], 2); ?> Y</td>
                                                <td><?php echo date('d M Y', strtotime($row['tgl_diterima'])); ?></td>
                                                <td>
                                                    <a href="edit_transaksi_masuk.php?id=<?php echo $row['id_roll']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">Belum ada catatan penerimaan kain.</td></tr>';
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>

                            <nav aria-label="Page navigation example" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                                        <a class="page-link" href="?halaman=<?php echo $page - 1; ?>">Previous</a>
                                    </li>

                                    <?php 
                                    // Tampilkan maksimal 5 tombol halaman
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    for($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                                            <a class="page-link" href="?halaman=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php if($page >= $total_pages){ echo 'disabled'; } ?>">
                                        <a class="page-link" href="?halaman=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            </div>
                                                    <div class="card-footer text-end">
                            <a href="data_transaksi_masuk.php" class="small text-muted">Lihat Semua Riwayat Roll Masuk &raquo;</a>
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
mysqli_close($koneksi);
?>