<?php
// LOKASI FILE: /flower/hal/tambah_po.php

session_start();
// Pastikan koneksi.php sudah disiapkan dengan variabel $koneksi
include '../class/koneksi.php'; 
$title = "Input Purchase Order Baru"; 
$current_page = "tambah_po.php"; 
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); exit;
}

// ===============================================
// LOGIKA PHP UNTUK GENERATE NOMOR PO DINAMIS
// ===============================================

// 1. Ambil daftar klien
$sql_clients = "SELECT id_client, nama_client FROM clients ORDER BY nama_client ASC";
$result_clients = mysqli_query($koneksi, $sql_clients);

// 2. Ambil daftar nama kain untuk dropdown
$sql_kain = "SELECT DISTINCT nama_kain FROM stok_master ORDER BY nama_kain ASC";
$result_kain = mysqli_query($koneksi, $sql_kain);

$list_nama_kain = [];
if ($result_kain) {
    while($kain = mysqli_fetch_assoc($result_kain)) {
        $list_nama_kain[] = $kain['nama_kain'];
    }
}

// 3. Generate Nomor PO Unik Otomatis
$tanggal_hari_ini = date('Ymd');
$prefix = "PO-" . $tanggal_hari_ini . "-";

$sql_last_po = "
    SELECT id_po_unik 
    FROM orders 
    WHERE id_po_unik LIKE '{$prefix}%'
    ORDER BY id_order DESC 
    LIMIT 1
";
$result_last_po = mysqli_query($koneksi, $sql_last_po);
$nomor_urut = 1;

if ($result_last_po && mysqli_num_rows($result_last_po) > 0) {
    $row = mysqli_fetch_assoc($result_last_po);
    $last_number = substr($row['id_po_unik'], -3); 
    $nomor_urut = intval($last_number) + 1;
}

$id_po_unik_otomatis = $prefix . str_pad($nomor_urut, 3, '0', STR_PAD_LEFT);

