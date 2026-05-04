<?php
// LOKASI FILE: /flower/hal/data_po.php

session_start();
// Pastikan path ke file koneksi Anda sudah benar
include '../class/koneksi.php'; 
$title = "Daftar Purchase Order"; 
$current_page_menu = "data_po.php";
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// Cek status login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); exit;
}

// Ambil pesan status dari sesi (jika ada)
$message = isset($_SESSION['status_message']) ? $_SESSION['status_message'] : null;
$message_type = isset($_SESSION['status_type']) ? $_SESSION['status_type'] : null;
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// ==========================================================
// 1. LOGIKA FILTER & PENGAMBILAN DATA FILTER (Kode PHP Anda sebelumnya)
// ==========================================================
$filter_status = $_GET['status'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$filter_kain = $_GET['kain'] ?? '';
$filter_tgl_start = $_GET['tgl_start'] ?? '';
$filter_tgl_end = $_GET['tgl_end'] ?? '';

$params = [];
$types = '';
$where_clauses = [];

// VALIDASI SISI SERVER: Tgl Sampai tidak boleh lebih awal dari Tgl Dari
if (!empty($filter_tgl_start) && !empty($filter_tgl_end)) {
    if (strtotime($filter_tgl_end) < strtotime($filter_tgl_start)) {
        $message = "Tanggal Sampai tidak boleh lebih awal dari Tanggal Dari. Filter tanggal direset.";
        $message_type = "danger";
        $filter_tgl_end = '';
        $filter_tgl_start = '';
    }
}

// Filter: Status
if (!empty($filter_status)) {
    $where_clauses[] = "o.status_order = ?";
    $params[] = $filter_status;
    $types .= 's';
}

// Filter: Pencarian Teks (ID PO Unik atau Nama Client)
if (!empty($filter_search)) {
    $where_clauses[] = "(o.id_po_unik LIKE ? OR c.nama_client LIKE ?)";
    $search_term = "%" . $filter_search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

// Filter: Kain
if (!empty($filter_kain)) {
    $where_clauses[] = "o.nama_kain = ?";
    $params[] = $filter_kain;
    $types .= 's';
}

// Filter: Tanggal Mulai
if (!empty($filter_tgl_start)) {
    $where_clauses[] = "DATE(o.tgl_po_dibuat) >= ?";
    $params[] = $filter_tgl_start;
    $types .= 's';
}

// Filter: Tanggal Selesai
if (!empty($filter_tgl_end)) {
    $where_clauses[] = "DATE(o.tgl_po_dibuat) <= ?";
    $params[] = $filter_tgl_end;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Ambil semua nama kain yang unik untuk dropdown filter
$sql_kain_list = "SELECT DISTINCT nama_kain FROM orders WHERE nama_kain IS NOT NULL AND nama_kain != '' ORDER BY nama_kain ASC";
$result_kain_list = mysqli_query($koneksi, $sql_kain_list);
$kain_list = [];
if ($result_kain_list) {
    while($row = mysqli_fetch_assoc($result_kain_list)) {
        $kain_list[] = $row['nama_kain'];
    }
}

// ==========================================================
// 2. LOGIKA PAGINATION & QUERY UTAMA (Kode PHP Anda sebelumnya)
// ==========================================================
$data_per_halaman = 20;
$halaman_saat_ini = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_saat_ini < 1) $halaman_saat_ini = 1;

// Query untuk menghitung total baris
$sql_count = "SELECT COUNT(*) AS total_data FROM orders o JOIN clients c ON o.id_client = c.id_client" . $where_sql;

$stmt_count = mysqli_prepare($koneksi, $sql_count);
if ($stmt_count === false) { die("MySQLi prepare error for count: " . mysqli_error($koneksi)); }

if (count($params) > 0) {
    $refs_count = [];
    foreach ($params as $key => $value) { $refs_count[$key] = &$params[$key]; }
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_count, $types], $refs_count));
}
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total_data'];
mysqli_stmt_close($stmt_count);

$total_halaman = ceil($total_data / $data_per_halaman);
if ($halaman_saat_ini > $total_halaman && $total_halaman > 0) { $halaman_saat_ini = $total_halaman; } elseif ($total_halaman == 0) { $halaman_saat_ini = 1; }
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;
if ($offset < 0) $offset = 0;


// Query Utama untuk Daftar PO
$sql_po = "
    SELECT 
        o.*, 
        c.nama_client, 
        (SELECT SUM(jumlah_potong_yard) FROM transaksi_keluar tk WHERE tk.id_order = o.id_order) AS total_dipakai
    FROM orders o
    JOIN clients c ON o.id_client = c.id_client
    " . $where_sql . "
    ORDER BY o.tgl_po_dibuat DESC, o.id_order DESC
    LIMIT ?, ?
