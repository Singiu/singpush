<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Request;
use Singiu\Singpush\Contracts\PushInterface;

class UmengPush implements PushInterface
{
    private $_appKey;
    private $_appMasterSecret;
    private $request;

    /**
     * 构造函数。
     *
     * @param array $config
     */
    public function __construct($config = null)
    {
        if ($config != null && isset($config['umeng']['app_key']) && $config['umeng']['app_key'] != '') {
            $this->_appKey = $config['umeng']['app_key'];
        } else {
            $this->_appKey = getenv('UMENG_APP_KEY');
        }
        if ($config != null && isset($config['umeng']['app_master_secret']) && $config['umeng']['app_master_secret'] != '') {
            $this->_appMasterSecret = $config['umeng']['app_master_secret'];
        } else {
            $this->_appMasterSecret = getenv('UMENG_APP_MASTER_SECRET');
        }
        $this->request = new Request();
    }

    /**
     * 获取请求用的签名。
     * @param $method
     * @param $url
     * @param $postBody
     * @return string
     */
    private function _getSign($method, $url, $postBody)
    {
        $sign_string = $method . $url . $postBody . $this->_appMasterSecret;
        return md5($sign_string);
    }

    /**
     * 发送消息通知。
     *
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return \Singiu\Http\Response
     * @throws
     */
    public function sendMessage($deviceToken, $title, $message)
    {
        $payload = [
            'appkey' => $this->_appKey,
            'timestamp' => time() . '', // 转成字符串。
            'type' => 'unicast', // 单播发送
            'device_tokens' => $deviceToken,
            'production_mode' => true,
            'payload' => [
                'display_type' => 'notification',
                'body' => [
                    'ticker' => $message,
                    'title' => $title,
                    'text' => $message,
                    'after_open' => 'go_app', // 默认打开应用
                    "play_vibrate" => "false",
                    "play_lights" => "false",
                    "play_sound" => "true"
                ]
            ]
        ];

        $requestUrl = 'https://msgapi.umeng.com/api/send';
        $data = json_encode($payload);
        // die($data);
        $response = $this->request->post($requestUrl, [
            'query' => [
                'sign' => $this->_getSign('POST', $requestUrl, $data)
            ],
            'data' => $data
        ]);
        return $response;
    }
}