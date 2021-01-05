<?php

namespace Adimeo\SecurityChecker\Adapter;

interface AdapterInterface
{
    public static function transmit($result, $argv);
}