";

$stmt_po = mysqli_prepare($koneksi, $sql_po);
if ($stmt_po === false) { die("MySQLi prepare error for data: " . mysqli_error($koneksi)); }

$types_po = $types . 'ii';
$params_po = array_merge($params, [$offset, $data_per_halaman]);

$refs = [];
foreach ($params_po as $key => $value) { $refs[$key] = &$params_po[$key]; }

if (count($params_po) > 0) {
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_po, $types_po], $refs));
}

mysqli_stmt_execute($stmt_po);
$result_po = mysqli_stmt_get_result($stmt_po);
mysqli_stmt_close($stmt_po);


// Query untuk Rangkuman Status (Count)
$sql_status_count = "
    SELECT status_order, COUNT(*) as total 
    FROM orders 
    GROUP BY status_order
";
$result_status_count = mysqli_query($koneksi, $sql_status_count);
$status_counts = [];
while ($row = mysqli_fetch_assoc($result_status_count)) {
    $status_counts[$row['status_order']] = $row['total'];
}

$badges = ['Pending' => 'warning', 'Proses' => 'info', 'Selesai' => 'success', 'Batal' => 'secondary'];

// Tutup koneksi setelah semua data diambil
mysqli_close($koneksi);

// Fungsi untuk membuat class 'active'
function is_active($page, $current) {
    return ($page == $current) ? 'active' : '';
}

