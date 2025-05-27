<?php

define('HOST', 'localhost');
define('USER', 'root');
define('PASSWORD', 'vasco123');
define('DB', 'androiduts');

$conn = mysqli_connect(HOST, USER, PASSWORD, DB) or die('Unable to Connect');
if (!$conn) {
    echo "Koneksi Gagal : " . mysqli_connect_error();
    exit();
}
