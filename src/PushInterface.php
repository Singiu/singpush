<?php

namespace Singiu\Singpush;

interface PushInterface
{
    public function sendMessage($deviceToken, $title, $message);
}