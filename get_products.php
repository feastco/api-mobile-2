<?php
include "koneksimysql.php";
header('Content-Type: application/json');

$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if ($kategori == 'all' && $search) {
  $sql = "SELECT * FROM tbl_product WHERE merk LIKE '%$search%'";
} elseif ($kategori == 'all') {
  $sql = "SELECT * FROM tbl_product";
} elseif ($kategori && $search) {
  $sql = "SELECT * FROM tbl_product WHERE kategori = '$kategori' AND (merk LIKE '%$search%')";
} elseif ($kategori) {
  $sql = "SELECT * FROM tbl_product WHERE kategori = '$kategori'";
} elseif ($search) {
  $sql = "SELECT * FROM tbl_product WHERE merk LIKE '%$search%'";
} else {
  $sql = "SELECT * FROM tbl_product";
}

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
    'kode' => $data->kode,
    'merk' => $data->merk,
    'kategori' => $data->kategori,
    'satuan' => $data->satuan,
    'hargabeli' => $data->hargabeli,
    'diskonbeli' => $data->diskonbeli,
    'hargapokok' => $data->hargapokok,
    'hargajual' => $data->hargajual,
    'diskonjual' => $data->diskonjual,
    'stok' => $data->stok,
    'weight' => $data->weight, // Add weight field to the response
    'foto' => $data->foto,
    'deskripsi' => $data->deskripsi,
    'visit' => $data->visit,
  ]);
}

echo json_encode(['result' => $result]);
