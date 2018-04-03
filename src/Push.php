<?php

namespace Singiu\Singpush;

class Push
{
    private static $_config = null;

    /**
     * 设定配置信息。
     * @param $config
     */
    public static function setConfig($config)
    {
        self::$_config = $config;
    }

    /**
     * 统一推送接口。
     *
     * @param $deviceToken
     * @param $title
     * @param $message
     * @param $platform
     * @return mixed
     */
    public static function sendMessage($deviceToken, $title, $message, $platform)
    {
        $class = '\\Singiu\\Singpush\\Services\\' . ucfirst($platform) . 'Push';
        $push = new $class(self::$_config);
        if (method_exists($push, 'sendMessage')) {
            return $push->sendMessage($deviceToken, $title, $message);
        }
        return false;
    }
}