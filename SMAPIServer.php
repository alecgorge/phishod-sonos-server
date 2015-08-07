<?php

function getLogFilePath() {
    return "SonosAPI.log";
}

$logLevel = 3;
function logMsg($level,$msg) {
    global $logLevel;
    
    if ($level <= $logLevel) {
        error_log($msg . "\r\n", 3, getLogFilePath());
    }
}

include 'PhishOD_SMAPI.php';

$start = microtime(true);
$server = new SoapServer('lib/Sonos.wsdl', array('cache_wsdl' => 0));
$server->setClass('SonosAPI');

try {
    $server->handle();
}
catch (Exception $e) {
    logMsg(0, "exception: " . $e->getMessage());

    $errorId2msgId = [
        'Server.ServiceUnknownError'  => "MSG_SOAPFAULT_SERVICE_UNKNOWN_ERROR",
        'Server.ServiceUnavailable'   => "MSG_SOAPFAULT_SERVICE_UNAVAILABLE",
        'Client.SessionIdInvalid'     => "MSG_SOAPFAULT_SESSION_ID_INVALID",
        'Client.LoginInvalid'         => "MSG_SOAPFAULT_LOGIN_UNAUTHORIZED",
        'Client.LoginDisabled'        => "MSG_SOAPFAULT_LOGIN_DISABLED",
        'Client.LoginUnauthorized'    => "MSG_SOAPFAULT_LOGIN_UNAUTHORIZED",
        'Client.DeviceLimit'          => "MSG_SOAPFAULT_DEVICE_LIMIT",
        'Client.UnsupportedTerritory' => "MSG_SOAPFAULT_UNSUPPORTED_TERRITORY",
        'Client.ItemNotFound'         => "MSG_SOAPFAULT_ITEM_NOT_FOUND",
    ];

    $requestContents = "\n".file_get_contents('php://input')."\n"; // reset this to just the input on any fault
    
    $transittime = number_format(microtime(true)-$start,6);
    logMsg(0, $request . $requestContents . "\nRESPONSE ($transittime): ERROR: " . 
    	      $errorId2msgId[$e->getMessage()] . 
              ' ('.$e->getCode().': '.$e->getFile().':'.$e->getLine().")\n".
              "---------------------------------------------------\n");
    
    $server->fault($e->getMessage(), $errorId2msgId[$e->getMessage()] . 
                   ' ('.$e->getCode().': '.$e->getFile().':'.$e->getLine().')');
    // $server->fault ends processing, so this line is never reached.
}
