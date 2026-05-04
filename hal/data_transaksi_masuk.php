<?php
// LOKASI FILE: /flower/hal/data_transaksi_masuk.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Semua Data Bahan Baku Kain"; 
$username = htmlspecialchars($_SESSION['username']);

// Include koneksi database (Pastikan path benar)
include '../class/koneksi.php'; 

// --- 1. Ambil Filter dari URL (GET parameters) ---
$filter_tgl_awal = $_GET['tgl_awal'] ?? '';
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? '';
$filter_nama_kain = $_GET['nama_kain'] ?? '';

// --- 2. Bangun Klausa WHERE Dinamis ---
$where_clauses = [];

// Filter Tanggal
if (!empty($filter_tgl_awal) && !empty($filter_tgl_akhir)) {
    // Pastikan tanggal valid
    if (strtotime($filter_tgl_awal) && strtotime($filter_tgl_akhir)) {
        $where_clauses[] = "tgl_diterima BETWEEN '$filter_tgl_awal' AND '$filter_tgl_akhir'";
    }
} elseif (!empty($filter_tgl_awal)) {
    $where_clauses[] = "tgl_diterima >= '$filter_tgl_awal'";
} elseif (!empty($filter_tgl_akhir)) {
    $where_clauses[] = "tgl_diterima <= '$filter_tgl_akhir'";
}

// Filter Nama Kain
if (!empty($filter_nama_kain)) {
    // Jika user memilih semua, kita tidak tambahkan klausa WHERE
    if ($filter_nama_kain !== 'all') {
        // Menggunakan LIKE untuk fleksibilitas (bisa diganti menjadi = jika nama harus persis)
        $where_clauses[] = "nama_kain = '" . mysqli_real_escape_string($koneksi, $filter_nama_kain) . "'";
    }
}

// Gabungkan semua klausa WHERE
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";


// --- 3. Query untuk data penerimaan (transaksi_masuk) dengan filter ---
$sql_data = "SELECT id_roll, nama_kain, tgl_diterima, lebar_kain, panjang_yard_awal AS panjang_yard, 
                    (panjang_yard_awal * 0.9144) AS panjang_meter, keterangan
             FROM transaksi_masuk 
             " . $where_sql . "
             ORDER BY tgl_diterima DESC";

$result_data = mysqli_query($koneksi, $sql_data);

// --- 4. Query untuk mengambil daftar unik nama kain (untuk dropdown filter) ---
$sql_unique_kain = "SELECT DISTINCT nama_kain FROM transaksi_masuk ORDER BY nama_kain ASC";
$result_unique_kain = mysqli_query($koneksi, $sql_unique_kain);


// --- 5. Query untuk total STOK (stok_master) ---
$sql_stok = "SELECT nama_kain, stok_saat_ini AS total_stok 
             FROM stok_master 
             ORDER BY nama_kain ASC";
             
$result_stok = mysqli_query($koneksi, $sql_stok);

if (!$result_data || !$result_stok) {
    die("Query Database Error: " . mysqli_error($koneksi));
}

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

$total_penerimaan = mysqli_num_rows($result_data);
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
            
            <h2 class="mb-4"><?php echo $title; ?> (Total Catatan: <?php echo $total_penerimaan; ?>)</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white fw-bold">Total Akumulasi Stok Kain (Yard)</div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php 
                                if (mysqli_num_rows($result_stok) > 0) {
                                    while($stok = mysqli_fetch_assoc($result_stok)) {
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($stok['nama_kain']); ?>
                                            <span class="badge bg-secondary rounded-pill">
                                                <?php echo number_format($stok['total_stok'], 2); ?> Y
                                            </span>
                                        </li>
                                        <?php
                                    }
                                } else {
                                    echo '<li class="list-group-item text-center text-muted">Stok master kosong.</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Tabel Semua Catatan Penerimaan</span>
                            <a href="../class/export_kain_pdf.php?tgl_awal=<?php echo $filter_tgl_awal; ?>&tgl_akhir=<?php echo $filter_tgl_akhir; ?>&nama_kain=<?php echo $filter_nama_kain; ?>" class="btn btn-sm btn-danger" target="_blank">
                                <i class="me-1">📄</i> Export ke PDF
                            </a>
                        </div>
                        <div class="card-body">

                            <form action="" method="GET" class="mb-4 row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="tgl_awal" class="form-label mb-0 small">Tgl Awal</label>
                                    <input type="date" class="form-control form-control-sm" name="tgl_awal" id="tgl_awal" value="<?php echo $filter_tgl_awal; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="tgl_akhir" class="form-label mb-0 small">Tgl Akhir</label>
                                    <input type="date" class="form-control form-control-sm" name="tgl_akhir" id="tgl_akhir" value="<?php echo $filter_tgl_akhir; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="nama_kain" class="form-label mb-0 small">Jenis Kain</label>
                                    <select class="form-select form-select-sm" name="nama_kain" id="nama_kain">
                                        <option value="all">-- Semua Jenis Kain --</option>
                                        <?php 
                                        while($kain = mysqli_fetch_assoc($result_unique_kain)) {
                                            $selected = ($kain['nama_kain'] === $filter_nama_kain) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($kain['nama_kain']) . '" ' . $selected . '>' . htmlspecialchars($kain['nama_kain']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Kain</th>
                                            <th>Tgl Diterima</th>
                                            <th>Lebar</th>
                                            <th>Panjang (Y)</th>
                                            <th>Panjang (M)</th>
                                            <th>Keterangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                    if (mysqli_num_rows($result_data) > 0) {
                                        // Pointer result set sudah berada di awal karena baru di-query
                                        $no = 1;
                                        while($row = mysqli_fetch_assoc($result_data)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_kain']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($row['tgl_diterima'])); ?></td>
                                                <td><?php echo number_format($row['lebar_kain'], 2); ?></td>
                                                <td><?php echo number_format($row['panjang_yard'], 2); ?> Y</td>
                                                <td><?php echo number_format($row['panjang_meter'], 2); ?> M</td>
                                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                                <td>
                                                    <a href="edit_transaksi_masuk.php?id=<?php echo $row['id_roll']; ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                                                    <a href="../class/hapus_transaksi_masuk.php?id=<?php echo $row['id_roll']; ?>" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Yakin ingin menghapus Roll ID: <?php echo $row['id_roll']; ?>? Penghapusan ini akan mengurangi stok master.');">
                                                         Hapus
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center">Tidak ada catatan penerimaan kain yang sesuai dengan filter.</td></tr>';
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
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