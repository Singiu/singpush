<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Http;
use Singiu\Http\Request;
use Singiu\Http\Response;
use Singiu\Singpush\Contracts\PushInterface;

class HmsPush implements PushInterface
{
    private $_http;
    private $_clientId;
    private $_clientSecret;
    private $_accessToken;

    /**
     * 构造函数。
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        if ($config === null && !function_exists('getenv')) {
            throw new \Exception('Cannot found any configurations!');
        }
        if ($config != null && isset($config['hms']['client_id']) && $config['hms']['client_id'] != '') {
            $this->_clientId = $config['hms']['client_id'];
        } else if (function_exists('getenv')) {
            $this->_clientId = getenv('HMS_CLIENT_ID');
        } else {
            throw new \Exception('Cannot found configuration: hms.client_id!');
        }
        if ($config != null && isset($config['hms']['client_secret']) && $config['hms']['client_secret'] != '') {
            $this->_clientSecret = $config['hms']['client_secret'];
        } else if (function_exists('getenv')) {
            $this->_clientSecret = getenv('HMS_CLIENT_SECRET');
        } else {
            throw new \Exception('Cannot found configuration: hms.client_secret!');
        }
        $this->_http = new Request();
        $this->_http->setHttpVersion(Http::HTTP_VERSION_1_1);
    }

    /**
     * 请求新的 Access Token。
     */
    private function _getAccessToken()
    {
        $response = $this->_http->post('https://login.cloud.huawei.com/oauth2/v2/token', [
            'data' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->_clientId,
                'client_secret' => $this->_clientSecret
            ]
        ]);
        $this->_accessToken = $response->getResponseObject()->access_token;
        // return $response->getResponseText();
    }

    /**
     * 发送华为推送消息。
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return Response
     * @throws
     */
    public function sendMessage($deviceToken, $title, $message)
    {
        // date_default_timezone_set('Asia/Shanghai');
        // 构建 Payload
        if (is_array($message)) {
            $payload = json_encode($message, JSON_UNESCAPED_UNICODE);
        } else if (is_string($message)) {
            $payload = json_encode([
                'hps' => [
                    'msg' => [
                        'type' => 3,
                        'body' => [
                            'content' => $message,
                            'title' => $title
                        ],
                        'action' => [
                            'type' => 3,
                            'param' => [
                                'appPkgName' => 'cn.figo.aiqilv.test'
                            ]
                        ]
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $payload = '';
        }
        // 发送消息通知
        $this->_getAccessToken();
        $response = $this->_http->post('https://api.push.hicloud.com/pushsend.do', [
            'query' => [
                'nsp_ctx' => json_encode(['ver' => '1', 'appId' => $this->_clientId])
            ],
            'data' => [
                'access_token' => $this->_accessToken,
                'nsp_ts' => time(),
                'nsp_svc' => 'openpush.message.api.send',
                'device_token_list' => json_encode([$deviceToken]),
                'payload' => $payload
            ]
        ]);
        return $response;
    }
}