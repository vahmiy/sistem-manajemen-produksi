<?php
// LOKASI FILE: /flower/hal/data_client.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Semua Data Client"; 
$username = htmlspecialchars($_SESSION['username']);

// PENTING: Include koneksi database
include '../class/koneksi.php'; 

// Ambil SEMUA data untuk List Client
$sql = "SELECT id_client, nama_client, email_client, no_hp_client, tgl_dibuat FROM clients ORDER BY tgl_dibuat DESC";
$result = mysqli_query($koneksi, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Ambil pesan status (jika ada)
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// Hitung jumlah total klien
$total_clients = mysqli_num_rows($result);
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
            
            <h2 class="mb-4"><?php echo $title; ?> (Total: <?php echo $total_clients; ?>)</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Tabel Semua Data Client</span>
                    <a href="../class/export_client_pdf.php" class="btn btn-sm btn-danger" target="_blank">
                        <i class="me-1">📄</i> Export ke PDF
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Client</th>
                                    <th>Email</th>
                                    <th>Nomor HP</th>
                                    <th>Tgl Registrasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            if (mysqli_num_rows($result) > 0) {
                                // Reset pointer result set untuk looping lagi
                                mysqli_data_seek($result, 0);
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_client']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email_client']); ?></td>
                                        <td><?php echo htmlspecialchars($row['no_hp_client']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tgl_dibuat'])); ?></td>
                                        <td>
                                            <a href="edit_client.php?id=<?php echo $row['id_client']; ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                                            <a href="../class/hapus_client.php?id=<?php echo $row['id_client']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Yakin ingin menghapus client <?php echo htmlspecialchars($row['nama_client']); ?>?');">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">Belum ada data client terdaftar.</td></tr>';
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

<script src="../dist/js/bootstrap.bundle.min.js"></script> 
<script>
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        wrapper.classList.toggle("toggled");
    });
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }
</script>
</body>
</html>
<?php 
mysqli_close($koneksi);
?>