<?php

session_start();

$sessionTimeout = 1440;
$sessionIdResetTimout = 600;
$requestType = $_SERVER['REQUEST_METHOD'];
$endpoint =  $_GET['method'];
$salonId = $_GET['salonId'];
$url = "https://booking.raise.no/api/v2/";
$payload = trim(file_get_contents("php://input"));
$isPost = $requestType == "POST";
$querystring = stripQueryParams($_SERVER['QUERY_STRING']);
$sessionName = "customerId";
$lockedEndpoints = array("customer", "activity");
$customerIsLoggingIn = $endpoint == "customer/search";
$customerIsLoggingOut = $endpoint == "logout";

if ($customerIsLoggingOut) {
    destroySession();
    http_response_code(204);
    return;
}

if (!$customerIsLoggingIn) {
    updateSessionActivity($sessionIdResetTimout);
    resetSessionId($sessionTimeout);
}

if ($endpoint == "isAuthenticated") {
    echo isAuthenticated($sessionName)  == null ? "false" : "true";
    return;
}

if (endpointIsLocked($endpoint, $lockedEndpoints, $customerIsLoggingIn)) {
    if (!isAuthenticated($sessionName)) {
        http_response_code(401);  
        return;
    }
}

$token = findAPIKey($salonId);
$response = forwardRequest($url, $endpoint, $payload, $token, $isPost, $querystring);

if ($customerIsLoggingIn) {
    $customerId = findCustomerId($response);
    createSession($customerId, $sessionName);
}

echo $response;

function json_response($message = null, $code = 200) {
    header_remove();
    http_response_code($code);
    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900"); // Forces cache
    header('Content-Type: application/json');

    $status = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
    );

    header('Status: '. $status[$code]);

    return json_encode(array(
        'status' => $code < 300, // success or not?
        'message' => $message
    ));
}

function endpointIsLocked($endpoint, $lockedEndpoints, $customerIsLoggingIn) {
    if ($customerIsLoggingIn) {
        return false;
    }

    foreach ($lockedEndpoints as $e) {
        $search_length = strlen($e);
        if (substr($endpoint, 0, $search_length) == $e) {
            return true;
        }       
    }
    return false;
}

function updateSessionActivity ($timeout) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
        // Last request was more than n minutes ago.
        destroySession();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function destroySession() {
    session_unset();
    session_destroy();
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
        return $_SESSION[$sessionName]; //session_id();
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

function forwardRequest($url, $endpoint, $payload, $token, $isPost, $params) {
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