<?php
include "koneksimysql.php";
header('Content-Type: application/json');

$identifier = mysqli_real_escape_string($conn, $_POST['identifier']); // Can be email or username
$password = mysqli_real_escape_string($conn, $_POST['password']);
$datauser = array();
$getstatus = 0;

// Check if the identifier is an email or username
if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    // Login with email
    $sql = "SELECT * FROM tb_user WHERE email='" . $identifier . "' AND password=md5('" . $password . "')";
} else {
    // Login with username
    $sql = "SELECT * FROM tb_user WHERE username='" . $identifier . "' AND password=md5('" . $password . "')";
}

$hasil = mysqli_query($conn, $sql);
$data = mysqli_fetch_object($hasil);

if (!$data) {
    $getstatus = 0;
} else {
    $getstatus = 1;
    $datauser = array(
        'username' => $data->username,
        'nama' => $data->nama,
        'email' => $data->email,
        'foto' => $data->foto,
        'id' => $data->id  // I recommend keeping the ID for reference
    );
}

echo json_encode(array('result' => $getstatus, 'data' => $datauser));
