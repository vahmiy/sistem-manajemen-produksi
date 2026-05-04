<?php
// 1. Memulai session
session_start();

// 2. Mengimpor file koneksi database
include 'koneksi.php';

// Cek apakah data form telah dikirimkan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Menerima data username dan password
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 4. Sanitasi data untuk mencegah SQL Injection
    // Menggunakan real_escape_string untuk keamanan dasar
    $username = mysqli_real_escape_string($koneksi, $username);
    
    // Query untuk mencari pengguna berdasarkan username
    $query = "SELECT * FROM user WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    // Cek apakah pengguna ditemukan
    if (mysqli_num_rows($result) === 1) {
        $user_data = mysqli_fetch_assoc($result);
        
        // 6. Verifikasi Password
        // ASUMSI: Password di database TERSIMPAN DALAM BENTUK HASH menggunakan password_hash().
        // Jika password di database Anda masih plain text, ganti kondisi if di bawah dengan:
        // if ($password == $user_data['password'])
        // if (password_verify($password, $user_data['password']) -> code lama
        
        if ($password == $user_data['password']) {
            
            // Login Berhasil!
            
            // Atur variabel session
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['level'] = $user_data['level']; // Simpan level akses jika ada

            // 7. Redirect ke halaman dashboard
            header("Location: ../hal/dashboard.php");
            exit;
            
        } else {
            // Password salah
            $_SESSION['login_error'] = "Password salah. Silakan coba lagi.";
            header("Location: ../index.php");
            exit;
        }
        
    } else {
        // Username tidak ditemukan
        $_SESSION['login_error'] = "Username tidak ditemukan.";
        header("Location: ../index.php");
        exit;
    }
    
} else {
    // Jika diakses langsung tanpa POST, arahkan kembali ke halaman login
    header("Location: ../index.php");
    exit;
}

// Tutup koneksi database
mysqli_close($koneksi);
?>