<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Request;
use Singiu\Singpush\Contracts\PushInterface;

class MiPush implements PushInterface
{
    private $_appPackageName;
    private $_appSecret;
    private $_request;

    /**
     * MiPush constructor.
     *
     * @param null $config
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        if ($config === null && !function_exists('getenv')) {
            throw new \Exception('Cannot found any configurations!');
        }

        if ($config != null && isset($config['mi']['app_package_name']) && $config['mi']['app_package_name'] != '') {
            $this->_appPackageName = $config['mi']['app_package_name'];
        } else if (function_exists('getenv')) {
            $this->_appPackageName = getenv('MI_APP_PACKAGE_NAME');
        } else {
            throw new \Exception('Cannot found configuration: mi.app_package_name!');
        }

        if ($config != null && isset($config['mi']['app_secret']) && $config['mi']['app_secret'] != '') {
            $this->_appSecret = $config['mi']['app_secret'];
        } else if (function_exists('getenv')) {
            $this->_appSecret = getenv('MI_APP_SECRET');
        } else {
            throw new \Exception('Cannot found configuration: mi.app_secret!');
        }

        $this->_request = new Request();
    }

    /**
     * 发送推送通知。
     *
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return \Singiu\Http\Response
     * @throws \Exception
     */
    public function sendMessage($deviceToken, $title, $message)
    {
        $payload = [
            'title' => $title, // 通知栏展示的通知的标题，这里统一不显示。
            'description' => $message,
            'pass_through' => 0, // 设定是否为透传消息，0 = 推送消息，1 = 透传消息。
            'payload' => $message, // 消息内容。
            'notify_type' => -1, // 提示通知默认设定，-1 = DEFAULT_ALL。
            'extra.notify_effect' => 1, // 预定义通知栏消息的点击行为，1 = 打开 app 的 Launcher Activity，2 = 打开 app 的任一 Activity（还需要 extra.intent_uri）,3 = 打开网页（还需要传入 extra.web_uri）
            'restricted_package_name' => $this->_appPackageName,
            'registration_id' => $deviceToken
        ];

        $response = $this->_request->post('https://api.xmpush.xiaomi.com/v3/message/regid', [
            'headers' => [
                'Authorization' => 'key=' . $this->_appSecret
            ],
            'data' => $payload
        ]);

        return $response;
    }
}