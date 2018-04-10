
## 整合了小米推送、华为推送、友盟推送、苹果推送服务的整合包

[![Latest Stable Version](https://poser.pugx.org/singiu/singpush/v/stable)](https://packagist.org/packages/singiu/singpush)
[![Total Downloads](https://poser.pugx.org/singiu/singpush/downloads)](https://packagist.org/packages/singiu/singpush)
[![Latest Unstable Version](https://poser.pugx.org/singiu/singpush/v/unstable)](https://packagist.org/packages/singiu/singpush)
[![License](https://poser.pugx.org/singiu/singpush/license)](https://packagist.org/packages/singiu/singpush)

这个是我自己项目中使用的包，因为在国内不能使用 Google 推送服务，Android 又来兼容各平台机型，所以需要在客户端植入各种推送 SDK。

后台服务也需要对应开发各类推送服务，我不想去接入各个平台提供的服务端 SDK，所以自己做了一个这样的整合包，目前功能只有发送简单的推送消息栏通知。

用户点击消息也是默认跳回到 app 而已。所以如果不能满足你的需求的，就不要使用了。

后续如果有时间，我会加入**自定义消息推送**和**消息透传**的功能，这样可能就会比较大众一些了。

### 安装
可以使用 Composer 安装：
```bash
composer require singiu/singpush
```

### 使用方法

#### 发关消息栏通知
```php
// 准备配置信息，不用全部都设定，用到哪个推送服务只设定对应的配置就可以了。
$config = array(
    // 苹果推送配置段。
    'apns' => [
        'certificate_path' => '证书地址，必需是绝对路径。如：/etc/nginx/cert/app_push_cert.pem',
        'certificate_passphrase' => '证书密码',
        'environment' => '如果是测试就填 sandbox，正式填production'
    ],
    // 小米推送配置段。
    'mi' => [
        'app_package_name' => '注意这里是填入 Android 应用的包名',
        'app_secret' => '填入对应资料'
    ],
    // 友盟推送配置段。
    'umeng' => [
        'app_key' => '填入对应资料',
        'app_master_secret' => '填入对应资料'
    ],
    // 华为推送配置段。
    'hms' => [
        'client_id' => '填入对应资料',
        'client_secret' => '填入对应资料'
    ]
);

// 然后可以这样使用。
$device_token = 'Your device token string'; // 从对应推送服务商那里获取的设备唯一标识符。
$title = '推送的消息标题';
$message = '需要推送的消息内容';
$push_service = 'apns'; // 需要使用的推送服务标志，对应配置信息中的 key 值，为 apns, mi, umeng, hms 中的一个。
Push::setConfig($config); // 设定配置。
Push::sendMessage($device_token, $title, $message, $push_service); // 推送消息。
```

如果你觉得配置过于麻烦，我推荐使用一个 php 下好用的配置包： [symfony/dotenv](https://github.com/symfony/dotenv)。

如果你会使用这个包，那就只需要将 vendor/singiu/singpush/src/.env.example 文件中对应的配置信息填好，然后拷贝到你的项目根目录下，并改名为 .env（如果你已经有这个文件，那就将 .env.example 中的内容复制并粘贴到你的 .env 文件的内容后面）。

一旦你这样做了，配合 symfony/dotenv 包，你就可以省去配置的环节，直接在你的项目的任何位置使用推送服务。如下：

```php
$device_token = 'Your device token string';
$title = '推送的消息标题';
$message = '需要推送的消息内容';
$push_service = 'apns';
Push::sendMessage($device_token, $title, $message, $push_service);
```

### License

The MIT License (MIT). Please see [License File](LICENSE) for more information.