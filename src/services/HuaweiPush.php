<?php

namespace Singiu\Singpush\Services;

use Singiu\Http\Http;
use Singiu\Http\Request;
use Singiu\Http\Response;
use Singiu\Singpush\Contracts\PushInterface;

class HuaweiPush implements PushInterface
{
    private $http;
    private $client_id;
    private $client_secret;
    private $access_token;

    /**
     * 构造函数。
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct($clientId, $clientSecret)
    {
        $this->client_id = getenv('HUAWEI_CLIENT_ID');
        $this->client_secret = getenv('HUAWEI_CLIENT_SECRET');
        $this->http = new Request();
        $this->http->setHttpVersion(Http::HTTP_VERSION_1_1);
    }

    /**
     * 请求新的 Access Token。
     */
    private function getAccessToken()
    {
        $response = $this->http->post('https://login.cloud.huawei.com/oauth2/v2/token', [
            'data' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret
            ]
        ]);
        $this->access_token = $response->getResponseObject()->access_token;
    }

    /**
     * 发送华为推送消息。
     * @param $deviceToken
     * @param $title
     * @param $message
     * @return Response
     */
    public function sendMessage($deviceToken, $title, $message)
    {
        // 构建 Payload
        if (is_array($message)) {
            $payload = json_encode($message);
        } else if (is_string($message)) {
            $payload = json_encode([
                'hps' => [
                    'msg' => [
                        'type' => 1,
                        'body' => $message
                    ]
                ]
            ]);
        } else {
            $payload = '';
        }
        // 发送消息通知
        $this->getAccessToken();
        $response = $this->http->post('https://api.push.hicloud.com/pushsend.do', [
            'query' => [
                'nsp_ctx' => urlencode(json_encode(['ver' => '1', 'appId' => $this->client_id]))
            ],
            'data' => [
                'access_token' => $this->access_token,
                'nsp_ts' => time(),
                'nsp_svc' => 'openpush.message.api.send',
                'device_token_list' => json_encode([$deviceToken]),
                'payload' => $payload
            ]
        ]);
        return $response;
    }
}