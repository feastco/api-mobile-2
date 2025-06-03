<?php
$BASE_URL_IMAGES = "/var/www/webserver/images/";
$filename = "img" . date("YmdHis") . rand(9, 999) . ".jpg";

// Database connection
$servername = "localhost";
$username = "root";
$password = "vasco123";
$dbname = "androiduts"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res = array();
$kode = "";
$pesan = "";

// if ($_SERVER['REQUEST_METHOD'] == "POST") {
//     if ($_FILES['imageupload']) {
//         $temp_name = $_FILES['imageupload']['tmp_name'];
//         $dest = $BASE_URL_IMAGES . $filename;

//         // Move the uploaded image to the destination folder
//         if (move_uploaded_file($temp_name, $dest)) {
//             // Prepare an SQL query to insert image details into the database
//             $sql = "INSERT INTO images (filename, path) VALUES ('$filename', '$dest')";

//             if ($conn->query($sql) === TRUE) {
//                 $kode = 1;
//                 $pesan = "Upload Sukses dan data disimpan ke database";
//             } else {
//                 $kode = 0;
//                 $pesan = "Error: " . $conn->error;
//             }
//         } else {
//             $kode = 0;
//             $pesan = "Upload Gagal";
//         }
//     } else {
//         $kode = 0;
//         $pesan = "Upload Gagal";
//     }
// } else {
//     $kode = 0;
//     $pesan = "Upload Gagal";
// }

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_FILES['imageupload'])) {
        // Debug information
        error_log(print_r($_FILES['imageupload'], true));

        if ($_FILES['imageupload']['error'] !== UPLOAD_ERR_OK) {
            $kode = 0;
            $pesan = "Upload Error: " . $_FILES['imageupload']['error'];
        } else {
            $temp_name = $_FILES['imageupload']['tmp_name'];
            $dest = $BASE_URL_IMAGES . $filename;

            // Check if directory exists and is writable
            if (!is_dir($BASE_URL_IMAGES) || !is_writable($BASE_URL_IMAGES)) {
                $kode = 0;
                $pesan = "Directory not accessible or not writable. Path: " . $BASE_URL_IMAGES .
                    " Exists: " . (is_dir($BASE_URL_IMAGES) ? 'Yes' : 'No') .
                    " Writable: " . (is_writable($BASE_URL_IMAGES) ? 'Yes' : 'No');
                error_log($pesan);
            } else {
                if (move_uploaded_file($temp_name, $dest)) {
                    // Prepare an SQL query to insert image details into the database
                    $sql = "INSERT INTO images (filename, path) VALUES ('$filename', '$dest')";

                    if ($conn->query($sql) === TRUE) {
                        $kode = 1;
                        $pesan = "Upload Sukses dan data disimpan ke database";
                    } else {
                        $kode = 0;
                        $pesan = "Error: " . $conn->error;
                    }
                } else {
                    $kode = 0;
                    $pesan = "Upload Gagal";
                }
            }
        }
    }
}


$res['kode'] = $kode;
$res['pesan'] = $pesan;

echo json_encode($res);

$conn->close();
