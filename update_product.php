<?php
include "koneksimysql.php";
header('Content-Type: application/json');

// Check if the request is a POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and sanitize data
$kode = mysqli_real_escape_string($conn, $_POST['kode']);
$merk = mysqli_real_escape_string($conn, $_POST['merk']);
$kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
$satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
$hargabeli = mysqli_real_escape_string($conn, $_POST['hargabeli']);
$diskonbeli = mysqli_real_escape_string($conn, $_POST['diskonbeli']);
$hargapokok = mysqli_real_escape_string($conn, $_POST['hargapokok']);
$hargajual = mysqli_real_escape_string($conn, $_POST['hargajual']);
$diskonjual = mysqli_real_escape_string($conn, $_POST['diskonjual']);
$stok = mysqli_real_escape_string($conn, $_POST['stok']);
$weight = mysqli_real_escape_string($conn, $_POST['weight']); // Include weight field
$foto = mysqli_real_escape_string($conn, $_POST['foto']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

// Validate required fields
if (empty($kode)) {
    echo json_encode([
        'status' => false,
        'message' => 'Kode produk diperlukan'
    ]);
    exit;
}

// Check if product exists
$sql_check = "SELECT kode FROM tbl_product WHERE kode = '$kode'";
$result_check = mysqli_query($conn, $sql_check);

if (!$result_check || mysqli_num_rows($result_check) === 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Produk tidak ditemukan'
    ]);
    exit;
}

// Update product
$sql_update = "UPDATE tbl_product SET 
               merk = '$merk',
               kategori = '$kategori',
               satuan = '$satuan',
               hargabeli = '$hargabeli',
               diskonbeli = '$diskonbeli',
               hargapokok = '$hargapokok',
               hargajual = '$hargajual',
               diskonjual = '$diskonjual',
               stok = '$stok',
               weight = '$weight',
               foto = '$foto',
               deskripsi = '$deskripsi'
               WHERE kode = '$kode'";

if (mysqli_query($conn, $sql_update)) {
    echo json_encode([
        'status' => true,
        'message' => 'Produk berhasil diupdate'
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal update produk: ' . mysqli_error($conn)
    ]);
}
