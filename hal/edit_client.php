<?php
// LOKASI FILE: /flower/hal/edit_client.php

session_start();
// Cek autentikasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Edit Data Client"; 
$username = htmlspecialchars($_SESSION['username']);

// Include koneksi database (Pastikan path benar)
include '../class/koneksi.php'; 

$client_data = null;
$error_msg = "";

// 1. Ambil ID dari URL dan validasi
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_client = (int) $_GET['id'];

    // 2. Query untuk mendapatkan data client lama menggunakan Prepared Statement
    $sql = "SELECT id_client, nama_client, email_client, no_hp_client FROM clients WHERE id_client = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_client);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $client_data = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Data client tidak ditemukan.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error saat menyiapkan query database.";
    }
} else {
    $error_msg = "ID client tidak valid.";
}

// Tutup koneksi segera setelah selesai query
mysqli_close($koneksi);

// Jika data tidak ditemukan atau ID tidak valid, redirect kembali ke list
if ($client_data === null) {
    // Pesan ini akan ditangkap oleh tambah_client.php
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    header("Location: tambah_client.php"); 
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
            
            <h2 class="mb-4"><?php echo $title; ?>: <?php echo htmlspecialchars($client_data['nama_client']); ?></h2>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">Formulir Edit Client</div>
                        <div class="card-body">
                            <form action="../class/proses_edit_client.php" method="POST">
                                
                                <input type="hidden" name="id_client" value="<?php echo htmlspecialchars($client_data['id_client']); ?>">

                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Client</label>
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           value="<?php echo htmlspecialchars($client_data['nama_client']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Client</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($client_data['email_client']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="no_hp" class="form-label">Nomor HP</label>
                                    <input type="tel" class="form-control" id="no_hp" name="no_hp" 
                                           value="<?php echo htmlspecialchars($client_data['no_hp_client']); ?>"
                                           pattern="[0-9]{9,15}" 
                                           title="Masukkan 9 sampai 15 digit angka." 
                                           required>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success me-2">Simpan Perubahan</button>
                                    <a href="tambah_client.php" class="btn btn-secondary">Batal</a>
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