<?php

require_once dirname(__FILE__) . '/../paysystem.php';

$paysystem = new PaySystem();
echo $paysystem->getIframeForm();