<?php
header('Content-Type: application/json');
include "koneksimysql.php";

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['status' => false, 'message' => 'Order ID diperlukan']);
    exit;
}

// Validasi order
$sql = "SELECT * FROM orders WHERE id = $order_id AND payment_method = 'transfer' AND payment_status = 'unpaid'";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['status' => false, 'message' => 'Order tidak ditemukan atau sudah dibayar']);
    exit;
}

$order = mysqli_fetch_assoc($result);

// Validasi file upload
if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => false, 'message' => 'File bukti pembayaran harus diupload']);
    exit;
}

$file = $_FILES['bukti_bayar'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validasi ukuran file
if ($file['size'] > $max_size) {
    echo json_encode(['status' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB']);
    exit;
}

// Validasi tipe file berdasarkan ekstensi file
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png'];

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['status' => false, 'message' => 'Tipe file tidak didukung. Gunakan JPG, JPEG, atau PNG']);
    exit;
}

// Validasi tambahan dengan getimagesize
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['status' => false, 'message' => 'File bukan gambar yang valid']);
    exit;
}

// Coba buat direktori dengan permission yang tepat
$upload_dir = 'uploads/bukti_bayar/';

// Buat direktori bertahap
if (!is_dir('uploads/')) {
    if (!mkdir('uploads/', 0777, true)) {
        echo json_encode(['status' => false, 'message' => 'Gagal membuat direktori uploads']);
        exit;
    }
}

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['status' => false, 'message' => 'Gagal membuat direktori bukti_bayar']);
        exit;
    }
}

// Generate nama file unik
$filename = 'bukti_' . $order['order_number'] . '_' . time() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Upload file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Update database
    $filename_escaped = mysqli_real_escape_string($conn, $filename);
    $sql_update = "UPDATE orders SET bukti_bayar = '$filename_escaped', payment_status = 'paid', order_status = 'delivered' WHERE id = $order_id";

    if (mysqli_query($conn, $sql_update)) {
        echo json_encode([
            'status' => true,
            'message' => 'Bukti pembayaran berhasil diupload. Pembayaran telah dikonfirmasi.',
            'data' => [
                'order_id' => $order_id,
                'order_number' => $order['order_number'],
                'bukti_bayar' => $filename,
                'payment_status' => 'paid',
                'order_status' => 'delivered',
                'file_url' => 'uploads/bukti_bayar/' . $filename
            ]
        ]);
    } else {
        unlink($filepath);
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan data ke database: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Gagal mengupload file']);
}
