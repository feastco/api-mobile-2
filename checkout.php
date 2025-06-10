<?php
header('Content-Type: application/json');
include "koneksimysql.php";

// Menerima data dari POST
$user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($conn, $_POST['user_id']) : '';
$shipping_address_id = isset($_POST['shipping_address_id']) ? mysqli_real_escape_string($conn, $_POST['shipping_address_id']) : '';
$origin_id = isset($_POST['origin_id']) ? mysqli_real_escape_string($conn, $_POST['origin_id']) : '';
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];
$courier = isset($_POST['courier']) ? mysqli_real_escape_string($conn, $_POST['courier']) : '';
$courier_service = isset($_POST['courier_service']) ? mysqli_real_escape_string($conn, $_POST['courier_service']) : '';
$shipping_cost = isset($_POST['shipping_cost']) ? (float)$_POST['shipping_cost'] : 0;
$payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : '';
$lama_kirim = isset($_POST['lama_kirim']) ? mysqli_real_escape_string($conn, $_POST['lama_kirim']) : '';

// Debug: Log semua data yang diterima
error_log("Checkout Data: " . print_r($_POST, true));

// Validasi data
if (
    empty($user_id) || empty($shipping_address_id) || empty($origin_id) ||
    empty($products) || empty($courier) || empty($courier_service) ||
    empty($shipping_cost) || empty($payment_method)
) {
    echo json_encode([
        'status' => false,
        'message' => 'Semua data diperlukan untuk proses checkout'
    ]);
    exit;
}

// Validasi metode pembayaran
if (!in_array($payment_method, ['transfer', 'cod'])) {
    echo json_encode([
        'status' => false,
        'message' => 'Metode pembayaran tidak valid, pilih transfer atau cod'
    ]);
    exit;
}

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // Hitung total produk dan total berat
    $total_product_amount = 0;
    $total_weight = 0;

    foreach ($products as $product) {
        $total_product_amount += $product['price'] * $product['quantity'];

        // Ambil berat produk dari database
        $product_code = mysqli_real_escape_string($conn, $product['product_code']);
        $weight_query = "SELECT weight FROM tbl_product WHERE kode = '$product_code'";
        $weight_result = mysqli_query($conn, $weight_query);

        if ($weight_result && mysqli_num_rows($weight_result) > 0) {
            $weight_data = mysqli_fetch_assoc($weight_result);
            $product_weight = (int)$weight_data['weight'];
            $total_weight += ($product_weight * (int)$product['quantity']);
        }
    }

    // Hitung grand total
    $grand_total = $total_product_amount + $shipping_cost;

    // Generate nomor order
    $order_number = date('ymd') . mt_rand(1000, 9999);
    do {
        $order_number = date('ymd') . mt_rand(1000, 9999);
        $check_sql = "SELECT COUNT(*) as count FROM orders WHERE order_number = '$order_number'";
        $check_result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($check_result);
        $exists = $row['count'] > 0;
    } while ($exists);

    // Tentukan status awal berdasarkan payment method
    if ($payment_method === 'transfer') {
        $order_status = 'pending';  // Menunggu bukti bayar
        $payment_status = 'unpaid';    // Belum dibayar
    } else if ($payment_method === 'cod') {
        $order_status = 'delivered';   // COD langsung delivered
        $payment_status = 'paid';      // COD dianggap sudah paid
    } else {
        $order_status = 'pending';     // Default
        $payment_status = 'unpaid';
    }

    // Debug: Log query sebelum insert
    $sql_order = "INSERT INTO orders (order_number, user_id, shipping_address_id, origin_id, total_product_amount, 
              shipping_cost, grand_total, courier, courier_service, lama_kirim, total_weight, order_status, payment_status, payment_method) 
              VALUES ('$order_number', $user_id, $shipping_address_id, $origin_id, $total_product_amount, 
              $shipping_cost, $grand_total, '$courier', '$courier_service', '$lama_kirim', $total_weight, 
              '$order_status', '$payment_status', '$payment_method')";

    error_log("SQL Order Query: " . $sql_order);

    if (!mysqli_query($conn, $sql_order)) {
        throw new Exception("Error creating order: " . mysqli_error($conn));
    }

    $order_id = mysqli_insert_id($conn);
    error_log("Order ID created: " . $order_id);

    if (!$order_id) {
        throw new Exception("Failed to get order ID after insert");
    }

    // Insert ke tabel order_items
    foreach ($products as $product) {
        $product_code = mysqli_real_escape_string($conn, $product['product_code']);
        $product_name = mysqli_real_escape_string($conn, $product['product_name']);
        $quantity = (int)$product['quantity'];
        $price = (float)$product['price'];
        $subtotal = $quantity * $price;

        // Ambil berat produk
        $weight_query = "SELECT weight FROM tbl_product WHERE kode = '$product_code'";
        $weight_result = mysqli_query($conn, $weight_query);
        $product_weight = 0;

        if ($weight_result && mysqli_num_rows($weight_result) > 0) {
            $weight_data = mysqli_fetch_assoc($weight_result);
            $product_weight = (int)$weight_data['weight'];
        }

        $item_weight = $product_weight * $quantity;

        $sql_item = "INSERT INTO order_items (order_id, product_code, product_name, quantity, price, subtotal, total_weight) 
            VALUES ($order_id, '$product_code', '$product_name', $quantity, $price, $subtotal, $item_weight)";

        error_log("SQL Item Query: " . $sql_item);

        if (!mysqli_query($conn, $sql_item)) {
            throw new Exception("Error adding order item: " . mysqli_error($conn));
        }

        // Update stok produk - pindahkan ke dalam try block
        $sql_update_stock = "UPDATE tbl_product SET stok = stok - $quantity WHERE kode = '$product_code'";
        if (!mysqli_query($conn, $sql_update_stock)) {
            throw new Exception("Error updating stock: " . mysqli_error($conn));
        }
    }

    // Commit transaksi jika semua operasi berhasil
    mysqli_commit($conn);

    $response = [
        'status' => true,
        'message' => 'Order berhasil dibuat',
        'data' => [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'total_product_amount' => $total_product_amount,
            'shipping_cost' => $shipping_cost,
            'grand_total' => $grand_total,
            'total_weight' => $total_weight,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'order_status' => $order_status,
            'lama_kirim' => $lama_kirim,
            'upload_bukti_required' => ($payment_method === 'transfer') // Info apakah perlu upload bukti
        ]
    ];

    // Tambahkan info pembayaran untuk transfer
    if ($payment_method === 'transfer') {
        $response['payment_info'] = [
            'bank_name' => 'BCA',
            'account_number' => '0912256378221',
            'account_name' => 'Elisha Mart',
            'amount' => $grand_total,
            'instructions' => 'Silakan transfer sesuai nominal yang tertera dan upload bukti pembayaran'
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    // Rollback transaksi jika terjadi kesalahan
    mysqli_rollback($conn);

    error_log("Checkout Error: " . $e->getMessage());

    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
