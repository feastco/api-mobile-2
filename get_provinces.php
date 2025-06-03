    <?php
    header('Content-Type: application/json');
    include "koneksimysql.php";

    // API key Raja Ongkir
    $api_key = "419d120b08e2598fc331a9a665ba52da";

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.rajaongkir.com/starter/province",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
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
