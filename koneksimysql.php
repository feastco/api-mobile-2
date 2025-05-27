<?php

define('HOST', 'localhost');
define('USER', 'androidfisco');
define('PASSWORD', 'Fisco123');
define('DB', 'androidfisco');

$conn = mysqli_connect(HOST, USER, PASSWORD, DB) or die('Unable to Connect');
if (!$conn) {
    echo "Koneksi Gagal : " . mysqli_connect_error();
    exit();
}
