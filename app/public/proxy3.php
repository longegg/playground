<?php

$endpoint =  $_GET['method']; 

$url = "https://booking.raise.no/api/v2/";

$payload = trim(file_get_contents("php://input"));

$response = forward($url, $endpoint, $payload);
// $resArr = array();
// $resArr = json_decode($response);
// echo "<pre>"; print_r($resArr); echo "</pre>";
echo $response;

function forward($url, $endpoint, $payload) {
    $token = "FC0DC4DA-EA2C-40A5-B6E3-DCC2486094B0";
    $authorization = "Authorization: Bearer " . $token;
    $redirect_url = $url . $endpoint;

    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        // CURLOPT_HEADER         => false,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization ),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload
    ); 

    $ch = curl_init($redirect_url);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if (!isset($response)) {
        return null;
    }
    return $response;
}

?>