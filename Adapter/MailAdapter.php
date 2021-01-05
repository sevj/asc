<?php

namespace Adimeo\SecurityChecker\Adapter;

/**
 * Class MailAdapter
 * @package Adimeo\SecurityChecker\Adapter
 */
class MailAdapter implements AdapterInterface
{
    public static function transmit($result, $argv)
    {
        $message = self::formatMessage($result);
        foreach ($argv as $email) {
            mail($email, 'Adimeo/SecurityCheck', $message);
        }
    }

    protected static function formatMessage($result)
    {
        $message = "SecurityCheck \n";

        foreach ($result as $target => $items) {
            $message .= "" . $target;
            foreach ($items as $item) {
                $message .= '<ul>';
                $message .= '<li>title : ' . $item['title'] . "</li>";
                $message .= '<li>package : ' . $item['package'] . "</li>";
                $message .= '<li>version : ' . $item['version'] . "</li>";
                $message .= '</ul>';
            }
            $message .= '';
        }



        return $message;
    }
}