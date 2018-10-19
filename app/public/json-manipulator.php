<?php

$id = "1";
$newSession = "Session" . time();
$jsonFile = "users.json";

$data = json_decode(file_get_contents($jsonFile));

foreach($data->users as $item) {
    if($item->id == $id) {
        $item->sessionId = $newSession;
    }
}

$newJsonString = json_encode($data);
file_put_contents($jsonFile, $newJsonString);

?>