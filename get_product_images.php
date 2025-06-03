<?php
// filepath: /var/www/webserver/get_product_images.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Get product code from request
$product_code = isset($_GET['product_code']) ? mysqli_real_escape_string($conn, $_GET['product_code']) : '';

// Validate product code is provided
if (empty($product_code)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product code is required'
    ]);
    exit;
}

// Query to get images for the specific product, ordered by image_order
$sql = "SELECT * FROM product_images WHERE product_code = '$product_code' ORDER BY image_order ASC";
$hasil = mysqli_query($conn, $sql);

if (!$hasil) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Query gagal dijalankan: ' . mysqli_error($conn),
    ]);
    exit;
}

$result = [];
while ($data = mysqli_fetch_object($hasil)) {
    array_push($result, [
        'id' => $data->id,
        'product_code' => $data->product_code,
        'foto' => $data->foto,
        'image_order' => $data->image_order
    ]);
}

echo json_encode(['result' => $result]);
