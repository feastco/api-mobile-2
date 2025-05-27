<?php
include "koneksimysql.php";
header('content-type: application/json');

// Define both filesystem path and web path
$FILESYSTEM_PATH = "/www/wwwroot/androidfisco/api-mobile-2/images/";
$WEB_PATH = "/api-mobile-2/images/"; // Change this to match your actual web path
$filename = "user_" . date("YmdHis") . rand(9, 999) . ".jpg";

$res = array();
$kode = 0;
$pesan = "";

// Check if username is provided - changed from user_id to username
$username = isset($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : '';

if (empty($username)) {
    $kode = 0;
    $pesan = "Error: username is required";
} else {
    // Check if user exists - using username instead of id
    $sql_check = "SELECT * FROM tb_user WHERE username = '$username'";
    $check_result = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($check_result) == 0) {
        $kode = 0;
        $pesan = "Error: User not found";
    } else {
        if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_FILES['foto'])) {
            // Debug information
            error_log(print_r($_FILES['foto'], true));

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $kode = 0;
                $pesan = "Upload Error: " . $_FILES['foto']['error'];
            } else {
                $temp_name = $_FILES['foto']['tmp_name'];
                $file_dest = $FILESYSTEM_PATH . $filename;
                $db_path = $WEB_PATH . $filename; // This is what we'll store in the database

                // Check if directory exists and is writable
                if (!is_dir($FILESYSTEM_PATH) || !is_writable($FILESYSTEM_PATH)) {
                    $kode = 0;
                    $pesan = "Directory not accessible or not writable. Path: " . $FILESYSTEM_PATH .
                        " Exists: " . (is_dir($FILESYSTEM_PATH) ? 'Yes' : 'No') .
                        " Writable: " . (is_writable($FILESYSTEM_PATH) ? 'Yes' : 'No');
                    error_log($pesan);
                } else {
                    // Get the old photo path to delete it later - using username
                    $sql_get_old = "SELECT foto FROM tb_user WHERE username = '$username'";
                    $old_result = mysqli_query($conn, $sql_get_old);
                    $old_data = mysqli_fetch_assoc($old_result);
                    $old_photo = isset($old_data['foto']) ? $old_data['foto'] : '';

                    // Upload the new photo
                    if (move_uploaded_file($temp_name, $file_dest)) {
                        // Set file permissions to be readable by everyone
                        chmod($file_dest, 0644);

                        // Update the user's photo in the database with the web path - using username
                        $sql_update = "UPDATE tb_user SET foto = '$filename' WHERE username = '$username'";

                        if (mysqli_query($conn, $sql_update)) {
                            $kode = 1;
                            $pesan = "Foto profil berhasil diupdate";

                            // Delete the old photo if it exists and is not the default photo
                            if (!empty($old_photo) && $old_photo != '/images/default.jpg') {
                                $old_file_path = str_replace($WEB_PATH, $FILESYSTEM_PATH, $old_photo);
                                if (file_exists($old_file_path)) {
                                    @unlink($old_file_path);
                                }
                            }
                        } else {
                            $kode = 0;
                            $pesan = "Database Error: " . mysqli_error($conn);
                        }
                    } else {
                        $kode = 0;
                        $pesan = "Upload Gagal: Tidak dapat memindahkan file";
                    }
                }
            }
        } else {
            $kode = 0;
            $pesan = "No image uploaded or invalid request method";
        }
    }
}

$res['kode'] = $kode;
$res['pesan'] = $pesan;
if ($kode == 1) {
    $res['file_path'] = $db_path; // Return the web path, not the filesystem path
    $res['filename'] = $filename;
    // Add the complete URL for easier debugging
    $res['image_url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") .
        $_SERVER['HTTP_HOST'] . $db_path;
}

echo json_encode($res);
mysqli_close($conn);
