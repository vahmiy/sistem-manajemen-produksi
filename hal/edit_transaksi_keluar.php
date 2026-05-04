<?php
// flower/hal/edit_transaksi_keluar.php

session_start();
include '../class/koneksi.php'; 
$title = "Edit Transaksi Pemakaian Kain"; 
$username = htmlspecialchars($_SESSION['username']);

// 1. Keamanan & Ambil ID
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php"); exit;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: data_mutasi_kain.php"); exit;
}
$id_transaksi = mysqli_real_escape_string($koneksi, $_GET['id']);
$message = null;
$message_type = null;

// 2. PROSES UPDATE DATA (POST)
if (isset($_POST['submit_edit'])) {
    $id_transaksi_post = mysqli_real_escape_string($koneksi, $_POST['id_keluar']);
    $nama_kain_baru = mysqli_real_escape_string($koneksi, $_POST['nama_kain']);
    $kuantitas_baru = (float)$_POST['jumlah_potong_yard'];
    $tgl_potong_baru = mysqli_real_escape_string($koneksi, $_POST['tgl_potong']);
    $id_order_baru = mysqli_real_escape_string($koneksi, $_POST['id_order']); // Keterangan

    $berhasil = false;

    mysqli_begin_transaction($koneksi);
    try {
        // A. Ambil Data LAMA untuk perhitungan stok
        $sql_get_old = "SELECT nama_kain, jumlah_potong_yard FROM transaksi_keluar WHERE id_keluar = '{$id_transaksi_post}'";
        $result_old = mysqli_query($koneksi, $sql_get_old);
        $data_lama = mysqli_fetch_assoc($result_old);
        
        if (!$data_lama) {
            throw new Exception("Data lama tidak ditemukan.");
        }
        $nama_kain_lama = mysqli_real_escape_string($koneksi, $data_lama['nama_kain']);
        $kuantitas_lama = $data_lama['jumlah_potong_yard']; // Nilai lama yang dikeluarkan

        // B. Hitung Selisih Stok (nilai lama dikeluarkan, nilai baru dikeluarkan)
        // Jika kuantitas_baru > kuantitas_lama, stok harus dikurangi lagi (selisih > 0)
        // Jika kuantitas_baru < kuantitas_lama, stok harus ditambah (selisih < 0)
        $selisih_stok = $kuantitas_baru - $kuantitas_lama;
        
        // C. Update Transaksi Keluar
        $sql_update = "
            UPDATE transaksi_keluar SET
            nama_kain = '{$nama_kain_baru}',
            jumlah_potong_yard = {$kuantitas_baru},
            tgl_potong = '{$tgl_potong_baru}',
            id_order = '{$id_order_baru}'
            WHERE id_keluar = '{$id_transaksi_post}'
        ";
        if (!mysqli_query($koneksi, $sql_update)) {
            throw new Exception("Gagal update transaksi: " . mysqli_error($koneksi));
        }

        // D. Update Stok Master
        if ($selisih_stok != 0) {
             // 1. Kembalikan stok lama (jika nama kain berubah)
            if ($nama_kain_baru !== $nama_kain_lama) {
                // Tambah kembali stok lama (ke kain yang lama)
                $sql_rollback_stok = "UPDATE stok_master SET stok_saat_ini = stok_saat_ini + {$kuantitas_lama} WHERE nama_kain = '{$nama_kain_lama}'";
                if (!mysqli_query($koneksi, $sql_rollback_stok)) {
                    throw new Exception("Gagal rollback stok lama: " . mysqli_error($koneksi));
                }
                // Kurangi stok baru (dari kain yang baru)
                $sql_update_stok_new = "UPDATE stok_master SET stok_saat_ini = stok_saat_ini - {$kuantitas_baru} WHERE nama_kain = '{$nama_kain_baru}'";
                if (!mysqli_query($koneksi, $sql_update_stok_new)) {
                    throw new Exception("Gagal update stok baru: " . mysqli_error($koneksi));
                }
            } else {
                // Jika nama kain sama, stok di-update dengan nilai NEGATIF dari selisih
                // Jika selisih_stok positif (keluar lebih banyak), maka dikurangi lagi.
                // Jika selisih_stok negatif (keluar lebih sedikit), maka ditambah.
                $sql_update_stok = "UPDATE stok_master SET stok_saat_ini = stok_saat_ini - {$selisih_stok} WHERE nama_kain = '{$nama_kain_baru}'";
                if (!mysqli_query($koneksi, $sql_update_stok)) {
                    throw new Exception("Gagal update stok: " . mysqli_error($koneksi));
                }
            }
        }
        
        mysqli_commit($koneksi);
        $message = "Perubahan pada Transaksi Keluar ID **{$id_transaksi_post}** berhasil disimpan.";
        $message_type = "success";
        $berhasil = true;
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $message = "Gagal Update! " . $e->getMessage();
        $message_type = "danger";
    }

    // Setelah update, kita perlu memuat ulang data terbaru ke dalam form
    if ($berhasil) {
        $sql_select = "SELECT * FROM transaksi_keluar WHERE id_keluar = '{$id_transaksi_post}'";
    }
}

