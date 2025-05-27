<?php
include "koneksimysql.php";
header('content-type: application/json');

$email = $_POST['email'];
$nama = $_POST['nama'];
$username = $_POST['username']; // Added username field
$alamat = $_POST['alamat'];
$kota = $_POST['kota'];
$provinsi = $_POST['provinsi'];
$telp = $_POST['telp'];
$kodepos = $_POST['kodepos'];
$foto = isset($_POST['foto']) ? $_POST['foto'] : NULL; // Added foto field

$getresult = 0;
$message = "";

$sql = "UPDATE tb_user SET 
        nama = '" . $nama . "', 
        email = '" . $email . "', 
        alamat = '" . $alamat . "', 
        kota = '" . $kota . "', 
        provinsi = '" . $provinsi . "', 
        telp = '" . $telp . "', 
        kodepos = '" . $kodepos . "',
        foto = '" . $foto . "'
        WHERE username = '" . $username . "'";

$hasil = mysqli_query($conn, $sql);
if ($hasil) {
    $getresult = 1;
    $message = "Update berhasil";
} else {
    $getresult = 0;
    $message = "Update gagal : " . mysqli_error($conn);
}

echo json_encode(array('result' => $getresult, 'message' => $message));
