<?php

namespace Adimeo\SecurityChecker\Adapter;

use Symfony\Component\HttpClient\HttpClient;

/**
 * Class ApiAdapter
 * @package Adimeo\SecurityChecker\Adapter
 */
class ApiAdapter implements AdapterInterface
{
    public static function transmit($result, $argv)
    {
        // Curl call
        foreach ($argv as $url) {
            $client = HttpClient::create();
            $client->request('POST', $url, [
                'body' => json_encode($result)
            ]);
        }
    }
}