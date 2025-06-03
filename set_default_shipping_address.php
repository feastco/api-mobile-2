<?php
// filepath: /var/www/webserver/set_default_shipping_address.php
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

// Mulai transaksi untuk update data
mysqli_begin_transaction($conn);

try {
    // Nonaktifkan semua alamat default untuk user ini
    $reset_sql = "UPDATE shipping_addresses SET is_default = 0 WHERE user_id = $user_id";
    if (!mysqli_query($conn, $reset_sql)) {
        throw new Exception("Gagal mereset alamat default");
    }
    
    // Set alamat yang dipilih sebagai default
    $update_sql = "UPDATE shipping_addresses SET is_default = 1 WHERE id = $id AND user_id = $user_id";
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Gagal mengatur alamat sebagai default");
    }
    
    // Commit transaksi jika berhasil
    mysqli_commit($conn);
    
    echo json_encode([
        'status' => true,
        'message' => 'Alamat berhasil diatur sebagai default'
    ]);
    
} catch (Exception $e) {
    // Rollback jika terjadi error
    mysqli_rollback($conn);
    
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>