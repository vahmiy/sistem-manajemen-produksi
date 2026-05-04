<?php
// LOKASI FILE: /flower/hal/detail_po.php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$title = "Detail Purchase Order"; 
$username = htmlspecialchars($_SESSION['username']);

include '../class/koneksi.php'; 

$po_data = null;
$error_msg = "";
$id_order = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_order > 0) {
    // Query untuk mengambil detail PO dan Nama Client
    $sql = "SELECT o.*, c.nama_client 
            FROM orders o
            JOIN clients c ON o.id_client = c.id_client
            WHERE o.id_order = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_order);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $po_data = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Data Purchase Order tidak ditemukan.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error saat menyiapkan query database.";
    }
} else {
    $error_msg = "ID PO tidak valid.";
}

if ($po_data === null) {
    $_SESSION['status_message'] = $error_msg;
    $_SESSION['status_type'] = "danger";
    header("Location: tambah_po.php"); 
    exit;
}

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

mysqli_close($koneksi);
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
                <button class="btn btn-outline-primary" id="sidebarToggle">☰ Menu</button>
                <h4 class="d-none d-lg-block my-0 ms-3"><?php echo $title; ?>: <?php echo htmlspecialchars($po_data['id_po_unik']); ?></h4>
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
            <h2 class="mb-4">Detail PO #<?php echo htmlspecialchars($po_data['id_po_unik']); ?></h2>
            <a href="data_po.php" class="btn btn-sm btn-secondary mb-3"><i class="me-1">←</i> Kembali ke Daftar PO</a>

            <?php 
            if ($message): 
                $badge_class = 'badge-' . strtolower(str_replace(' ', '-', $po_data['status_order']));
            ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Informasi Utama</span>
                            <?php 
                                $badge_class = 'badge-' . strtolower(str_replace(' ', '-', $po_data['status_order']));
                            ?>
                            <h4>Status: <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($po_data['status_order']); ?></span></h4>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Client:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($po_data['nama_client']); ?></dd>
                                
                                <dt class="col-sm-4">Tgl PO Dibuat:</dt>
                                <dd class="col-sm-8"><?php echo date('d M Y', strtotime($po_data['tgl_po_dibuat'])); ?></dd>
                                
                                <dt class="col-sm-4">ID Print (Jika ada):</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($po_data['id_print'] ?: '-'); ?></dd>
                                
                                <hr class="my-2">
                                
                                <dt class="col-sm-4">Nama Kain:</dt>
                                <dd class="col-sm-8 fw-bold"><?php echo htmlspecialchars($po_data['nama_kain']); ?></dd>
                                
                                <dt class="col-sm-4">Kebutuhan Panjang:</dt>
                                <dd class="col-sm-8 fw-bold text-danger"><?php echo number_format($po_data['kebutuhan_panjang'], 2) . " " . htmlspecialchars($po_data['satuan_panjang']); ?></dd>
                                
                                <dt class="col-sm-4">Metode Print:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($po_data['metode_print']); ?></dd>

                                <dt class="col-sm-4">Nama File Desain:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($po_data['nama_file_desain']); ?></dd>

                                <dt class="col-sm-4">Keterangan PO:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($po_data['keterangan_po'] ?: '-'); ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">Foto Desain</div>
                        <div class="card-body text-center">
                            <?php if ($po_data['foto_desain']): ?>
                                <img src="../uploads/desain/<?php echo htmlspecialchars($po_data['foto_desain']); ?>" alt="Foto Desain" class="img-desain">
                            <?php else: ?>
                                <p class="text-muted">Tidak ada foto desain yang diunggah.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold">Update Data Produksi</div>
                        <div class="card-body">
                            <form action="../class/proses_update_po.php" method="POST">
                                <input type="hidden" name="id_order" value="<?php echo $id_order; ?>">

                                <div class="mb-3">
                                    <label for="status_order" class="form-label">Status Order</label>
                                    <select class="form-select" id="status_order" name="status_order" required>
                                        <?php 
                                        $statuses = ['Pending', 'Proses', 'Selesai', 'Batal'];
                                        foreach ($statuses as $status) {
                                            $selected = ($status == $po_data['status_order']) ? 'selected' : '';
                                            echo "<option value=\"{$status}\" {$selected}>{$status}</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="text-danger">Ubah ke **Selesai** untuk memotong stok kain!</small>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <label for="nama_editor" class="form-label">Nama Editor</label>
                                    <input type="text" class="form-control" id="nama_editor" name="nama_editor" value="<?php echo htmlspecialchars($po_data['nama_editor']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="operator_print" class="form-label">Operator Print</label>
                                    <input type="text" class="form-control" id="operator_print" name="operator_print" value="<?php echo htmlspecialchars($po_data['operator_print']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="operator_press" class="form-label">Operator Press</label>
                                    <input type="text" class="form-control" id="operator_press" name="operator_press" value="<?php echo htmlspecialchars($po_data['operator_press']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="jenis_mesin" class="form-label">Jenis Mesin</label>
                                    <input type="text" class="form-control" id="jenis_mesin" name="jenis_mesin" value="<?php echo htmlspecialchars($po_data['jenis_mesin']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="keterangan_tambahan" class="form-label">Keterangan Tambahan Produksi</label>
                                    <textarea class="form-control" id="keterangan_tambahan" name="keterangan_tambahan" rows="3"><?php echo htmlspecialchars($po_data['keterangan_tambahan']); ?></textarea>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">Simpan Perubahan Produksi</button>
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
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        document.getElementById("wrapper").classList.toggle("toggled");
    });
</script>
</body>
</html>