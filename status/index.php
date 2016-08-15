<?php

require_once dirname(__FILE__) . '/../paysystem.php';

$paysystem = new PaySystem();
$statusResult = $paysystem->statusUrl();
error_log(print_r($statusResult, true));

if (isset($statusResult['answerForPaySystem']['text'])) {
    exit($statusResult['answerForPaySystem']['text']);
}

echo ('OK');