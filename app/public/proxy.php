<?php
$salonId =  $_GET['salonId'];
$endpoint =  $_GET['method'];

$json = json_decode(file_get_contents('salons.json'));
$token = "";

foreach($json->Salons as $item) {
    if($item->Id == $salonId) {
        $token = $item->APIKey;
    }
}

header('X-Auth: Bearer ' . $token);
header('X-Accel-Redirect: /api/' . $endpoint);
?>