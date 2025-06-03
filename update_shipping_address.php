<?php
// filepath: /var/www/webserver/update_shipping_address.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Ambil data dari POST request
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
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
if ($id <= 0 || $user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ID alamat dan ID user diperlukan'
    ]);
    exit;
}

if (empty($nama_penerima) || empty($nomor_telepon) || empty($alamat_lengkap) || 
    empty($province_id) || empty($province) || empty($city_id) || empty($city) || empty($kode_pos)) {
    echo json_encode([
        'status' => false,
        'message' => 'Semua data diperlukan untuk update alamat pengiriman'
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
$current_is_default = $address['is_default'];

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // Jika akan diset sebagai default, reset semua alamat user ini terlebih dahulu
    if ($is_default == 1 && $current_is_default == 0) {
        $reset_sql = "UPDATE shipping_addresses SET is_default = 0 WHERE user_id = $user_id";
        if (!mysqli_query($conn, $reset_sql)) {
            throw new Exception("Gagal mereset alamat default");
        }
    }
    
    // Update data alamat
    $update_sql = "UPDATE shipping_addresses SET 
                    nama_penerima = '$nama_penerima',
                    nomor_telepon = '$nomor_telepon',
                    alamat_lengkap = '$alamat_lengkap',
                    province_id = $province_id,
                    province = '$province',
                    city_id = $city_id,
                    city = '$city',
                    kode_pos = '$kode_pos',
                    is_default = $is_default
                    WHERE id = $id AND user_id = $user_id";
                    
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Gagal mengupdate alamat: " . mysqli_error($conn));
    }
    
    // Commit transaksi jika berhasil
    mysqli_commit($conn);
    
    echo json_encode([
        'status' => true,
        'message' => 'Alamat berhasil diupdate'
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