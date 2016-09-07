<?php

require_once dirname(__FILE__) . '/../paysystem.php';

$paysystem = new PaySystem();
$statusResult = $paysystem->statusUrl();
error_log(print_r($statusResult, true));

if (isset($statusResult['success'])) {

    if (!empty($statusResult['data'])) {
        // Действия по предоставлению услуги пользователю при успешной оплате
        // Пример:
        // $trObj = new Transaction($statusResult['data']['paymentId']);
        // $trObj->completeTransactionId();
        // ............
    }

    if (isset($statusResult['answerForPaySystem']['text'])) {
        exit($statusResult['answerForPaySystem']['text']);
    } elseif (isset($statusResult['answerForPaySystem']['xml'])) {
        header("HTTP/1.0 200");
        header("Content-Type: application/xml");
        exit($statusResult['answerForPaySystem']['xml']);
    }
    exit(json_encode(array('result' => array('message' => 'OK'))));
} else {
    // Server not responding
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
}


