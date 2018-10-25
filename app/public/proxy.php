<?php

session_start();

$sessionTimeout = 10;

updateSessionActivity(20);
resetSessionId($sessionTimeout);

$requestType = $_SERVER['REQUEST_METHOD'];
$endpoint =  $_GET['method'];
$salonId = $_GET['salonId'];
$url = "https://booking.raise.no/api/v2/";
$payload = trim(file_get_contents("php://input"));
$isPost = $requestType == "POST";
$querystring = stripQueryParams($_SERVER['QUERY_STRING']);
$sessionCustomerId = "customerId";
$lockedEndpoints = array("locked", "customer/2180057/activities");

if (endpointIsLocked($endpoint, $lockedEndpoints)) {
    if (!isAuthenticated($sessionCustomerId)) {
        echo "You are not logged in";
        return;
    }
}

if ($endpoint == "customer/isAuthenticated") {
    echo isAuthenticated($sessionCustomerId)  == null ? "false" : "true";
    return;
}

$token = findAPIKey($salonId);

$response = forward($url, $endpoint, $payload, $token, $isPost, $querystring);

if ($endpoint == "customer/search") {
    $customerId = findCustomerId($response);
    createSession($customerId, $sessionCustomerId);
}

echo $response;

function endpointIsLocked($endpoint, $lockedEndpoints) {
    return (in_array($endpoint, $lockedEndpoints));
}

function updateSessionActivity ($timeout) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
        // Last request was more than n minutes ago.
        session_unset();
        session_destroy();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function resetSessionId($timeout) {
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } else if (time() - $_SESSION['CREATED'] > $timeout) {
        // Session started more than n minutes ago.
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    } 
}

function retriveSession($sessionName) {
    if (isset($_SESSION[$sessionName])) {
        return session_id(); //$_SESSION[$sessionName];
    }
    return null;
}

function isAuthenticated($sessionName) {
    return retriveSession($sessionName) != null;
}

function createSession($customerId, $sessionName) {
    if (!isset($_SESSION[$sessionName])) {
        $_SESSION[$sessionName] = $customerId;
    } 
}

function findCustomerId($response) {
    $decoded = json_decode($response);
    return $decoded->Id;
}

function stripQueryParams($querystring) {
    if ($querystring = "") {
        return;
    }
    parse_str($querystring, $ar);
    unset($ar["method"]);
    unset($ar["salonId"]);
    return http_build_query($ar);
}

function findAPIKey($salonId) {
    $json = json_decode(file_get_contents('../salons.json'));
    $token = "";
    
    foreach($json->Salons as $item) {
        if($item->Id == $salonId) {
            return $item->APIKey;
        }
    }
}

function forward($url, $endpoint, $payload, $token, $isPost, $params) {
    $authorization = "Authorization: Bearer " . $token;
    $redirect_url = $url . $endpoint;
    
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization )
    );

    if ($isPost) {
        $options[CURLOPT_POST] = true;
    }

    if ($payload != "") {
        $options[CURLOPT_POSTFIELDS] = $payload;
        $options[CURLOPT_POST] = true;
    }

    $redirect_url = $redirect_url . "?" . $params;

    $ch = curl_init($redirect_url);
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if (!isset($response)) {
        return null;
    }
    return $response;
}

?>