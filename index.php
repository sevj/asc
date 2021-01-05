<?php

require_once __DIR__ . '/vendor/autoload.php';

$securityChecker = new \Adimeo\SecurityChecker\SecurityChecker($_SERVER['argv']);
$securityChecker->process();

