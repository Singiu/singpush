<?php

namespace Singiu\Singpush\Services;

use Singiu\Singpush\Contracts\PushInterface;

class ApnsPush implements PushInterface
{
    const ENVIRONMENT_PRODUCTION = 0;
    const ENVIRONMENT_SANDBOX = 1;
    const APPLE_PAYLOAD_NAMESPACE = 'aps';
    const PAYLOAD_MAXIMUM_SIZE = 2048;
    const DEVICE_TOKEN_SIZE = 32;

    protected $_badge;
    protected $_category;
    protected $_certificate;
    protected $_certificatePassphrase;

    // socket information.
    protected $_connectRetryTimes = 5;
    protected $_connectTimeout = 10; // 连接超时，单位秒
    protected $_contentAvailable; // 重连次数

    // message information.
    protected $_deviceToken;
    protected $_environment;
    protected $_expire;
    protected $_messageText;
    protected $_messageTitle;
    protected $_serverUrl = array(
        'tls://gateway.push.apple.com:2195', // Production environment
        'tls://gateway.sandbox.push.apple.com:2195' // Sandbox environment
    );
    protected $_socket;
    protected $_sound;

    /**
     * ApnsPush constructor.
     *
     * @param null $config
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        if ($config === null && !function_exists('getenv')) {
            throw new \Exception('Cannot found any configurations!');
        }

        if ($config != null && isset($config['apns']['certificate_path']) && $config['apns']['certificate_path'] != '') {
            $this->_certificate = $config['apns']['certificate_path'];
        } else if (function_exists('getenv')) {
            $this->_certificate = getenv('APNS_CERTIFICATE_PATH');
        } else {
            throw new \Exception('Cannot found configuration: apns.certificate_path!');
        }

        if ($config != null && isset($config['apns']['certificate_passphrase']) && $config['apns']['certificate_passphrase'] != '') {
            $this->_certificatePassphrase = $config['apns']['certificate_passphrase'];
        } else if (function_exists('getenv')) {
            $this->_certificatePassphrase = getenv('APNS_CERTIFICATE_PASSPHRASE');
        } else {
            throw new \Exception('Cannot found configuration: apns.certificate_passphrase!');
        }

        if ($config != null && isset($config['apns']['environment']) && $config['apns']['environment'] != '') {
            $this->_environment = $config['apns']['environment'] == 'production' ? self::ENVIRONMENT_PRODUCTION : self::ENVIRONMENT_SANDBOX;
        } else if (function_exists('getenv')) {
            $this->_environment = getenv('APNS_ENVIRONMENT') == 'production' ? self::ENVIRONMENT_PRODUCTION : self::ENVIRONMENT_SANDBOX;
            throw new \Exception('Cannot found configuration: apns.environment!');
        }

        $this->_expire = 3600 * 24;
    }

    protected function _connect()
    {
        $url = $this->_serverUrl[$this->_environment];
        $socketContext = stream_context_create([
            'ssl' => [
                'local_cert' => $this->_certificate,
                'passphrase' => $this->_certificatePassphrase
            ]
        ]);
        $retry = 0;
        while ($retry < $this->_connectRetryTimes) {
            if ($this->_socket = stream_socket_client($url, $errCode, $errMsg, $this->_connectTimeout, STREAM_CLIENT_CONNECT, $socketContext)) {
                break;
            } else if ($retry < $this->_connectRetryTimes) {
                $retry++;
            } else {
                throw new \Exception("Failed to connect to APNS server:{$errCode} ({$errMsg})");
            }
        }
    }

    protected function _disconnect()
    {
        if (is_resource($this->_socket)) {
            return fclose($this->_socket);
        }
        return false;
    }

    protected function _getNotificationBinary()
    {
        $payload = $this->_getPayload();
        $payloadLen = strlen($payload);
        $messageId = time();
        $expire = $messageId + $this->_expire;
        $binary = pack('CNNnH*n', 1, $messageId, $expire, self::DEVICE_TOKEN_SIZE, $this->_deviceToken, $payloadLen) . $payload;
        return $binary;
    }

    protected function _getPayload()
    {
        $payload[self::APPLE_PAYLOAD_NAMESPACE] = [];
        if (isset($this->_messageTitle))
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['alert']['title'] = $this->_messageTitle;
        if (isset($this->_messageText))
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['alert']['body'] = $this->_messageText;
        if (isset($this->_badge) && $this->_badge >= 0)
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['badge'] = $this->_badge;
        if (isset($this->_sound))
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['sound'] = $this->_sound;
        if (isset($this->_contentAvailable))
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['content-available'] = $this->_contentAvailable;
        if (isset($this->_category))
            $payload[self::APPLE_PAYLOAD_NAMESPACE]['category'] = $this->_category;
        /**
         * APNS 中 payload 不支持 \u* 格式的编码
         * 所以在使用 json_encode 函数的时候，需要使用 JSON_UNESCAPED_UNICODE 参数。
         * 然而这个参数只有在 PHP 5.4 及以上的版本才支持。
         */
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE 需要 PHP 5.4.0
        $payloadJson = str_replace(
            '"' . self::APPLE_PAYLOAD_NAMESPACE . '":[]',
            '"' . self::APPLE_PAYLOAD_NAMESPACE . '":{}',
            $payloadJson
        );
        $payloadLength = strlen($payloadJson);
        if ($payloadLength > self::PAYLOAD_MAXIMUM_SIZE) {
            throw new \Exception("Payload is too long:{$payloadLength} bytes. Maximum is " . self::PAYLOAD_MAXIMUM_SIZE . "bytes.");
        }
        return $payloadJson;
    }

    protected function _send()
    {
        $notificationBinary = $this->_getNotificationBinary();
        return fwrite($this->_socket, $notificationBinary);
    }

    /**
     * 添加接收设备的 deviceToken。
     *
     * @param $deviceToken
     * @throws \Exception
     */
    public function addRecipient($deviceToken)
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $deviceToken)) {
            throw new \Exception('Invalid device token!');
        }
        $this->_deviceToken = $deviceToken;
    }

    public function sendMessage($deviceToken, $title, $message)
    {
        $this->addRecipient($deviceToken);
        $this->setTitle($title);
        $this->setText($message);
        $this->setBadge(1);
        $this->setSound();
        $this->_connect();
        $this->_send();
        $this->_disconnect();
    }

    public function setBadge($badge = 0)
    {
        $this->_badge = $badge;
    }

    public function setSound($sound = 'default')
    {
        $this->_sound = $sound;
    }

    public function setText($text)
    {
        $this->_messageText = $text;
    }

    public function setTitle($title)
    {
        $this->_messageTitle = $title;
    }
}