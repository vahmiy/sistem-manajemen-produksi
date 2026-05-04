<?php
// LOKASI FILE: /flower/hal/tambah_pemakaian_kain.php (TERKOREKSI UI/UX & FUNGSI SIDEBAR)

session_start();
include '../class/koneksi.php'; 

// Cek autentikasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); 
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$title = "Catat Pemakaian Kain Produksi"; 

// Ambil pesan status
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// --- Query Kain dari stok_master ---
$sql_kain = "SELECT nama_kain, stok_saat_ini FROM stok_master ORDER BY nama_kain ASC";
$result_kain = mysqli_query($koneksi, $sql_kain);

// Siapkan data stok untuk JavaScript (menggunakan format float dengan titik desimal)
$stok_data = [];
$options_html = '<option value="" disabled selected>-- Pilih Jenis Kain --</option>';

if ($result_kain) {
    while($kain = mysqli_fetch_assoc($result_kain)) {
        $satuan = 'Y'; // Asumsi Satuan Default
        $stok_float = (float)$kain['stok_saat_ini'];
        
        // Data untuk JS: stok dalam FLOAT (titik desimal)
        $stok_data[htmlspecialchars($kain['nama_kain']) . '|' . $satuan] = [
            'stok' => $stok_float, 
            'stok_formatted' => number_format($stok_float, 2, ',', '.'), // Format untuk tampilan
            'satuan' => $satuan
        ];
        
        // Data untuk Dropdown (Tampilkan Stok di opsi untuk kemudahan user)
        $options_html .= '<option value="' . htmlspecialchars($kain['nama_kain']) . '|' . $satuan . '">';
        $options_html .= htmlspecialchars($kain['nama_kain']) . ' (' . number_format($stok_float, 2, ',', '.') . ' ' . $satuan . ' Tersedia)';
        $options_html .= '</option>';
    }
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
                                <a class="dropdown-item text-danger" href="../class/logout.php">Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
            
            <h2 class="mb-4 text-center">Formulir Pemakaian Kain</h2>

            <?php if ($message): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center"> 
                <div class="col-lg-6">
                    <div class="card shadow-lg">
                        <div class="card-header card-header-custom">Input Pemakaian Produksi</div>
                        <div class="card-body">
                            
                            <form action="../class/proses_pemakaian_kain.php" method="POST" id="form-pemakaian-kain" novalidate>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tgl_keluar" class="form-label">Tanggal Pemakaian</label>
                                        <input type="date" class="form-control" name="tgl_keluar" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="tujuan_produksi" class="form-label">ID Order / Tujuan Produksi</label>
                                        <input type="text" class="form-control" name="tujuan_produksi" placeholder="Contoh: PO-2025-001" required>
                                        <div class="form-text">ID yang akan dicatat di histori transaksi.</div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <label for="nama_kain" class="form-label">Jenis Kain yang Dipakai</label>
                                    <select class="form-select" id="nama_kain" name="nama_kain" required>
                                        <?php echo $options_html; ?>
                                    </select>
                                    <div class="invalid-feedback">Harap pilih jenis kain dari daftar.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="qty_keluar" class="form-label">Kuantitas Pemakaian (Yard)</label>
                                    <input type="number" step="0.01" class="form-control" id="qty_keluar" name="qty_keluar" placeholder="Contoh: 50.75" min="0.01" required>
                                    <div class="invalid-feedback" id="qty-feedback">Kuantitas pemakaian harus diisi dan tidak boleh melebihi stok tersedia.</div>
                                    <small class="form-text text-muted" id="stok-info">Pilih kain terlebih dahulu.</small>
                                </div>

                                <div class="mb-4">
                                    <label for="keterangan" class="form-label">Keterangan Tambahan (Opsional)</label>
                                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Potong untuk sampel / Penggunaan darurat"></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-lg btn-success" id="btn-submit">
                                        Catat & Kurangi Stok Kain
                                    </button>
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
    // ==========================================================
    // ** JAVASCRIPT SIDEBAR TOGGLE (KOREKSI FUNGSI) **
    // ==========================================================
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        wrapper.classList.toggle("toggled");
    });
    // Sembunyikan sidebar secara default pada perangkat mobile
    if (window.innerWidth < 992) {
        // Cek jika toggle belum pernah diset secara manual di desktop
        if (!sessionStorage.getItem('sidebar_toggled')) {
            document.getElementById("wrapper").classList.add("toggled");
        }
    }

    // ==========================================================
    // ** JAVASCRIPT VALIDASI FORM (LOGIKA STOK) **
    // ==========================================================
    
    // Data Stok yang sudah di-encode dalam format FLOAT (titik desimal)
    const stokKain = <?php echo json_encode($stok_data); ?>;
    const selectKain = document.getElementById('nama_kain');
    const stokInfo = document.getElementById('stok-info');
    const qtyKeluar = document.getElementById('qty_keluar');
    const qtyFeedback = document.getElementById('qty-feedback');
    const btnSubmit = document.getElementById('btn-submit');

    // Mengambil data stok dari JavaScript 
    function getSelectedStokData() {
        const selectedValue = selectKain.value;
        return stokKain[selectedValue] || null;
    }

    // Fungsi Validasi Utama
    function validateForm() {
        const stokData = getSelectedStokData();
        // Menggunakan parseFloat untuk qty, dan memeriksa jika hasilnya NaN, gunakan 0
        const qty = parseFloat(qtyKeluar.value.replace(',', '.')) || 0; 
        let isValid = true;
        let stokTersedia = 0;
        let satuan = 'Y';
        
        // --- 1. Update Info Stok di bawah kolom Kuantitas ---
        if (stokData) {
            stokTersedia = stokData.stok;
            satuan = stokData.satuan;
            stokInfo.innerHTML = `Stok Tersedia Saat Ini: <strong>${stokData.stok_formatted} ${satuan}</strong>`;
            stokInfo.classList.remove('text-danger', 'text-muted');
            stokInfo.classList.add('text-primary');
        } else {
            stokInfo.innerHTML = 'Pilih kain terlebih dahulu.';
            stokInfo.classList.remove('text-primary', 'text-danger');
            stokInfo.classList.add('text-muted');
        }

        // --- 2. Validasi Pemilihan Kain ---
        if (!selectKain.value) {
            selectKain.classList.add('is-invalid');
            isValid = false;
        } else {
            selectKain.classList.remove('is-invalid');
        }

        // --- 3. Validasi Kuantitas ---
        qtyKeluar.classList.remove('is-invalid', 'is-valid');
        
        if (qty <= 0) {
            qtyKeluar.classList.add('is-invalid');
            qtyFeedback.textContent = 'Kuantitas pemakaian harus lebih dari 0.00 Yard.';
            isValid = false;
        } else if (stokData && qty > stokTersedia) {
            qtyKeluar.classList.add('is-invalid');
            // Menampilkan pesan error yang lebih jelas
            const stokFormatted = stokData.stok_formatted;
            qtyFeedback.textContent = `Error! Pemakaian (${qty.toFixed(2).replace('.', ',')} ${satuan}) melebihi stok yang tersedia (${stokFormatted} ${satuan}).`;
            isValid = false;
        } else if (qty > 0 && stokData) {
            // Jika semua valid, tandai input sebagai valid (optional)
            qtyKeluar.classList.add('is-valid');
        }

        // --- 4. Final: Aktifkan/Nonaktifkan Tombol ---
        btnSubmit.disabled = !isValid;
    }

    // Event listeners untuk Form Validasi
    selectKain.addEventListener('change', validateForm);
    qtyKeluar.addEventListener('input', validateForm);
    
    // Panggil saat halaman dimuat
    validateForm(); 
</script>
</body>
</html>
<?php 
mysqli_close($koneksi);
?>