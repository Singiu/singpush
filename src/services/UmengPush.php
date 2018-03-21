<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Request;
use Singiu\Singpush\Contracts\PushInterface;

class UmengPush implements PushInterface
{
    private $app_key;
    private $app_master_secret;
    private $request;

    /**
     * 构造函数。
     */
    public function __construct()
    {
        $this->app_key = getenv('UMENG_APP_KEY');
        $this->app_master_secret = getenv('UMENG_APP_MASTER_SECRET');
        $this->request = new Request();
    }

    /**
     * 获取请求用的签名。
     * @param $method
     * @param $url
     * @param $postBody
     * @return string
     */
    private function getSign($method, $url, $postBody)
    {
        $signString = $method . $url . $postBody . $this->app_master_secret;
        return md5($signString);
    }

    /**
     * 发送消息通知。
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return \Singiu\Http\Response
     */
    public function sendMessage($deviceToken, $title, $message)
    {
        $payload = [
            'appkey' => $this->app_key,
            'timestamp' => time(),
            'type' => 'unicast', // 单播发送
            'device_tokens' => $deviceToken,
            'payload' => [
                'display_type' => 'notification',
                'body' => [
                    'ticker' => $message,
                    'title' => $title,
                    'text' => $message,
                    'after_open' => 'go_app' // 默认打开应用
                ]
            ]
        ];

        $requestUrl = 'http://msg.umeng.com/api/send';
        $data = json_encode($payload);
        $response = $this->request->post($requestUrl, [
            'query' => [
                'sign' => $this->getSign('POST', $requestUrl, $data)
            ],
            'data' => $data
        ]);
        return $response;
    }
}