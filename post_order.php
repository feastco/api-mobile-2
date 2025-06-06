<?php
// filepath: /var/www/webserver/post_order.php
include "koneksimysql.php";
header('Content-Type: application/json');

// Decode input JSON
$data = json_decode(file_get_contents("php://input"), true);
$items = isset($data['items']) ? $data['items'] : null;

// Validasi input
if (empty($items) || !is_array($items)) {
    echo json_encode(['result' => 0, 'message' => 'Data item tidak valid atau kosong']);
    exit;
}

$getresult = 0;
$message = "";

// Mulai transaksi database
mysqli_begin_transaction($conn);

try {
    // Hitung total dari semua item dan total berat
    $total = 0;
    $totalWeight = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];

        // Ambil weight dari database untuk produk ini
        $kode = mysqli_real_escape_string($conn, $item['product']);
        $weightQuery = "SELECT weight FROM tbl_product WHERE kode = '$kode'";
        $weightResult = mysqli_query($conn, $weightQuery);

        if ($weightResult && mysqli_num_rows($weightResult) > 0) {
            $weightData = mysqli_fetch_assoc($weightResult);
            $productWeight = $weightData['weight'];
            $totalWeight += ($productWeight * $item['qty']);
        }
    }

    // Simpan data order baru ke tabel orders dengan total berat
    $sql = "INSERT INTO orders (total, total_weight) VALUES ($total, $totalWeight)";
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Gagal membuat order: " . mysqli_error($conn));
    }
    $orderId = mysqli_insert_id($conn);

    // Proses setiap item yang dipesan
    foreach ($items as $item) {
        // Ambil kode produk dan bersihkan
        $inputKode = trim($item['product']);

        // Periksa apakah produk ada di database
        $sql = "SELECT kode, merk, hargajual, stok, weight FROM tbl_product WHERE kode = '" . mysqli_real_escape_string($conn, $inputKode) . "'";
        $result = mysqli_query($conn, $sql);

        // Jika produk tidak ditemukan, batalkan transaksi
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Produk dengan kode '$inputKode' tidak ditemukan");
        }

        // Ambil detail produk dari database
        $productData = mysqli_fetch_assoc($result);
        $exactKode = $productData['kode']; // Gunakan nilai kode yang tepat dari database

        // Periksa apakah stok mencukupi
        if ($productData['stok'] < $item['qty']) {
            throw new Exception("Stok tidak cukup untuk produk '$exactKode'");
        }

        // Siapkan data untuk penyimpanan
        $productName = mysqli_real_escape_string($conn, $productData['merk']);
        $harga = $item['harga'];
        $qty = $item['qty'];
        $subtotal = $item['subtotal'];
        $itemWeight = $productData['weight'] * $qty; // Hitung berat per item pesanan

        // Simpan item pesanan ke tabel order_items dengan informasi berat
        $sql = "INSERT INTO order_items (order_id, kode, product, harga, qty, subtotal, weight) 
                VALUES ($orderId, '$exactKode', '$productName', $harga, $qty, $subtotal, $itemWeight)";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Gagal menyimpan item pesanan: " . mysqli_error($conn));
        }

        // Update stok produk
        $newStock = $productData['stok'] - $qty;
        $sql = "UPDATE tbl_product SET stok = $newStock WHERE kode = '$exactKode'";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Gagal mengupdate stok produk '$exactKode': " . mysqli_error($conn));
        }
    }

    // Commit transaksi jika semua operasi berhasil
    mysqli_commit($conn);
    $getresult = 1;
    $message = "Pesanan berhasil dibuat";
} catch (Exception $e) {
    // Batalkan transaksi jika terjadi error
    mysqli_rollback($conn);
    $getresult = 0;
    $message = "Pembuatan pesanan gagal: " . $e->getMessage();
}

// Kirim respons ke client
echo json_encode(array('result' => $getresult, 'message' => $message, 'total_weight' => $totalWeight ?? 0));
