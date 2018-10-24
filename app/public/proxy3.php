<?php

$endpoint =  $_GET['method'];
$salonId = $_GET['salonId'];
$url = "https://booking.raise.no/api/v2/";
$payload = trim(file_get_contents("php://input"));

$token = findAPIKey($salonId);

$response = forward($url, $endpoint, $payload, $token);
echo $response;

function findAPIKey($salonId) {
    $json = json_decode(file_get_contents('salons.json'));
    $token = "";
    
    foreach($json->Salons as $item) {
        if($item->Id == $salonId) {
            return $item->APIKey;
        }
    }
}

function forward($url, $endpoint, $payload, $token) {
    // $token = "FC0DC4DA-EA2C-40A5-B6E3-DCC2486094B0";
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
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if (!isset($response)) {
        return null;
    }
    return $response;
}

?>