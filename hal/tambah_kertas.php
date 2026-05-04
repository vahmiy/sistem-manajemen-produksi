<?php
// LOKASI FILE: /flower/hal/tambah_kertas.php

session_start();
include '../class/koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$title = "Tambah & Data Bahan Baku Kertas"; 

// Ambil pesan status (dari proses_tambah_kertas.php)
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// Query untuk total STOK kertas (stok_kertas_master)
$sql_stok = "SELECT nama_kertas, stok_saat_ini, satuan
             FROM stok_kertas_master 
             ORDER BY nama_kertas ASC";
$result_stok = mysqli_query($koneksi, $sql_stok);

// Cek jika ada error query stok
if (!$result_stok) {
    $error_stok = "Error saat mengambil data stok: " . mysqli_error($koneksi);
    $result_stok = false; // Setel ke false agar tidak di-loop
} else {
    $error_stok = null;
}

// --- Mulai HTML dan Layout ---
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
            <h2 class="mb-4">Input & Stok Bahan Baku Kertas</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold">Input Data Kertas Baru</div>
                        <div class="card-body">
                            <form action="../class/proses_tambah_kertas.php" method="POST">
                                <div class="mb-3">
                                    <label for="nama_kertas" class="form-label">Nama/Jenis Kertas</label>
                                    <input type="text" class="form-control" id="nama_kertas" name="nama_kertas" placeholder="Contoh: Sublim Roll 110cm / DTF Sheet A3" required>
                                </div>
                                <div class="mb-3">
                                    <label for="tgl_diterima" class="form-label">Tanggal Diterima</label>
                                    <input type="date" class="form-control" id="tgl_diterima" name="tgl_diterima" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lebar_kertas" class="form-label">Lebar Kertas (cm)</label>
                                        <input type="number" step="0.01" class="form-control" id="lebar_kertas" name="lebar_kertas" placeholder="Roll: 110.00 | Lembar: 0">
                                        <small class="form-text text-muted">Isi '0' jika menggunakan kertas lembaran (A4/A3).</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="gramasi" class="form-label">Gramasi (gsm)</label>
                                        <input type="number" step="1" class="form-control" id="gramasi" name="gramasi" placeholder="Contoh: 90 / 120">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <label for="panjang_awal" class="form-label">Panjang / Jumlah (Awal)</label>
                                        <input type="number" step="0.01" class="form-control" id="panjang_awal" name="panjang_awal" required>
                                    </div>
                                    <div class="col-4">
                                        <label for="satuan" class="form-label">Satuan</label>
                                        <select class="form-select" id="satuan" name="satuan" required>
                                            <option value="LBR">LBR (Lembar)</option>
                                            <option value="M">M (Meter)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-3">Tambah Stok Kertas</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white fw-bold">Stok Kertas Akumulasi (Total)</div>
                        <div class="card-body p-0">
                            <?php if ($error_stok): ?>
                                <div class="alert alert-danger mb-0 text-center"><?php echo $error_stok; ?></div>
                            <?php elseif (mysqli_num_rows($result_stok) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    while($stok = mysqli_fetch_assoc($result_stok)) {
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($stok['nama_kertas']); ?>
                                            <span class="badge bg-secondary rounded-pill">
                                                <?php echo number_format($stok['stok_saat_ini'], 2) . ' ' . $stok['satuan']; ?>
                                            </span>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item text-center text-muted">Stok kertas master kosong.</li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="tambah_pemakaian_kertas.php" class="btn btn-warning text-dark fw-bold">
                            Catat Penggunaan Kertas (Keluar) &raquo;
                        </a>
                        <a href="data_transaksi_kertas.php" class="btn btn-outline-primary">
                            Lihat Histori Masuk & Keluar Kertas &raquo;
                        </a>
                    </div>
                </div>
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
mysqli_close($koneksi);
?>