<?php
// LOKASI FILE: /flower/hal/edit_transaksi_masuk.php

session_start();
include '../class/koneksi.php'; 

// 1. Amankan Halaman
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

// 2. Tentukan Judul & Username
$username = htmlspecialchars($_SESSION['username']);
$title_base = "Edit Roll Kain"; 
$data_roll = null; // Inisialisasi variabel data_roll

// 3. Ambil data dari database HANYA JIKA method GET
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $id_roll = (int) ($_GET['id'] ?? 0);

    if ($id_roll <= 0) {
        // ID tidak valid
        $_SESSION['status_message'] = "ID Roll tidak valid.";
        $_SESSION['status_type'] = "danger";
        header("Location: tambah_kain.php");
        exit;
    }
    
    // Query untuk mengambil data roll menggunakan Prepared Statement
    $sql_roll = "SELECT id_roll, nama_kain, tgl_diterima, lebar_kain, panjang_yard_awal, keterangan 
                 FROM transaksi_masuk 
                 WHERE id_roll = ?";
    $stmt_roll = mysqli_prepare($koneksi, $sql_roll);
    
    if (!$stmt_roll) {
        die("Prepare failed: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt_roll, "i", $id_roll);
    mysqli_stmt_execute($stmt_roll);
    $result_roll = mysqli_stmt_get_result($stmt_roll);
    $data_roll = mysqli_fetch_assoc($result_roll);
    mysqli_stmt_close($stmt_roll);

    if (!$data_roll) {
        // Data tidak ditemukan
        $_SESSION['status_message'] = "Data roll ID " . $id_roll . " tidak ditemukan.";
        $_SESSION['status_type'] = "danger";
        header("Location: tambah_kain.php");
        exit;
    }

    // Set judul dengan ID yang ditemukan
    $title = "Edit Roll Kain ID: " . $data_roll['id_roll'];
    
} else {
    // Jika ada akses ke file ini dengan method selain GET, redirect saja
    header("Location: tambah_kain.php");
    exit;
}

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

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

            <div class="card shadow-sm col-lg-6 mx-auto">
                <div class="card-header bg-warning text-white fw-bold">Edit Data Roll Kain</div>
                <div class="card-body">
                    <form action="../class/proses_edit_roll.php" method="POST">
                        <input type="hidden" name="id_roll" value="<?php echo $data_roll['id_roll']; ?>">
                        <input type="hidden" name="panjang_lama" value="<?php echo $data_roll['panjang_yard_awal']; ?>">
                        
                        <div class="mb-3">
                            <label for="nama_kain" class="form-label">Nama Kain</label>
                            <input type="text" class="form-control" name="nama_kain" 
                                value="<?php echo htmlspecialchars($data_roll['nama_kain']); ?>" readonly>
                            <small class="form-text text-danger">Nama kain tidak dapat diubah!</small>
                        </div>

                        <div class="mb-3">
                            <label for="tgl_diterima" class="form-label">Tanggal Diterima</label>
                            <input type="date" class="form-control" id="tgl_diterima" name="tgl_diterima" 
                                value="<?php echo htmlspecialchars($data_roll['tgl_diterima']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="lebar_kain" class="form-label">Lebar Kain (cm/inch)</label>
                            <input type="number" step="0.01" class="form-control" id="lebar_kain" name="lebar_kain" 
                                value="<?php echo htmlspecialchars($data_roll['lebar_kain']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="panjang_baru" class="form-label">Panjang Kain (Yard) - Baru</label>
                            <input type="number" step="0.01" class="form-control" id="panjang_baru" name="panjang_baru" 
                                value="<?php echo htmlspecialchars($data_roll['panjang_yard_awal']); ?>" required>
                            <small class="form-text text-muted">Perubahan nilai ini akan menyesuaikan saldo total di Stok Master.</small>
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan" name="keterangan" 
                                value="<?php echo htmlspecialchars($data_roll['keterangan']); ?>">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                            <a href="data_transaksi_masuk.php" class="btn btn-outline-secondary mt-2">Kembali ke Data Transaksi</a>
                        </div>
                    </form>
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