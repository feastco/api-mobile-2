<?php
// filepath: /var/www/webserver/delete_shipping_address.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Ambil ID alamat dan user_id untuk validasi
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validasi input
if ($id <= 0 || $user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ID alamat dan user ID diperlukan'
    ]);
    exit;
}

// Cek apakah alamat milik user yang bersangkutan
$check_sql = "SELECT * FROM shipping_addresses WHERE id = $id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Alamat tidak ditemukan atau bukan milik user ini'
    ]);
    exit;
}

$address = mysqli_fetch_assoc($check_result);
$is_default = $address['is_default'];

// Hapus alamat
$delete_sql = "DELETE FROM shipping_addresses WHERE id = $id AND user_id = $user_id";
$result = mysqli_query($conn, $delete_sql);

if (!$result) {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal menghapus alamat: ' . mysqli_error($conn)
    ]);
    exit;
}

// Jika alamat yang dihapus adalah default, set alamat lain sebagai default (jika ada)
if ($is_default == 1) {
    $update_default_sql = "UPDATE shipping_addresses SET is_default = 1 
                          WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1";
    mysqli_query($conn, $update_default_sql);
}

echo json_encode([
    'status' => true,
    'message' => 'Alamat berhasil dihapus'
]);