// Fungsi untuk membuat class 'active'
function is_active($page, $current) {
    return ($page == $current) ? 'active' : '';
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
        :root {--tblr-primary: #588f99;}
        body {background-color: #f5f7fa; font-family: 'Inter', sans-serif; overflow-x: hidden;}
        #wrapper {display: flex;}
        #sidebar-wrapper {min-height: 100vh; transition: margin .25s ease-out; background-color: #ffffff; box-shadow: 1px 0 10px rgba(0, 0, 0, 0.05); width: 18rem; z-index: 1030;}
        #sidebar-wrapper .sidebar-heading {padding: 1.5rem 1.5rem; font-size: 1.5rem; font-weight: 600; color: var(--tblr-primary);}
        #sidebar-wrapper .list-group-item {color: #495057; background-color: transparent; border: none; padding: 12px 1.5rem; font-weight: 500; cursor: pointer;}
        #sidebar-wrapper .list-group-item.sidebar-sub-item {padding-left: 2.5rem;}
        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {background-color: var(--tblr-primary); color: #fff; border-radius: 4px; margin: 0 1rem; width: calc(100% - 2rem);}
        #page-content-wrapper {flex-grow: 1; min-width: 0; width: 100%;}
        .toggled #sidebar-wrapper {margin-left: -18rem;}
        @media (min-width: 992px) {
            #sidebar-wrapper {margin-left: 0;}
            .toggled #sidebar-wrapper {margin-left: -18rem;}
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">FLOWERINDO</div>
        <div class="list-group list-group-flush p-2">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo is_active('dashboard.php', $current_page); ?>"><i class="me-2">📊</i> Dashboard</a>
            <div class="list-group-item">📝 Purchase Order (PO)</div>
            <a href="data_po.php" class="list-group-item list-group-item-action sidebar-sub-item <?php echo is_active('data_po.php', $current_page); ?>">→ Data PO</a>
            <a href="tambah_po.php" class="list-group-item list-group-item-action sidebar-sub-item <?php echo is_active('tambah_po.php', $current_page); ?>">→ Input PO Baru</a>
            <a href="tambah_client.php" class="list-group-item list-group-item-action"><i class="me-2">👥</i> Data Client</a>
            <div class="list-group-item">🧵 Bahan Baku Kain</div>
            <a href="tambah_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Catat Pemakaian</a>
            <a href="data_mutasi_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Histori Kain</a>
            <div class="list-group-item">📄 Bahan Baku Kertas</div>
            <a href="tambah_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Catat Pemakaian</a>
            <a href="data_transaksi_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Histori Transaksi</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="me-2">📦</i> Data Produk</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="me-2">📈</i> Laporan Penjualan</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="me-2">⚙️</i> Pengaturan</a>
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
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="badge bg-primary me-2">AD</span> <?php echo $username; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Profil</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="../class/logout.php">Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4 p-lg-5">
            <h2 class="mb-4"><?php echo $title; ?></h2>

            <?php
            if (isset($_GET['status'])) {
                $status = $_GET['status'];
                $message = "";
                $alert_class = "";

                if ($status == 'sukses') {
                    $po_id_display = htmlspecialchars($_GET['po_id'] ?? $id_po_unik_otomatis);
                    $message = "✅ **Data Purchase Order berhasil disimpan!** Nomor PO baru adalah **" . $po_id_display . "**";
                    $alert_class = "alert-success";
                } elseif ($status == 'gagal') {
                    $message = "❌ **Penyimpanan Purchase Order gagal!** Silakan cek kembali data Anda. Error: " . htmlspecialchars($_GET['msg'] ?? 'Kesalahan koneksi/query database.');
                    $alert_class = "alert-danger";
                }
                
                if ($message !== "") {
                    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
                    echo $message;
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                }
            }
            ?>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">Detail Purchase Order</div>
                <div class="card-body">
                    <form action="../class/proses_po.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="id_po_unik" class="form-label">Nomor PO</label>
                                <input type="text" 
                                       class="form-control fw-bold bg-light" 
                                       id="id_po_unik" 
                                       name="id_po_unik" 
                                       value="<?php echo $id_po_unik_otomatis; ?>" 
                                       readonly 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="tgl_po_dibuat" class="form-label">Tanggal PO Dibuat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tgl_po_dibuat" name="tgl_po_dibuat" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="id_client" class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_client" name="id_client" required>
                                    <option value="">Pilih Client</option>
                                    <?php 
                                    if ($result_clients) {
                                        mysqli_data_seek($result_clients, 0); 
                                        while($client = mysqli_fetch_assoc($result_clients)) {
                                            echo '<option value="' . $client['id_client'] . '">' . htmlspecialchars($client['nama_client']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <h5 class="mt-3 border-bottom pb-2">Detail Bahan & Desain</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="nama_kain" class="form-label">Jenis Kain (Fabric) <span class="text-danger">*</span></label>
                                <select class="form-select" id="nama_kain" name="nama_kain" required>
                                    <option value="">Pilih Jenis Kain</option>
                                    <?php 
                                    foreach ($list_nama_kain as $nama_kain): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($nama_kain); ?>">
                                            <?php echo htmlspecialchars($nama_kain); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nama_file_desain" class="form-label">SPU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_file_desain" name="nama_file_desain" placeholder="Cth: TENZZ25091830-3-s98" required>
                            </div>
                            <div class="col-md-4">
                                <label for="kebutuhan_panjang" class="form-label">Kebutuhan Panjang (Need) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="kebutuhan_panjang" name="kebutuhan_panjang" required>
                            </div>
                            <div class="col-md-2">
                                <label for="satuan_panjang" class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select class="form-select" id="satuan_panjang" name="satuan_panjang" required>
                                    <option value="Yard">Yard</option>
                                    <option value="Meter">Meter</option>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label for="metode_print" class="form-label">Metode Print</label>
                                <select class="form-select" id="metode_print" name="metode_print">
                                    <option value="1 Sisi">1 Sisi</option>
                                    <option value="2 Sisi">2 Sisi</option>
                                    <option value="">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="foto_desain" class="form-label">Foto Produk / QR (Opsional)</label>
                                <input class="form-control" type="file" id="foto_desain" name="foto_desain" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label for="keterangan_po" class="form-label">Keterangan PO</label>
                                <textarea class="form-control" id="keterangan_po" name="keterangan_po" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <h5 class="mt-3 border-bottom pb-2">Penugasan Awal Produksi (Opsional)</h5>
                        <div class="row g-3 mb-4">
                             <div class="col-md-6">
                                <label for="nama_editor" class="form-label">Nama Editor</label>
                                <input type="text" class="form-control" id="nama_editor" name="nama_editor">
                            </div>
                            <div class="col-md-6">
                                <label for="operator_print" class="form-label">Operator Print</label>
                                <input type="text" class="form-control" id="operator_print" name="operator_print">
                            </div>
                            <div class="col-md-6">
                                <label for="operator_press" class="form-label">Operator Press</label>
                                <input type="text" class="form-control" id="operator_press" name="operator_press">
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_mesin" class="form-label">Jenis Mesin</label>
                                <input type="text" class="form-control" id="jenis_mesin" name="jenis_mesin">
                            </div>
                            <div class="col-12">
                                <label for="keterangan_tambahan" class="form-label">Keterangan Tambahan Produksi (Remark)</label>
                                <textarea class="form-control" id="keterangan_tambahan" name="keterangan_tambahan" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success btn-lg">Simpan Purchase Order</button>
                            <a href="data_po.php" class="btn btn-secondary btn-lg">Batal</a>
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
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>