<?php
include "koneksimysql.php";
header('content-type: application/json');

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
$weight = mysqli_real_escape_string($conn, $_POST['weight']); // Add weight field
$foto = mysqli_real_escape_string($conn, $_POST['foto']);
$deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

$getstatus = 0;
$getresult = 0;
$message = "";

// Check if kode already exists
$sql = "SELECT * FROM tbl_product WHERE kode = '$kode'";
$hasil = mysqli_query($conn, $sql);

if (mysqli_num_rows($hasil) > 0) {
    $getstatus = 0;
    $message = "Product dengan kode ini sudah terdaftar";
} else {
    $getstatus = 1;
    // Insert new product
    $sql = "INSERT INTO tbl_product (kode, merk, kategori, satuan, hargabeli, diskonbeli, hargapokok, hargajual, diskonjual, stok, weight, foto, deskripsi, visit) 
            VALUES ('$kode', '$merk', '$kategori', '$satuan', '$hargabeli', '$diskonbeli', '$hargapokok', '$hargajual', '$diskonjual', '$stok', '$weight', '$foto', '$deskripsi', 0)";

    if (mysqli_query($conn, $sql)) {
        $getresult = 1;
        $message = "Product berhasil ditambahkan";
    } else {
        $getresult = 0;
        $message = "Gagal menambahkan product: " . mysqli_error($conn);
    }
}

echo json_encode(array('status' => $getstatus, 'result' => $getresult, 'message' => $message));
