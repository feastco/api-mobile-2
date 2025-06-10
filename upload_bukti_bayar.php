<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output
ini_set('log_errors', 1);

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

// Validasi order dengan prepared statement
$sql = "SELECT * FROM orders WHERE id = ? AND payment_method = 'transfer' AND payment_status = 'unpaid'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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

// Coba buat direktori dengan permission yang tepat - gunakan path absolute
$upload_dir = __DIR__ . '/uploads/bukti_bayar/';

// Buat direktori bertahap
$uploads_parent = __DIR__ . '/uploads/';
if (!is_dir($uploads_parent)) {
    if (!mkdir($uploads_parent, 0755, true)) {
        echo json_encode(['status' => false, 'message' => 'Gagal membuat direktori uploads']);
        exit;
    }
}

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['status' => false, 'message' => 'Gagal membuat direktori bukti_bayar']);
        exit;
    }
}

// Generate nama file unik
$filename = 'bukti_' . $order['order_number'] . '_' . time() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Debug informasi sebelum upload
error_log("Upload Debug - Temp file: " . $file['tmp_name']);
error_log("Upload Debug - Target path: " . $filepath);
error_log("Upload Debug - Directory exists: " . (is_dir($upload_dir) ? 'Yes' : 'No'));
error_log("Upload Debug - Directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No'));
error_log("Upload Debug - Temp file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No'));

// Upload file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Set file permissions
    chmod($filepath, 0644);

    // Update database with prepared statement
    $sql_update = "UPDATE orders SET bukti_bayar = ?, payment_status = 'paid', order_status = 'delivered' WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "si", $filename, $order_id);

    if (mysqli_stmt_execute($stmt_update)) {
        echo json_encode([
            'status' => true,
            'message' => 'Bukti pembayaran berhasil diupload. Pembayaran sedang diproses.',
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
        // Hapus file jika gagal update database
        @unlink($filepath);
        error_log("Database update failed: " . mysqli_error($conn));
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan data ke database']);
    }
    mysqli_stmt_close($stmt_update);
} else {
    $upload_error = error_get_last();
    error_log("Upload failed - Error: " . print_r($upload_error, true));
    error_log("Upload failed - PHP upload_tmp_dir: " . ini_get('upload_tmp_dir'));
    error_log("Upload failed - Current working directory: " . getcwd());

    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengupload file',
        'debug' => [
            'temp_file' => $file['tmp_name'],
            'target_path' => $filepath,
            'dir_exists' => is_dir($upload_dir),
            'dir_writable' => is_writable($upload_dir),
            'temp_exists' => file_exists($file['tmp_name'])
        ]
    ]);
}

// Tutup koneksi database
mysqli_stmt_close($stmt);
mysqli_close($conn);
