    <?php
    header('Content-Type: application/json');
    include "koneksimysql.php";

    // API key Raja Ongkir
    $api_key = "419d120b08e2598fc331a9a665ba52da";

    // Data pengiriman dari parameter POST
    $origin = isset($_POST['origin']) ? $_POST['origin'] : ''; // ID kota asal
    $destination = isset($_POST['destination']) ? $_POST['destination'] : ''; // ID kota tujuan
    $weight = isset($_POST['weight']) ? $_POST['weight'] : ''; // Berat dalam gram
    $courier = isset($_POST['courier']) ? $_POST['courier'] : ''; // Kurir: jne, tiki, pos

    // Validasi input
    if (empty($origin) || empty($destination) || empty($weight) || empty($courier)) {
    echo json_encode([
        'status' => false,
        'message' => 'Semua parameter (origin, destination, weight, courier) dibutuhkan'
    ]);
    exit;
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "origin=" . $origin . "&destination=" . $destination . "&weight=" . $weight . "&courier=" . $courier,
    CURLOPT_HTTPHEADER => [
        "content-type: application/x-www-form-urlencoded",
        "key: " . $api_key
    ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
    echo json_encode([
        'status' => false,
        'message' => "cURL Error #:" . $err
    ]);
    } else {
    echo $response;
    }
    ?>