<?php
// LOKASI FILE: /flower/hal/edit_kain.php

session_start();
// Cek autentikasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Edit Catatan Bahan Baku Kain"; 
$username = htmlspecialchars($_SESSION['username']);

// Include koneksi database (Pastikan path benar)
include '../class/koneksi.php'; 

$kain_data = null;
$error_msg = "";

// 1. Ambil ID dari URL dan validasi
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_kain = (int) $_GET['id'];

    // 2. Query untuk mendapatkan data kain lama menggunakan Prepared Statement
    $sql = "SELECT id_kain, nama_kain, tgl_diterima, lebar_kain, panjang_yard FROM bahan_baku WHERE id_kain = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_kain);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $kain_data = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Data catatan kain tidak ditemukan.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error saat menyiapkan query database.";
    }
} else {
    $error_msg = "ID catatan kain tidak valid.";
}

mysqli_close($koneksi);

// Jika data tidak ditemukan atau ID tidak valid, redirect kembali ke list
if ($kain_data === null) {
    // Pesan ini akan ditangkap oleh tambah_kain.php
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    header("Location: tambah_kain.php"); 
    exit;
}
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
            
            <h2 class="mb-4"><?php echo $title; ?>: Catatan ID #<?php echo htmlspecialchars($kain_data['id_kain']); ?></h2>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">Formulir Edit Penerimaan Kain</div>
                        <div class="card-body">
                            
                            <form action="../class/proses_edit_kain.php" method="POST">
                                
                                <input type="hidden" name="id_kain" value="<?php echo htmlspecialchars($kain_data['id_kain']); ?>">

                                <div class="mb-3">
                                    <label for="nama_kain" class="form-label">Nama Kain</label>
                                    <input type="text" class="form-control" id="nama_kain" name="nama_kain" 
                                           value="<?php echo htmlspecialchars($kain_data['nama_kain']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tgl_diterima" class="form-label">Tanggal Diterima</label>
                                    <input type="date" class="form-control" id="tgl_diterima" name="tgl_diterima" 
                                           value="<?php echo htmlspecialchars($kain_data['tgl_diterima']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lebar_kain" class="form-label">Lebar Kain (cm/inch)</label>
                                    <input type="number" step="0.01" class="form-control" id="lebar_kain" name="lebar_kain" 
                                           value="<?php echo htmlspecialchars($kain_data['lebar_kain']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="panjang_yard" class="form-label">Panjang Kain (Yard)</label>
                                    <input type="number" step="0.01" class="form-control" id="panjang_yard" name="panjang_yard" 
                                           value="<?php echo htmlspecialchars($kain_data['panjang_yard']); ?>" required>
                                    <small class="form-text text-muted">Perubahan ini akan mengubah total stok.</small>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success me-2">Simpan Perubahan</button>
                                    <a href="tambah_kain.php" class="btn btn-secondary">Batal</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        </div> 
    </div>
<script src="../dist/js/bootstrap.bundle.min.js"></script> 

<script>
    // JS untuk Toggle Sidebar 
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        wrapper.classList.toggle("toggled");
    });
    // Menutup sidebar secara default pada mobile
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }
</script>
</body>
</html>