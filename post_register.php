<?php
include "koneksimysql.php";
header('content-type: application/json');

$email = mysqli_real_escape_string($conn, $_POST['email']);
$nama = mysqli_real_escape_string($conn, $_POST['nama']);
$username = mysqli_real_escape_string($conn, $_POST['username']);  // Add username field
$password = mysqli_real_escape_string($conn, $_POST['password']);

$getstatus = 0;
$getresult = 0;
$message = "";

// Check if email or username already exists
$sql = "SELECT * FROM tb_user WHERE email = '$email' OR username = '$username'";
$hasil = mysqli_query($conn, $sql);

if (mysqli_num_rows($hasil) > 0) {
    $getstatus = 0;
    $message = "User sudah terdaftar";
} else {
    $getstatus = 1;
    // Insert new user
    $sql = "INSERT INTO tb_user (nama, username, email, password) 
            VALUES ('$nama', '$username', '$email', md5('$password'))";

    if (mysqli_query($conn, $sql)) {
        $getresult = 1;
        $message = "Register berhasil";
    } else {
        $getresult = 0;
        $message = "Register gagal : " . mysqli_error($conn);
    }
}

echo json_encode(array('status' => $getstatus, 'result' => $getresult, 'message' => $message));
