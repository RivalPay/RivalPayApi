<?php

require_once dirname(__FILE__) . '/../paysystem.php';

$paysystem = new PaySystem();
$statusResult = $paysystem->statusUrl();
error_log(print_r($statusResult, true));

if ($statusResult['success']) {

    if (!empty($statusResult['data'])) {
        // Действия по предоставлению услуги пользователю при успешной оплате
        // $trObj = new Transaction($statusResult['data']['paymentId']);
        // $trObj->completeTransactionId();
        // ............
    } elseif (isset($statusResult['answerForPaySystem']['text'])) {
        exit($statusResult['answerForPaySystem']['text']);
    } elseif (isset($statusResult['answerForPaySystem']['xml'])) {
        header("HTTP/1.0 200");
        header("Content-Type: application/xml");
        exit($statusResult['answerForPaySystem']['xml']);
    }
    exit('OK');
} else {
    // Server not responding
}