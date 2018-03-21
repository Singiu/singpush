<?php

namespace Singiu\Singpush;

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env.example');

class Push
{
    public static function sendMessage($deviceToken, $title, $message, $platform)
    {
        $class = '\\Singiu\\Singpush\\Services\\' . ucfirst($platform) . 'Push';
        $push = new $class();
        return call_user_func_array(array($push, 'sendMessage'), [
            'deviceToken' => $deviceToken,
            'title' => $title,
            '$message' => $message
        ]);
    }
}