<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Request;
use Singiu\Singpush\Contracts\PushInterface;

class MiPush implements PushInterface
{
    private $app_package_name;
    private $app_secret;
    private $request;

    public function __construct()
    {
        $this->app_package_name = getenv('MI_APP_PACKAGE_NAME');
        $this->app_secret = getenv('MI_APP_SECRET');
        $this->request = new Request();
    }

    public function sendMessage($deviceToken, $title, $message)
    {
        $payload = [
            'title' => $title, // 通知栏展示的通知的标题，这里统一不显示。
            'description' => $message,
            'pass_through' => 0, // 设定是否为透传消息，0 = 推送消息，1 = 透传消息。
            'payload' => $message, // 消息内容。
            'notify_type' => -1, // 提示通知默认设定，-1 = DEFAULT_ALL。
            'extra.notify_effect' => 1, // 预定义通知栏消息的点击行为，1 = 打开 app 的 Launcher Activity，2 = 打开 app 的任一 Activity（还需要 extra.intent_uri）,3 = 打开网页（还需要传入 extra.web_uri）
            'restricted_package_name' => $this->app_package_name,
            'registration_id' => $deviceToken
        ];

        $response = $this->request->post('https://api.xmpush.xiaomi.com/v3/message/regid', [
            'headers' => [
                'Authorization' => 'key=' . $this->app_secret
            ],
            'data' => $payload
        ]);

        return $response;
    }
}