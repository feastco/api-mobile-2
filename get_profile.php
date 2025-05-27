<?php
include "koneksimysql.php";
header('content-type: application/json');

$username = $_GET['username'];
$datauser = array();
$getstatus = 0;

$sql = "SELECT * FROM tb_user WHERE username = '" . $username . "'";
$hasil = mysqli_query($conn, $sql);
$data = mysqli_fetch_object($hasil);
if (!$data) {
    $getstatus = 0;
} else {
    $getstatus = 1;

    // Check if photo is null or empty, if so use default
    // if ($data->foto === null || empty($data->foto)) {
    //     $fotoUrl = 'http://192.168.1.8/webserver/images/default.jpg';
    // } else {
    //     // If user has a photo, check if it needs base URL
    //     if (!preg_match('/^https?:\/\//', $data->foto)) {
    //         $fotoUrl = 'http://192.168.1.8/webserver/images/' . $data->foto;
    //     } else {
    //         $fotoUrl = $data->foto;
    //     }
    // }

    $datauser = array(
        'id' => $data->id,
        'email' => $data->email,
        'nama' => $data->nama,
        'username' => $data->username,
        'alamat' => $data->alamat,
        'kota' => $data->kota,
        'provinsi' => $data->provinsi,
        'kodepos' => $data->kodepos,
        'telp' => $data->telp,
        'foto' => $data->foto
    );
}

echo json_encode(array('result' => $getstatus, 'data' => $datauser));
?>