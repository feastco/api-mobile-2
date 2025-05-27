<?php
include "koneksimysql.php";
header('content-type: application/json');

// Get parameter from GET request instead of POST
$kode = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';

if (!empty($kode)) {
    // Check if the product exists
    $sql = "SELECT * FROM tbl_product WHERE kode = '$kode'";
    $hasil = mysqli_query($conn, $sql);

    if (mysqli_num_rows($hasil) > 0) {
        // Increment the visit count
        $sql = "UPDATE tbl_product SET visit = visit + 1 WHERE kode = '$kode'";
        if (mysqli_query($conn, $sql)) {
            // Get the updated visit count
            $sql = "SELECT visit FROM tbl_product WHERE kode = '$kode'";
            $result = mysqli_query($conn, $sql);
            $data = mysqli_fetch_assoc($result);

            echo json_encode([
                'status' => 'success',
                'message' => 'Visit count berhasil diperbarui',
                'visit_count' => (int)$data['visit']  // Return the updated visit count
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal memperbarui visit count: ' . mysqli_error($conn)
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kode produk tidak diberikan'
    ]);
}
