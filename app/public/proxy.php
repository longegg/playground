<?php

session_start();

$sessionTimeout = 1440;
$sessionIdResetTimout = 600;
$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint =  $_GET['method'];
$salonId = $_GET['salonId'];
$customerId = isset($_GET['customerId']) ? $_GET['customerId'] : null;
$url = "https://booking.raise.no/api/v2/";
$payload = trim(file_get_contents("php://input"));
$requestType = getRequestType($requestMethod);
$isPost = $requestType == RequestType::POST;
$querystring = stripQueryParams($_SERVER['QUERY_STRING']);
$sessionName = "customerId";
$lockedEndpoints = array("customer", "activity");
$customerIsLoggingIn = $endpoint == "customer/search";
$customerIsLoggingOut = $endpoint == "logout";
$addingCustomer = $endpoint == "customer" && ($requestType == RequestType::POST || $requestType == RequestType::PUT);
$customerActionIsPermittable = $customerIsLoggingIn || $addingCustomer;

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
    if (isAuthenticated($sessionName, $customerId)) {
        http_response_code(204);
    } else {
        http_response_code(401);
    }
    
     return;
}

if (endpointIsLocked($endpoint, $lockedEndpoints) && !$customerActionIsPermittable) {
    if (!isAuthenticated($sessionName, $customerId)) {
        http_response_code(401);  
        return;
    }
}

$token = findAPIKey($salonId);
$response = forwardRequest($url, $endpoint, $payload, $token, $requestType, $querystring);

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

function endpointIsLocked($endpoint, $lockedEndpoints) {
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

function isAuthenticated($sessionName, $customerId) {
    $customerIdSession = retriveSession($sessionName);
    if ($customerIdSession == null) {
        return false;
    }

    if ($customerIdSession == $customerId) {
        return true;
    }

    return false;
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
    if (!isset($querystring) || trim($querystring) === '') {
        return;
    }

    parse_str($querystring, $ar);
    unset($ar["method"]);
    unset($ar["salonId"]);
    unset($ar["customerId"]);
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

function forwardRequest($url, $endpoint, $payload, $token, $requestType, $params) {
    $authorization = "Authorization: Bearer " . $token;
    $redirect_url = $url . $endpoint;
    
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization )
    );
    
    $options[CURLOPT_CUSTOMREQUEST] = $requestType;

    if ($payload != "") {
        $options[CURLOPT_POSTFIELDS] = $payload;
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

function getRequestType($requestMethod) {
    $requestType = RequestType::GET;

    switch ($requestMethod) {
        case "GET":
            $requestType = RequestType::GET;
            break;
        case "POST":
            $requestType = RequestType::POST;
            break;
        case "PUT":
            $requestType = RequestType::PUT;
            break;
        case "DELETE":
            $requestType = RequestType::DELETE;
            break;                                    
    }

    return $requestType;
}

abstract class RequestType {
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";
}

?>