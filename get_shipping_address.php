<?php
// filepath: /var/www/webserver/get_shipping_address.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Ambil parameter user_id dari request
$user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($conn, $_GET['user_id']) : '';

if (empty($user_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'User ID diperlukan untuk mendapatkan data alamat pengiriman'
    ]);
    exit;
}

// Query untuk mendapatkan data alamat pengiriman berdasarkan user_id
$sql = "SELECT * FROM shipping_addresses WHERE user_id = '$user_id' ORDER BY is_default DESC, created_at DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        'status' => false,
        'message' => 'Query gagal dijalankan: ' . mysqli_error($conn),
    ]);
    exit;
}

$addresses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $addresses[] = $row;
}

// Response JSON
echo json_encode([
    'status' => true,
    'data' => $addresses
]);
