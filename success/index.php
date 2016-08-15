<?php

require_once dirname(__FILE__) . '/../paysystem.php';

$paysystem = new PaySystem();
$paysystem->checkSuccessUrl();