// Fungsi untuk membuat URL pagination/filter
function build_pagination_url($page, $status, $search, $kain, $tgl_start, $tgl_end) {
    $url = "?halaman=" . $page;
    if (!empty($status)) { $url .= "&status=" . urlencode($status); }
    if (!empty($search)) { $url .= "&search=" . urlencode($search); }
    if (!empty($kain)) { $url .= "&kain=" . urlencode($kain); }
    if (!empty($tgl_start)) { $url .= "&tgl_start=" . urlencode($tgl_start); }
    if (!empty($tgl_end)) { $url .= "&tgl_end=" . urlencode($tgl_end); }
    return $url;
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?> | Flowerindo</title>
    
    <link href="../dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLMDJ48bU8jJk5QfF+S0k4eYQ7uP/X+J6M5S8K8s7k5nF5F5Q5Q5Q5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        :root {
            --tblr-primary: #588f99; 
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden; 
        }
        #wrapper { display: flex; }
        #sidebar-wrapper {
            min-height: 100vh;
            transition: margin .25s ease-out; 
            background-color: #ffffff;
            box-shadow: 1px 0 10px rgba(0, 0, 0, 0.05);
            width: 18rem; 
            z-index: 1030;
        }
        /* ... (CSS sidebar lainnya tetap sama) ... */
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
            cursor: pointer;
        }
        #sidebar-wrapper .list-group-item.sidebar-sub-item {
            padding-left: 2.5rem;
        }
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
        /* Tampilan tambahan untuk tombol Print Batch */
        #btn-print-batch {
            transition: opacity 0.3s ease;
        }
        .table-checkbox-col {
            width: 1%; /* Kolom checkbox kecil */
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">FLOWERINDO</div>
        <div class="list-group list-group-flush p-2">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo is_active('dashboard.php', $current_page_menu); ?>">
                <i class="me-2 fas fa-chart-line"></i> Dashboard
            </a>
            
            <div class="list-group-item">📝 Purchase Order (PO)</div>
            <a href="data_po.php" class="list-group-item list-group-item-action sidebar-sub-item <?php echo is_active('data_po.php', $current_page_menu); ?>">
                → Data PO
            </a>
            <a href="tambah_po.php" class="list-group-item list-group-item-action sidebar-sub-item <?php echo is_active('tambah_po.php', $current_page_menu); ?>">
                → Input PO Baru
            </a>
            
            <a href="tambah_client.php" class="list-group-item list-group-item-action">
                <i class="me-2 fas fa-users"></i> Data Client
            </a>
            <div class="list-group-item">🧵 Bahan Baku Kain</div>
            <a href="tambah_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Catat Pemakaian</a>
            <a href="data_mutasi_kain.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Histori Kain</a>
            
            <div class="list-group-item">📄 Bahan Baku Kertas</div>
            <a href="tambah_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Tambah Stok Baru</a>
            <a href="tambah_pemakaian_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Catat Pemakaian</a>
            <a href="data_transaksi_kertas.php" class="list-group-item list-group-item-action sidebar-sub-item">→ Histori Transaksi</a>
            
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2 fas fa-box"></i> Data Produk
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2 fas fa-chart-area"></i> Laporan Penjualan
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                <i class="me-2 fas fa-cog"></i> Pengaturan
            </a>
        </div>
    </div>
    <div id="page-content-wrapper">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-outline-primary" id="sidebarToggle">
                    <i class="me-2 fas fa-bars"></i> Menu
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
        <div class="container-fluid p-4 p-lg-5">
            <h2 class="mb-4"><?php echo $title; ?></h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php 
                $sorted_badges = ['Pending' => 'warning', 'Proses' => 'info', 'Selesai' => 'success', 'Batal' => 'secondary'];
                foreach ($sorted_badges as $status => $class):
                ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card bg-<?php echo $class; ?> text-white shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-0"><?php echo $status; ?></h5>
                            <p class="card-text fs-4 fw-bold"><?php echo $status_counts[$status] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="data_po.php" id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filter_search" class="form-label small text-muted">Cari PO / Client</label>
                            <input type="text" class="form-control form-control-sm" id="filter_search" name="search" 
                                placeholder="ID PO atau Nama Client..." value="<?php echo htmlspecialchars($filter_search); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="filter_status" class="form-label small text-muted">Status</label>
                            <select class="form-select form-select-sm" id="filter_status" name="status">
                                <option value="">Semua</option>
                                <?php 
                                $all_statuses = ['Pending', 'Proses', 'Selesai', 'Batal'];
                                foreach ($all_statuses as $status_opt): ?>
                                    <option value="<?php echo $status_opt; ?>" 
                                        <?php echo ($filter_status == $status_opt) ? 'selected' : ''; ?>>
                                        <?php echo $status_opt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="filter_kain" class="form-label small text-muted">Jenis Kain</label>
                            <select class="form-select form-select-sm" id="filter_kain" name="kain">
                                <option value="">Semua Kain</option>
                                <?php foreach ($kain_list as $kain_name): ?>
                                    <option value="<?php echo htmlspecialchars($kain_name); ?>" 
                                        <?php echo ($filter_kain == $kain_name) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kain_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="filter_tgl_start" class="form-label small text-muted">Tgl Dari</label>
                            <input type="date" class="form-control form-control-sm" id="filter_tgl_start" name="tgl_start" 
                                value="<?php echo htmlspecialchars($filter_tgl_start); ?>">
                        </div>

                        <div class="col-md-2">
                            <label for="filter_tgl_end" class="form-label small text-muted">Tgl Sampai</label>
                            <input type="date" class="form-control form-control-sm" id="filter_tgl_end" name="tgl_end" 
                                value="<?php echo htmlspecialchars($filter_tgl_end); ?>">
                            <div class="text-danger small mt-1" id="tglError" style="display: none;"></div>
                        </div>
                        
                        <div class="col-12 d-flex justify-content-end pt-3">
                            <button type="submit" class="btn btn-primary btn-sm me-2">Terapkan Filter</button>
                            <a href="data_po.php" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
                        </div>
                    </form>
                </div>
            </div>
            <button type="button" id="btn-print-batch" class="btn btn-success btn-sm mb-3 shadow-sm" style="display:none;">
                <i class="fas fa-print me-1"></i> Print Batch (<span id="count-selected">0</span> PO)
            </button>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    Daftar Purchase Order (Menampilkan <?php echo $total_data; ?> Data | Halaman <?php echo $halaman_saat_ini; ?> dari <?php echo $total_halaman; ?>)
                    <a href="tambah_po.php" class="btn btn-primary btn-sm">Input PO Baru</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="table-checkbox-col"><input type="checkbox" id="check-all"></th>
                                    <th>Tgl PO</th>
                                    <th>ID PO</th>
                                    <th>Client</th>
                                    <th>Kain</th>
                                    <th>Kebutuhan</th>
                                    <th>Dipakai (Y)</th>
                                    <th>Sisa (Y)</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result_po && mysqli_num_rows($result_po) > 0) {
                                    while($row = mysqli_fetch_assoc($result_po)) {
                                        $status = htmlspecialchars($row['status_order']);
                                        $badge_class = $badges[$status] ?? 'secondary';
                                        $kebutuhan = number_format($row['kebutuhan_panjang'], 2);
                                        $dipakai = number_format($row['total_dipakai'] ?? 0, 2);
                                        $sisa = number_format($row['kebutuhan_panjang'] - ($row['total_dipakai'] ?? 0), 2);
                                        ?>
                                        <tr>
                                            <td><input type="checkbox" name="selected_orders[]" class="check-item" value="<?php echo $row['id_order']; ?>"></td>
                                            
                                            <td><?php echo date('d/m/Y', strtotime($row['tgl_po_dibuat'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['id_po_unik']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_client']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_kain']); ?></td>
                                            <td><?php echo $kebutuhan . " " . $row['satuan_panjang']; ?></td>
                                            <td class="text-success"><?php echo $dipakai; ?></td>
                                            <td class="<?php echo $sisa < 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo $sisa; ?></td>
                                            <td><span class="badge bg-<?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
                                            <td>
                                                <a href="detail_po.php?id=<?php echo $row['id_order']; ?>" class="btn btn-sm btn-info text-white me-1">Detail</a>
                                                <a href="print_po.php?id=<?php echo $row['id_order']; ?>" class="btn btn-sm btn-secondary" target="_blank">Print</a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="11" class="text-center">Tidak ada Purchase Order yang ditemukan sesuai filter.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_halaman > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            
                            <li class="page-item <?php echo ($halaman_saat_ini <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_url($halaman_saat_ini - 1, $filter_status, $filter_search, $filter_kain, $filter_tgl_start, $filter_tgl_end); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <?php 
                            $start = max(1, $halaman_saat_ini - 2);
                            $end = min($total_halaman, $halaman_saat_ini + 2);

                            for ($i = $start; $i <= $end; $i++):
                            ?>
                            <li class="page-item <?php echo ($i == $halaman_saat_ini) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_url($i, $filter_status, $filter_search, $filter_kain, $filter_tgl_start, $filter_tgl_end); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo ($halaman_saat_ini >= $total_halaman) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo build_pagination_url($halaman_saat_ini + 1, $filter_status, $filter_search, $filter_kain, $filter_tgl_start, $filter_tgl_end); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                        <div class="text-center text-muted small">Total Data (Setelah Filter): <?php echo $total_data; ?></div>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <script src="../dist/js/bootstrap.bundle.min.js"></script> 
<script>
    // Toggle Sidebar
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        wrapper.classList.toggle("toggled");
    });
    // Sembunyikan sidebar secara default pada perangkat mobile
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }

    // ==========================================================
    // VALIDASI JAVASCRIPT: Tgl Sampai vs Tgl Dari (Kode Anda sebelumnya)
    // ==========================================================
    const filterForm = document.getElementById('filterForm');
    const tglStartInput = document.getElementById('filter_tgl_start');
    const tglEndInput = document.getElementById('filter_tgl_end');
    const tglErrorDiv = document.getElementById('tglError');

    filterForm.addEventListener('submit', function(event) {
        var tglStart = tglStartInput.value;
        var tglEnd = tglEndInput.value;
        
        tglErrorDiv.style.display = 'none'; 

        if (tglStart && tglEnd) { 
            if (tglEnd < tglStart) {
                event.preventDefault(); 
                tglErrorDiv.innerHTML = 'Tanggal Sampai tidak boleh lebih awal.';
                tglErrorDiv.style.display = 'block';
                tglEndInput.focus();
            }
        }
    });

    tglStartInput.addEventListener('change', function() { tglErrorDiv.style.display = 'none'; });
    tglEndInput.addEventListener('change', function() { tglErrorDiv.style.display = 'none'; });


    // ==========================================================
    // LOGIKA JQUERY UNTUK PRINT BATCH
    // ==========================================================
    $(document).ready(function() {
        var $checkAll = $('#check-all');
        var $checkItems = $('.check-item');
        var $btnPrintBatch = $('#btn-print-batch');
        var $countSelected = $('#count-selected');

        // Fungsi untuk memperbarui tampilan tombol Print Batch
        function updatePrintButton() {
            var checkedCount = $checkItems.filter(':checked').length;
            $countSelected.text(checkedCount);
            
            if (checkedCount > 0) {
                $btnPrintBatch.show();
            } else {
                $btnPrintBatch.hide();
            }
        }
        
        // Inisialisasi status tombol
        updatePrintButton(); 

        // 1. Check/Uncheck semua item
        $checkAll.on('click', function() {
            $checkItems.prop('checked', this.checked);
            updatePrintButton();
        });

        // 2. Perbarui status tombol dan master checkbox saat item individual dicentang
        $checkItems.on('click', function() {
            updatePrintButton();
            
            if ($checkItems.filter(':checked').length === $checkItems.length) {
                $checkAll.prop('checked', true);
            } else {
                $checkAll.prop('checked', false);
            }
        });

        // 3. Aksi saat tombol Print Batch diklik
        $btnPrintBatch.on('click', function() {
            var selectedIds = [];
            $checkItems.filter(':checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                var idsString = selectedIds.join(',');
                
                // Buka halaman print_po.php dengan multiple ID
                // Ini akan memicu kode Batch Print di print_po.php
                window.open('print_po.php?ids=' + encodeURIComponent(idsString), '_blank');
                
                // Opsional: Uncheck semua setelah print
                $checkAll.prop('checked', false);
                $checkItems.prop('checked', false);
                updatePrintButton();
            } else {
                alert("Pilih minimal satu Purchase Order untuk dicetak.");
            }
        });
    });
</script>
</body>
</html>