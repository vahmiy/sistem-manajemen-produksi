<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'flowerindo';

$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>