<?php
// filepath: /var/www/webserver/get_order_history.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Ambil user_id dari parameter GET
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Validasi user_id
if ($user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'User ID diperlukan'
    ]);
    exit;
}

// Query untuk mendapatkan semua order milik user
$order_sql = "SELECT 
                o.id, o.order_number, o.shipping_address_id, 
                o.total_product_amount, o.shipping_cost, o.grand_total,
                o.courier, o.courier_service, o.total_weight,
                o.order_status, o.payment_status, o.payment_method,
                o.created_at, o.updated_at,
                a.nama_penerima, a.nomor_telepon, a.alamat_lengkap,
                a.province, a.city, a.kode_pos
              FROM orders o
              LEFT JOIN shipping_addresses a ON o.shipping_address_id = a.id
              WHERE o.user_id = $user_id
              ORDER BY o.created_at DESC";

$order_result = mysqli_query($conn, $order_sql);

if (!$order_result) {
    echo json_encode([
        'status' => false,
        'message' => 'Error query order: ' . mysqli_error($conn)
    ]);
    exit;
}

$orders = [];

// Loop untuk setiap order
while ($order = mysqli_fetch_assoc($order_result)) {
    $order_id = $order['id'];

    // Query untuk mendapatkan item dalam order ini
    $item_sql = "SELECT 
              id, product_code, product_name, 
              quantity, price, subtotal, total_weight
           FROM order_items
           WHERE order_id = $order_id";

    $item_result = mysqli_query($conn, $item_sql);

    if (!$item_result) {
        echo json_encode([
            'status' => false,
            'message' => 'Error query item: ' . mysqli_error($conn)
        ]);
        exit;
    }

    $items = [];
    while ($item = mysqli_fetch_assoc($item_result)) {
        $items[] = $item;
    }

    // Format tanggal agar lebih user-friendly
    $created_at = date('d M Y H:i', strtotime($order['created_at']));
    $updated_at = date('d M Y H:i', strtotime($order['updated_at']));

    // Tambahkan order ke array orders
    $orders[] = [
        'id' => $order['id'],
        'order_number' => $order['order_number'],
        'order_date' => $created_at,
        'update_date' => $updated_at,
        'status' => [
            'order' => $order['order_status'],
            'payment' => $order['payment_status']
        ],
        'shipping' => [
            'name' => $order['nama_penerima'],
            'phone' => $order['nomor_telepon'],
            'address' => $order['alamat_lengkap'],
            'province' => $order['province'],
            'city' => $order['city'],
            'postal_code' => $order['kode_pos'],
            'courier' => $order['courier'],
            'service' => $order['courier_service'],
            'cost' => (float)$order['shipping_cost'],
            'weight' => (int)$order['total_weight'] // Add weight info
        ],
        'payment' => [
            'method' => $order['payment_method'],
            'subtotal' => (float)$order['total_product_amount'],
            'shipping' => (float)$order['shipping_cost'],
            'total' => (float)$order['grand_total']
        ],
        'items' => $items
    ];
}

echo json_encode([
    'status' => true,
    'data' => $orders
]);