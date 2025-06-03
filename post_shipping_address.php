<?php
// filepath: /var/www/webserver/post_shipping_address.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Ambil data dari POST request
$user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($conn, $_POST['user_id']) : '';
$nama_penerima = isset($_POST['nama_penerima']) ? mysqli_real_escape_string($conn, $_POST['nama_penerima']) : '';
$nomor_telepon = isset($_POST['nomor_telepon']) ? mysqli_real_escape_string($conn, $_POST['nomor_telepon']) : '';
$alamat_lengkap = isset($_POST['alamat_lengkap']) ? mysqli_real_escape_string($conn, $_POST['alamat_lengkap']) : '';
$province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : 0;
$province = isset($_POST['province']) ? mysqli_real_escape_string($conn, $_POST['province']) : '';
$city_id = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 0;
$city = isset($_POST['city']) ? mysqli_real_escape_string($conn, $_POST['city']) : '';
$kode_pos = isset($_POST['kode_pos']) ? mysqli_real_escape_string($conn, $_POST['kode_pos']) : '';
$is_default = isset($_POST['is_default']) ? (int)$_POST['is_default'] : 0;

// Validasi data
if (empty($user_id) || empty($nama_penerima) || empty($nomor_telepon) || empty($alamat_lengkap) || empty($province_id) || empty($province) || empty($city_id) || empty($city) || empty($kode_pos)) {
    echo json_encode([
        'status' => false,
        'message' => 'Semua data diperlukan untuk menambahkan alamat pengiriman'
    ]);
    exit;
}

// Jika is_default = 1, ubah semua alamat lain menjadi non-default
if ($is_default === 1) {
    $sql_update_default = "UPDATE shipping_addresses SET is_default = 0 WHERE user_id = '$user_id'";
    mysqli_query($conn, $sql_update_default);
}

// Query untuk menambahkan data ke tabel shipping_addresses
$sql = "INSERT INTO shipping_addresses (user_id, nama_penerima, nomor_telepon, alamat_lengkap, province_id, province, city_id, city, kode_pos, is_default) 
        VALUES ('$user_id', '$nama_penerima', '$nomor_telepon', '$alamat_lengkap', $province_id, '$province', $city_id, '$city', '$kode_pos', $is_default)";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'status' => true,
        'message' => 'Alamat pengiriman berhasil ditambahkan',
        'id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal menambahkan alamat pengiriman: ' . mysqli_error($conn)
    ]);
}
