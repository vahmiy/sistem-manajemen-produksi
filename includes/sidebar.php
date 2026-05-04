<?php 
// File: includes/sidebar.php
// Berisi markup HTML untuk sidebar navigasi DAN membuka wrapper konten.

// Ambil username dari session
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Tamu';
?>

<div class="border-end" id="sidebar-wrapper">
    <div class="sidebar-heading">FLOWERINDO</div>
    <div class="list-group list-group-flush p-2">
        <a href="dashboard.php" class="list-group-item list-group-item-action active">
            <i class="me-2">📊</i> Dashboard
        </a>
        <a href="add_user.php" class="list-group-item list-group-item-action">
            <i class="me-2">➕</i> Tambah User
        </a>
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
    
    <header class="p-3 mb-4 bg-white border-bottom shadow-sm d-flex justify-content-between align-items-center">
        <button class="btn btn-outline-primary d-lg-none" id="sidebarToggle">
            <i class="me-2">☰</i> Menu
        </button>
        <h4 class="d-none d-lg-block my-0">Dashboard Utama</h4> <div>
            <span class="text-secondary me-3 d-none d-sm-inline">Halo, <?php echo $username; ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </header>
    
    <div class="container-fluid p-4 p-lg-5"> 
    </div>

</div>