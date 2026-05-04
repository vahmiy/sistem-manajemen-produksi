<?php
// LOKASI FILE: /flower/hal/tambah_pemakaian_kertas.php

session_start();
include '../class/koneksi.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$title = "Catat Pemakaian Kertas Produksi"; 

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// Ambil daftar jenis kertas dari stok master untuk dropdown
// PENTING: Ambil juga stok_saat_ini agar bisa ditampilkan secara dinamis via JS
$sql_kertas = "SELECT nama_kertas, satuan, stok_saat_ini FROM stok_kertas_master ORDER BY nama_kertas ASC";
$result_kertas = mysqli_query($koneksi, $sql_kertas);

// Siapkan data stok dalam format JSON untuk JavaScript (Fitur Dinamis)
$stok_data = [];
$options_html = '<option value="">-- Pilih Jenis Kertas --</option>';

if ($result_kertas) {
    while($kertas = mysqli_fetch_assoc($result_kertas)) {
        // Data untuk JS
        $stok_data[$kertas['nama_kertas'] . '|' . $kertas['satuan']] = [
            'stok' => number_format($kertas['stok_saat_ini'], 2, ',', '.'),
            'satuan' => $kertas['satuan']
        ];
        
        // Data untuk Dropdown
        $options_html .= '<option value="' . htmlspecialchars($kertas['nama_kertas']) . '|' . htmlspecialchars($kertas['satuan']) . '">';
        $options_html .= htmlspecialchars($kertas['nama_kertas']) . ' (' . htmlspecialchars($kertas['satuan']) . ')';
        $options_html .= '</option>';
    }
} else {
    // Handle error jika query gagal
    $options_html = '<option value="" disabled>Error: ' . mysqli_error($koneksi) . '</option>';
}

// Tutup koneksi setelah selesai mengambil data
mysqli_close($koneksi);

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
            <h2 class="mb-4 text-primary">Form Catat Pemakaian Kertas</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-warning text-dark fw-bold">
                            <i class="me-2">✂️</i> Pengurangan Stok Kertas (Pemakaian Produksi Sublime/DTF)
                        </div>
                        <div class="card-body p-4">
                            <form action="../class/proses_pemakaian_kertas.php" method="POST">
                                
                                <div class="mb-3">
                                    <label for="nama_kertas" class="form-label fw-bold">Jenis Kertas yang Digunakan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="nama_kertas" name="nama_kertas" required>
                                        <?php echo $options_html; ?>
                                    </select>
                                    <div id="stok-sekarang" class="stok-display d-none mt-2">
                                        Stok Tersedia: <span class="text-primary" id="stok-value">0</span>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tgl_keluar" class="form-label fw-bold">Tanggal Pemakaian <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tgl_keluar" name="tgl_keluar" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="qty_keluar" class="form-label fw-bold">Jumlah Kuantitas Dipakai <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" id="qty_keluar" name="qty_keluar" placeholder="Contoh: 15.5 M atau 50 LBR" required>
                                        <small class="form-text text-muted">Masukkan kuantitas sesuai satuan yang dipilih.</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="tujuan_produksi" class="form-label fw-bold">Nomor PO / Tujuan Produksi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tujuan_produksi" name="tujuan_produksi" placeholder="Contoh: PO-202509-001 / Produksi Kaos Client X" required>
                                    <small class="form-text text-muted">Catat Nomor Pesanan agar mudah ditelusuri.</small>
                                </div>

                                <div class="mb-4">
                                    <label for="keterangan" class="form-label fw-bold">Keterangan Tambahan</label>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Catatan: Digunakan untuk desain 5 warna"></textarea>
                                </div>

                                <button type="submit" class="btn btn-warning w-100 fw-bold py-2 shadow-sm">
                                    <i class="me-2">⬇️</i> CATAT & KURANGI STOK KERTAS
                                </button>
                                
                                <div class="text-center mt-3">
                                    <a href="tambah_kertas.php" class="btn btn-link text-primary">← Kembali ke Halaman Tambah Stok</a>
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
    // Data Stok dari PHP ke JavaScript
    const stokData = <?php echo json_encode($stok_data); ?>;
    const selectKertas = document.getElementById('nama_kertas');
    const stokDisplay = document.getElementById('stok-sekarang');
    const stokValue = document.getElementById('stok-value');
    const qtyInput = document.getElementById('qty_keluar');

    selectKertas.addEventListener('change', function() {
        const selectedValue = this.value;
        
        if (stokData[selectedValue]) {
            const data = stokData[selectedValue];
            stokValue.textContent = `${data.stok} ${data.satuan}`;
            stokDisplay.classList.remove('d-none');
            // Update placeholder pada input kuantitas sesuai satuan
            qtyInput.placeholder = `Contoh: 15.5 ${data.satuan} atau 50 ${data.satuan}`;
            
        } else {
            stokDisplay.classList.add('d-none');
            qtyInput.placeholder = 'Contoh: 15.5 M atau 50 LBR';
        }
    });

    // Toggle Sidebar
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        document.getElementById("wrapper").classList.toggle("toggled");
    });
</script>
</body>
</html>