// 3. Ambil data untuk Form (Awalnya dari GET, atau setelah POST berhasil)
if (!isset($sql_select)) {
    $sql_select = "SELECT * FROM transaksi_keluar WHERE id_keluar = '{$id_transaksi}'";
}
$result_select = mysqli_query($koneksi, $sql_select);
$data_form = mysqli_fetch_assoc($result_select);

if (!$data_form) {
    $_SESSION['status_message'] = "Data Transaksi Keluar ID {$id_transaksi} tidak ditemukan.";
    $_SESSION['status_type'] = "danger";
    header("Location: data_mutasi_kain.php");
    exit;
}

// Query untuk Filter/Nama Kain
$sql_unique_kain = "SELECT DISTINCT nama_kain FROM stok_master ORDER BY nama_kain ASC";
$result_unique_kain = mysqli_query($koneksi, $sql_unique_kain);
if (!$result_unique_kain) {
    die("Query Database Error (Stok Master): " . mysqli_error($koneksi));
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?> | Flowerindo</title>
    <link href="../dist/css/bootstrap.min.css" rel="stylesheet"> 
    </head>
<body>

<div class="d-flex" id="wrapper">
    <div id="page-content-wrapper">
        <div class="container-fluid p-4 p-lg-5">
            <h2 class="mb-4"><?php echo $title; ?></h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <span class="fw-bold">Form Edit Pemakaian Kain (ID: <?php echo htmlspecialchars($data_form['id_keluar']); ?>)</span>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="row g-3">
                        <input type="hidden" name="id_keluar" value="<?php echo htmlspecialchars($data_form['id_keluar']); ?>">
                        
                        <div class="col-md-6">
                            <label for="tgl_potong" class="form-label">Tanggal Pemotongan <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tgl_potong" name="tgl_potong" value="<?php echo htmlspecialchars($data_form['tgl_potong']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="nama_kain" class="form-label">Jenis Kain <span class="text-danger">*</span></label>
                            <select class="form-select" id="nama_kain" name="nama_kain" required>
                                <?php 
                                mysqli_data_seek($result_unique_kain, 0); 
                                while($kain = mysqli_fetch_assoc($result_unique_kain)) {
                                    $selected = ($kain['nama_kain'] === $data_form['nama_kain']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($kain['nama_kain']) . '" ' . $selected . '>' . htmlspecialchars($kain['nama_kain']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="jumlah_potong_yard" class="form-label">Jumlah Pemakaian (Yard) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="jumlah_potong_yard" name="jumlah_potong_yard" value="<?php echo htmlspecialchars($data_form['jumlah_potong_yard']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="id_order" class="form-label">ID PO/Order (Keterangan)</label>
                            <input type="text" class="form-control" id="id_order" name="id_order" value="<?php echo htmlspecialchars($data_form['id_order']); ?>">
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" name="submit_edit" class="btn btn-primary me-2">Simpan Perubahan</button>
                            <a href="data_mutasi_kain.php" class="btn btn-secondary">Batal / Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../dist/js/bootstrap.bundle.min.js"></script> 
</body>
</html>
<?php mysqli_close($koneksi); ?>